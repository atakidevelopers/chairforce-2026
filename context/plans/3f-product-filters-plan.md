# 3f — Product archive filters (Figma bar + AJAX) — Implementation Plan

## Status: ⏳ In progress (chunks 1–6 implemented; QA pending)

Plan locked from findings + UX decisions (31 Jul 2026).

| Chunk | Scope | Status |
|---|---|---|
| 1 | ACF theme options + PHP filter helpers (`Filterer`, dynamic attributes) | ✅ |
| 2 | Filter bar + panel markup (card grid) + chips partials | ✅ |
| 3 | Extend **`load-more` REST** (`mode`, filter params, product extras) | ✅ |
| 4 | Frontend JS (panel, AJAX, pushState, vertical/horizontal) | ✅ |
| 5 | Sass (bar, panel orientations, cards, chips) | ✅ |
| 6 | Template wiring + `WooCommerce_Archive` + Load More integration | ✅ |
| 7 | QA matrix (filters × panel modes × Load More) | ⏳ |

## Goal

Ship **theme-owned product archive filtering** on shop + WC product taxonomy
archives (Product Collection `inherit: true`):

- **Figma filter bar** — dynamic filter buttons, active chips, sort (+ optional
  grid/list toggle).
- **Floating filter panel** — all filters as **cards in a grid**; bar button
  focuses the matching card; panel stays open for multi-filter sessions.
- **Panel orientation** — **vertical** (side drawer) or **horizontal**
  (mega-menu-style deck under the bar); **theme options** per breakpoint.
- **AJAX refresh** — no full page reload on filter toggle; URL updates via
  `history.pushState`; **same `GET chairforce/v1/load-more` route** as page‑1
  Load More (extended — **no second catalog endpoint**).
- **Native WooCommerce** filtering only — `filter_{attribute}`, price params,
  `Filterer` facet counts. **No** WP Grid Builder / Jet Smart Filters.

**Source docs (read before implementing):**

- `context/notes/product-filters-findings.md` — live audit, locked decisions.
- `context/existing-functionality/12-shop-archive-filters.md` — WC layered nav.
- `context/existing-functionality/02-data-model-and-storage.md` — term meta.
- `context/existing-functionality/17-load-more-and-event-delegation.md` —
  delegated events + `chairforce:content-updated`.
- `context/plans/3i-load-more-plan.md` — REST partial HTML pattern to extend.
- `.cursor/rules/17-menu-system.mdc` — panel positioning reference (mega menu).
- `.cursor/skills/chairforce-woocommerce/` — WC class + REST conventions.

---

## Locked decisions

### UX

| Decision | Value |
|---|---|
| **Filter buttons** | **Dynamic** — only attributes with products in current archive (§2.3 findings) |
| **Panel content** | **All filters** as **card grid**; max-width cards; focus card from bar click |
| **Panel motion** | **Overlay only** — grid never pushed down (vertical drawer or horizontal bar-attached deck) |
| **Multi-filter session** | Panel **stays open**; chips + grid update **live** per toggle |
| **Full page reload on filter** | ❌ Rejected |
| **Facet plugin (WPGB / Jet)** | ❌ Not used — WC `Filterer` + lookup table |

### Panel orientation (theme options)

Add to **`acf-json/group_theme_options.json`** → **WooCommerce** tab:

| Field name | Type | Choices | Default |
|---|---|---|---|
| `filters_panel_desktop` | `select` | `vertical` \| `horizontal` | **`vertical`** |
| `filters_panel_mobile` | `select` | `vertical` \| `horizontal` | **`vertical`** |

| Mode | UI |
|---|---|
| **`vertical`** | Side off-canvas drawer (default desktop) |
| **`horizontal`** | Floating panel under filter bar — **same interaction model as header mega menu dropdown**; sticky to bar; internal scroll if tall |

**One markup tree** + modifier classes; orientation is mostly Sass (+ minor
positioning hooks). PM can switch in Theme Options without code changes.

**PHP helpers:**

```php
function chairforce_get_filters_panel_desktop(): string {
    $value = get_field( 'filters_panel_desktop', 'option' );
    return in_array( $value, [ 'vertical', 'horizontal' ], true ) ? $value : 'vertical';
}

function chairforce_get_filters_panel_mobile(): string {
    $value = get_field( 'filters_panel_mobile', 'option' );
    return in_array( $value, [ 'vertical', 'horizontal' ], true ) ? $value : 'vertical';
}
```

Localize on `Chairforce_Public`: `filtersPanelDesktop`, `filtersPanelMobile`.

### Filtering engine

| Layer | Source |
|---|---|
| Apply filters | Standard query params → `WC_Query` / main `tax_query` |
| Chosen state | `WC_Query::get_layered_nav_chosen_attributes()` + `min_price` / `max_price` |
| Term counts / hide empty | `Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer` |
| Colour swatches in cards | Reuse `Chairforce\Product_Swatches` read path |
| URL param names | `filter_colour`, `filter_indoor-outdoor`, … (attribute slug, not `filter_pa_*`) |

**Verify once on dev:** `woocommerce_attribute_lookup_enabled` = yes; lookup
table regenerated (WooCommerce → Status → Tools).

### REST / transport (locked)

| Decision | Value |
|---|---|
| **Catalog partial HTML** | **One route:** `GET chairforce/v1/load-more` (extend 3i — no `archive-products`) |
| **Client intent** | Required **`mode`**: `replace` \| `append` — JS instructs server; server validates (no referer guessing) |
| **`mode=replace`** | `page=1` — filter/sort refresh; client **replaces** grid inner HTML |
| **`mode=append`** | `page≥2` — Load More button; client **appends** (existing 3i behaviour) |
| **Product-only extras** | When `post_type` is `product` and `mode=replace`: also return `chipsHtml`, optional `panelHtml`, `resultsCountHtml`, updated `queryVars` |
| **Blog / non-product** | Same route; append-only; no chip/panel fields |
| **UI** | **One** Load More button + one pagination block in template — filter refresh is not a second button |

**Validation (server):**

- `mode=replace` → require `page === 1`
- `mode=append` → require `page >= 2`
- Mismatch → `400`

**Client modules (two callers, one route):**

- `product-filters.js` → always `mode=replace`, `page=1`, filter/sort params from URL
- `load-more.js` → always `mode=append`, `page=N` from button — unchanged append path

**State:** URL query string is source of truth for active filters (parse when
needed).

### Additional locked decisions (31 Jul 2026)

| Topic | Decision |
|---|---|
| **Sort changes** | Same as filters: update URL (`orderby` / `order`) + `load-more` with **`mode=replace`**, **`page=1`** — no full reload (live site parity) |
| **Browser Back / Forward** | On `popstate`, read URL → **`mode=replace`** fetch → update grid, chips, counts, Load More button (see note below) |
| **Empty filter groups** | If zero filter groups for archive, **omit filter bar** (sort/results/grid unchanged). Edge case only — products almost always expose attributes |

**What `popstate` means:** After filter AJAX we use `history.pushState`, so the
URL changes **without** reloading the page. When the user clicks the browser
**Back** or **Forward** button, the URL jumps to a prior/next state but the
page does not reload — the browser fires a **`popstate`** event. We must
listen for that and re-sync the grid/chips to match the URL (via
`mode=replace`), otherwise the address bar says `?filter_colour=blue` while the
grid still shows the old unfiltered products. Alternative would be forcing a
full page load on back/forward — rejected; AJAX sync matches live Woodmart
behaviour.

### Out of scope (3f v1)

- `/gallery/` filters (Phase 6)
- Blog filters (Phase 5)
- `venues` / `sales-by-location` as filter widgets (separate taxonomy archives)
- Grid/list view toggle — **defer unless PM promotes** (Figma shows it; not in
  current `archive-product.html`)
- Guest-specific filter behaviour (N/A — filters are public catalog)

---

## Architecture

```text
┌─────────────────────────────────────────────────────────────────┐
│ archive-product.html                                            │
├─────────────────────────────────────────────────────────────────┤
│ [new] product-filters-bar (buttons + chips + panel shell)       │
│ woocommerce/product-results-count + catalog-sorting             │
│ product-collection (page 1 grid + ONE Load More button)         │
└─────────────────────────────────────────────────────────────────┘

Filter toggle (panel stays open)
         │
         ▼
JS: pushState (filter_* / price / orderby in URL)
    GET /wp-json/chairforce/v1/load-more
        ?mode=replace&page=1&filter_colour=…&…
         │
         ▼
PHP: same query builder + card renderer as 3i
     + product fragments (chips, panel, counts)
         │
         ▼
product-filters.js: REPLACE .wc-block-product-template innerHTML
    update chips / counts / panel
    reset Load More button (nextPage=2, query_vars from response)
    dispatchContentUpdated({ source: 'filters' })

Load More click (unchanged contract + mode)
         │
         ▼
load-more.js: GET …/load-more?mode=append&page=2&query_vars=…
    APPEND html to grid
    dispatchContentUpdated({ source: 'load-more' })
```

**Initial page load / hard refresh:** server renders full archive from query
string (SEO, bookmarks). AJAX uses the same URL params the SSR page would use.

---

## REST API contract — extend `GET chairforce/v1/load-more`

**Do not register a second route.** Extend `includes/rest-api/load-more.php` and
shared helpers in `includes/load-more-functions.php`.

### Request params

| Param | Required | Notes |
|---|---|---|
| **`mode`** | **yes** | `replace` \| `append` |
| **`page`** | yes | `1` when `mode=replace`; `≥2` when `mode=append` |
| **`query_vars`** | no | JSON replay blob (extend to carry filter state after replace) |
| **`filter_*`** | no | WC attribute filters (when not fully covered by `query_vars`) |
| **`min_price`, `max_price`** | no | Price range |
| **`orderby`, `order`** | no | Catalog sort (same parsing as 3i — `fe21084`) |

Server validates `mode` + `page` pairing; builds `WP_Query` via existing Load
More helpers — **do not fork query/card logic**.

### Response (200) — base (all modes)

Same keys as shipped 3i: `html`, `nextPage`, `hasMore`, `maxPages`, `perPage`,
`total`, `viewingCount`, …

### Response extras — **`post_type=product` and `mode=replace` only**

```json
{
  "html": "<li class=\"wc-block-product\">…</li>…",
  "chipsHtml": "<div class=\"cf-active-filters\">…</div>",
  "panelHtml": "…",
  "resultsCountHtml": "…",
  "queryVars": { … },
  "total": 42,
  "hasMore": true,
  "maxPages": 5,
  "nextPage": 2
}
```

- **`html`:** page‑1 product `<li>` batch (client **replaces** grid).
- **`queryVars`:** write to Load More button `data-query-vars` after filter change.
- **`panelHtml`:** v1 may always return on product replace; optimize later.

**`mode=append`:** existing 3i response only — `load-more.js` ignores chip fields.

**Permission:** public (`__return_true`).

**Security:** sanitize filter slugs; extend `chairforce_sanitize_load_more_query_vars()`;
validate `mode` and `page`; whitelist query keys.

---

## Chunk 1 — ACF options + PHP helpers

**Files (expected):**

- `acf-json/group_theme_options.json` — two select fields
- `includes/product-filters-functions.php` (or `includes/woocommerce-filters-functions.php`)
- `lib/class-product-filters.php` — data assembly (or methods on `WooCommerce_Archive`)

**Deliverables:**

1. Theme option fields + getters + localize to `Chairforce_Public`.
2. `chairforce_get_archive_filter_groups( $query_context )` → list of groups:
   - `slug`, `label`, `taxonomy`, `type` (`price` | `attribute`), `terms[]`
     with `count`, `chosen`, swatch data for colour.
3. Wrap WC `Filterer::get_filtered_term_product_counts()` for each visible
   attribute in current archive + active filter state.
4. `chairforce_build_filter_url( $base_url, $changes )` — add/remove query
   params for chips and JS (mirror WC layered nav link algebra).
5. `chairforce_get_active_filter_chips()` — label + remove URL per chosen term
   + price range chip.

**Verify:** lounges-seating archive returns 7 groups; shop returns ~14; counts
change when `filter_colour` already active.

---

## Chunk 2 — Markup partials

**Files (expected):**

- `partials/product-filters-bar.php` or `parts/product-filters-bar.html` +
  `render.php` hook
- `partials/product-filters-panel.php` — card grid inner (reused in REST)
- `partials/product-filters-chips.php`

**Structure:**

```html
<div class="cf-product-filters" data-panel-desktop="vertical" data-panel-mobile="vertical">
  <div class="cf-product-filters__bar">…dynamic buttons…</div>
  <div class="cf-active-filters">…chips…</div>
  <div class="cf-filters-panel cf-filters-panel--vertical|horizontal" hidden>
    <div class="cf-filters-panel__grid">
      <div class="cf-filter-card" data-filter-card="colour">…</div>
      …
    </div>
  </div>
</div>
```

- Filter controls: `<button type="button">` / checkbox pattern — not `<a href>`
  full navigation.
- Price card: slider or inputs (UI decision §9 findings — default slider for
  live parity unless design picks buckets).

Wire into `templates/archive-product.html` above results count / sorting (Figma
order).

---

## Chunk 3 — Extend `load-more` REST

**Files:**

- `includes/rest-api/load-more.php` — extend route args + callback
- `includes/load-more-functions.php` — filter param parsing, product fragment renderers
- `context/plans/3i-load-more-plan.md` — add addendum for `mode` + product replace extras

**Deliverables:**

1. Add required **`mode`** param; relax `page` validation (`1` for replace, `≥2` for append).
2. Accept **`filter_*` / price** params; merge into query build (extend sanitizer).
3. **`mode=replace` + product:** render chips, optional panel, results count; return
   `queryVars` for Load More button.
4. **`mode=append`:** preserve shipped 3i behaviour exactly.
5. SSR parity: same query + markup as hard refresh for identical URL params.

**Must not** register `archive-products` or any second catalog route.

---

## Chunk 4 — Frontend JS

**Files:**

- `src/js/product-filters.js` — panel + filter AJAX (import from `src/public.js` on product archives)
- `src/js/shared/load-more.js` — add `mode=append` to existing requests (explicit)

**`product-filters.js`:**

1. **Panel open/close** — delegated clicks on bar buttons; Escape / outside
   click closes (mega-menu patterns).
2. **Orientation** — theme options + breakpoint →
   `cf-filters-panel--vertical` / `--horizontal`.
3. **Focus card** — bar button → open panel + `scrollIntoView` on
   `[data-filter-card="{slug}"]`.
4. **Filter toggle** — `pushState` → fetch **`load-more`** with
   **`mode=replace`**, **`page=1`**, params from current URL → **replace** grid
   innerHTML; swap chips/counts/panel from response →
   `dispatchContentUpdated({ source: 'filters' })`.
5. **Load More reset** — from response `queryVars` / `nextPage` / `hasMore`;
   remove button if `total <= perPage`.
6. **`popstate`** — browser Back/Forward after `pushState`: read URL →
   `mode=replace` fetch → sync grid/chips/Load More (no full reload).

**`load-more.js` (minimal change):**

- Send **`mode=append`** on every button click (existing append behaviour).
- Do not handle `mode=replace` or product chip fields.

Reuse `delegateDocument` from `src/js/shared/delegated-events.js`.

---

## Chunk 5 — Sass

**Files:**

- `src/sass/woocommerce/_product-filters.scss` (or `components/`)
- Import from `src/sass/woocommerce/_index.scss`

**Sections:**

- Filter bar (buttons, overflow scroll)
- Active chips row
- **Panel vertical** — off-canvas slide (reference menu drawer)
- **Panel horizontal** — absolute/fixed under bar, max-height + internal scroll
  (reference mega menu panel)
- Filter cards — grid, max-width, focused state
- Colour swatch list inside cards — align with existing swatch tokens

Use theme breakpoints from `_settings.scss` / menu `$menu-breakpoint` for
desktop vs mobile option switch.

---

## Chunk 6 — `WooCommerce_Archive` + Load More

**Files:**

- `lib/class-woocommerce-archive.php` — register hooks, enqueue filter assets
- `includes/load-more-functions.php` — ensure filtered SSR exports correct
  `data-query-vars` on Load More button

**Deliverables:**

1. Render filter bar on `is_shop() || is_product_taxonomy()`.
2. Conditional enqueue `product-filters.js` + CSS on product archives only.
3. **One** Product Collection + **one** Load More pagination block — no alternate
   “archive load more” template or editor control.
4. Confirm filtered URL hard refresh renders correct grid + chips + panel state.
5. Confirm `mode=append` after filter replace appends page 2 with filters preserved.

---

## Chunk 7 — Verification matrix

Test on **`/product-category/outdoor-furniture/seating-outdoor/lounges-seating/`**
(primary) and **`/shop/`** (max filters).

| Scenario | Pass |
|---|---|
| SSR: unfiltered archive | Grid + dynamic buttons + empty chips hidden |
| SSR: `?filter_colour=smokey-blue&filter_indoor-outdoor=outdoor` | Grid filtered; chips; URL bookmarkable |
| AJAX: toggle filter, panel open | No reload; URL updates; grid + chips refresh |
| AJAX: multi-filter without closing panel | Works |
| Bar click → focus correct card | vertical + horizontal each |
| Theme option: desktop horizontal | Panel under bar; grid does not shift |
| Theme option: desktop vertical | Side drawer |
| Mobile default vertical | Drawer usable with 7+ cards |
| Remove chip | Updates URL + grid via AJAX |
| Clear all | Base archive URL |
| Sort + filter combined | `orderby` preserved |
| Load More `mode=append` after filter replace | Page 2 matches filtered set |
| `mode=replace` + `mode=append` same endpoint regression | 3i append still works unfiltered |
| Quick view / swatches / wishlist on filtered grid | Delegated handlers OK |
| Empty filtered result | no-products + clear filters pattern |

Panel orientation QA: test **4 combos** when options toggled in admin (desktop
× mobile vertical/horizontal) — behaviour should differ only in presentation.

---

## Dependencies / blockers

| Dependency | Status |
|---|---|
| Product card HTML (`parts/product-card.html`) | ✅ Shipped (`2a455c9`) |
| Load More REST + card renderer | ✅ Shipped (`d7d7acc`) |
| `Product_Swatches` colour data | ✅ Shipped (3d) |
| Figma open-state screens | Optional — theme options reduce lock-in |
| Term `order` meta | Open — default WC/alpha order until PM decides |
| Price UI (slider vs buckets) | Open — default slider for v1 spike |

---

## Related docs

| Doc | Purpose |
|---|---|
| `context/notes/product-filters-findings.md` | Investigation + locked decisions |
| `context/PROGRESS.md` | Phase 3f tracker |
| `context/plans/3i-load-more-plan.md` | Base route — **extend in 3f**, do not duplicate |
| `context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md` | No WPGB for shop |
