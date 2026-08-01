# Product archive filters — investigation & architecture notes

Captured: 31 Jul 2026.

Findings from evaluating **Phase 3f** (shop/category product filters) for the
Chairforce block theme rebuild. Covers how filtering works on the **live
reference site** (`chairforce.test`), how that differs from the **Figma filter
bar** design, and what we need to decide before implementation.

**Phase tracker:** `context/PROGRESS.md` → **3f. Shop/category "filter by
color" sidebar widget** (⏳ not started).

**Prior research (mechanism + data model — still valid):**

- `context/existing-functionality/12-shop-archive-filters.md` — Woodmart
  `WOODMART_Widget_Layered_Nav` + WC native `filter_{attribute}` query args.
- `context/existing-functionality/02-data-model-and-storage.md` — term meta
  (`color`, `image`, `order`) and attribute taxonomy inventory.
- `context/existing-functionality/14-jet-content-types-and-showrooms.md` §I —
  Jet Smart Filters inventory (shop attribute filtering is **not** Jet).

**Design source:**

- `context/figma/components/Filters Bar - Desktop.png`
- `context/figma/components/Filters Bar Mobile.png`

---

## 1. Requirement (rebuild scope)

Replace Woodmart/Elementor shop-archive filtering with a **native Chairforce
implementation** that:

1. Filters products on shop + `product_cat` (+ other WC product taxonomies) using
   the **same underlying data** (`pa_*` attributes, price range, term meta).
2. Matches the **Figma filter bar** UX (horizontal filter buttons, active chips,
   sort + grid/list controls) — not the live site's left off-canvas drawer.
3. Updates the **URL** when filters change (bookmarkable, crawlable, back-button
   friendly) — same contract as today:
   `?filter_colour=smokey-blue&filter_indoor-outdoor=outdoor`.
4. Works with **already-shipped** archive infrastructure:
   - Product Collection grid + shared `parts/product-card.html`
   - Page-1 **Load More** (`context/plans/3i-load-more-plan.md`)
   - Delegated-events convention (`context/existing-functionality/17-load-more-and-event-delegation.md`)

**Explicitly out of scope for 3f v1 (separate passes):**

- `/gallery/` page filters (`gallery-category`) — Phase 6, file `15`.
- Blog Post Tag / Category filters — Phase 5.
- Contact page showroom Select filter — PM decision, file `14` §I.
- **`venues`** and **`sales-by-location`** as *filter widgets* on product
  archives — resolved elsewhere as **taxonomy archives** (not sidebar filters):
  - `venues` → `/venue/{slug}/` (mega-menu "Shop by Space")
  - `sales-by-location` → `/sales-by-location/{slug}/`

---

## 2. Live site audit (`chairforce.test`)

Audited 31 Jul 2026 via rendered HTML on reference URLs.

### 2.1 Archive layout today (not Figma)

| Region | Live behaviour |
|---|---|
| **Filter access** | Single **off-canvas left column** opened by a burger-style button (`elementor-widget-wd_builder_off_canvas_column_btn`). All filters live inside the drawer — **not** as horizontal buttons above the grid. |
| **Toolbar (`wd-shop-tools`)** | Result count, order-by, products-per-page, grid/list view toggle. **No per-attribute filter buttons** in the toolbar row. |
| **Active filters** | When query params are set, a **`wd_shop_archive_active_filters`** Elementor widget renders chips above the grid (`wd-active-filters` + WC `widget_layered_nav_filters`). Includes **Clear all** link back to the unfiltered archive URL. |
| **Product grid** | Elementor `wd_archive_products` — classic Woodmart loop, not WC blocks. |
| **Pagination** | Woodmart AJAX shop (`ajax_shop: 1`) — filter/sort/pagination links intercepted for PJAX partial refresh; URL still updates (`load_more_button_page_url: yes`). |

**Important:** Figma's horizontal filter buttons are a **new UX**, not a port of
the live toolbar. The closest live analogue is the **active-filter chips row**
(which Figma also shows).

### 2.2 Filtering mechanism (confirmed unchanged)

All filtering is **native WooCommerce layered nav** — no Jet Smart Filters on
product archives (file `12`).

| Mechanism | Detail |
|---|---|
| **Query params** | `filter_{attribute_slug}` where slug matches the WC attribute name (hyphenated), e.g. `filter_colour`, `filter_indoor-outdoor`, `filter_stackable`. |
| **Multi-value (same attribute)** | Comma-separated slugs: `filter_colour=black,white`. |
| **Multi-attribute AND** | Separate params joined with `&`: `filter_colour=smokey-blue&filter_indoor-outdoor=outdoor`. Verified on lounges archive — second filter link preserves existing params. |
| **Price** | `min_price` + `max_price` (+ Woodmart price slider widget). |
| **Sort** | `orderby` / `order` (WC catalog sorting). |
| **Server parsing** | `WC_Query::get_layered_nav_chosen_attributes()` → `tax_query` on main product query. **No custom filter SQL.** |
| **Term counts** | WC `Filterer` / layered nav counts — **transient cache disabled** site-wide (child theme, file `07` §5). Counts computed live per request. |

Example filtered URL (user-provided, verified live):

```
/product-category/outdoor-furniture/seating-outdoor/lounges-seating/
  ?filter_colour=smokey-blue&filter_indoor-outdoor=outdoor
```

Active chips render as: **Smokey Blue**, **Outdoor**, plus clear → base archive URL.

### 2.3 Filters differ per archive (dynamic, not manually curated)

The **set of visible filter groups** changes by category because WooCommerce
only outputs attribute terms that **have products in the current archive
context**. This is not a separate per-category widget configuration in wp-admin
— it's standard layered-nav "hide empty" behaviour.

**Widget titles observed (unique per URL):**

| Archive URL | Filter groups shown |
|---|---|
| `/shop/` | Price, Material, Colour, Indoor/Outdoor, Arms, Stackable, Assembly, Folding, Height, Shape, Size, Seat, **Type**, **Feet / Castors** (14 groups) |
| `/product-category/chairs/cafe-chairs/` | Price, Material, Colour, Indoor/Outdoor, Arms, Stackable, Assembly, Folding (8) |
| `/product-category/outdoor-furniture/residential-outdoor/` | Price, Material, Colour, Indoor/Outdoor, Arms, Stackable, Assembly, Folding, Height, Shape, Size, Seat (12) |
| `/product-category/outdoor-furniture/seating-outdoor/lounges-seating/` | Price, Material, Colour, Indoor/Outdoor, Arms, Stackable, Assembly (7) |
| `/sales-by-location/act-sale-products/` | Same broad set as shop (~13 groups) — this is a **taxonomy archive**, not a "location filter widget" on another page |

**Lounges archive — term counts per widget (illustrates sparsity):**

| Widget | Terms / links in drawer |
|---|---|
| Filter by price | Slider (no slug links) |
| Material | 1 term |
| Colour | 5 terms (inline swatches, `wd-layout-inline`) |
| Indoor / Outdoor | 1 term |
| Arms | 1 term |
| Stackable | 1 term |
| Assembly | 2 terms |

**Implication for Figma:** Desktop mock shows **6 fixed buttons** (Price,
Material, Color, Arms, Stackable, Assembly). Live archives may show **fewer or
different** buttons depending on category (e.g. Height/Shape/Size on table
categories, no Folding on lounges). The rebuild must handle **dynamic button
lists**, not a hard-coded sextet.

### 2.4 Display modes per attribute (live Woodmart widget config)

Same attribute can render differently in the drawer:

| Attribute (example) | Layout | Swatch style | Labels |
|---|---|---|---|
| **Colour** | Inline swatches | text-style 3 / bg-style 3 (round) | Off |
| **Material, Indoor/Outdoor, Stackable, Assembly** | Vertical list | text-style 1 / bg-style 1 or 4 | On |
| **Price** | Slider widget | n/a | n/a |

Chairforce already has swatch rendering for **`pa_colour`** via
`Chairforce\Product_Swatches` (grid + single product). Filter UI should reuse
the same read path (`color` → `image` → text fallback) for colour filters.

Other attributes use **text list** (and occasionally bg-style-4 pills for
Arms). Material texture icons (`cfvsw_image` on 2 terms) were **not** migrated
— open client question in `context/PROGRESS.md`.

### 2.5 Attributes inventory (relevant to filters)

From file `02` + 3c audit — **15** global WC attribute taxonomies:

`pa_colour`, `pa_size`, `pa_height`, `pa_material`, `pa_features`,
`pa_shape`, `pa_mounting`, `pa_arms`, `pa_stackable`, `pa_assembly`,
`pa_indoor-outdoor`, `pa_base-type`, `pa_folding`, `pa_seat`, `pa_backrest`.

Live shop also shows labels **Type** and **Feet / Castors** — map to attribute
admin labels (likely `pa_features` / `pa_base-type` or similar; confirm label →
slug mapping during implementation).

**Term display order:** many taxonomies carry Woodmart `order` term meta (real
data, not swatch-related). Preserving frontend term order in filter lists is an
**open decision** (`context/PROGRESS.md` → open issues).

---

## 3. Figma design breakdown

Source: `context/figma/components/Filters Bar - Desktop.png` and
`Filters Bar Mobile.png`. **No Figma spec for filter panel interaction** —
only the collapsed bar states.

### 3.1 Desktop bar

**Row 1 — left:** horizontal **filter buttons**, each with icon + label +
chevron:

- Price ($), Material (cube), Color (palette), Arms (chair arms), Stackable
  (stack), Assembly (tool)

**Row 1 — right:**

- **Sort** dropdown — mock shows "Sort: Best Sellers"
- **View toggle** — grid (selected) / list icons

**Row 2 — active filters (conditional):**

- Label: "Active Filters:"
- **Chips** with value + × remove — mock: `Upholstered`, `$100 - $250`

### 3.2 Mobile bar

- **Row 1:** subset of filter buttons (Price, Material, Color) — implies
  horizontal scroll, overflow, or a single "Filters" entry point for the rest.
- **Row 2:** Sort dropdown + grid/list toggle (same as desktop, stacked).

### 3.3 Design ↔ live gaps

| Figma | Live site today |
|---|---|
| Horizontal filter buttons | Single drawer button → all filters in sidebar |
| Chips below bar | Same concept (`wd-active-filters`) — **keep** |
| Sort + view in bar | Same toolbar elements — **keep** (new theme already has catalog sorting block) |
| Fixed 6 filters | Dynamic N filters per archive |
| Icons per filter type | Woodmart drawer uses text headings only — **new design asset work** |
| Panel on button click | **Undefined in Figma** — mitigated via theme options §5.3 |

---

## 4. New theme current state (`chairforce-2026.test`)

`templates/archive-product.html` today:

- Breadcrumbs, title, term description
- Results count + **`woocommerce/catalog-sorting`** only
- **`parts/product-collection.html`** — Product Collection + Load More
- **No filter UI** — `lib/class-woocommerce-archive.php` is a stub (quick-view
  script enqueue only)

Load More exports `$wp_query->query_vars` (including WC-parsed `tax_query`) to
the REST replay path. **Filter params on initial page load should flow into
Load More batches** once `tax_query` is present in exported vars — but this is
**unverified** with real filter URLs and called out in `3i-load-more-plan.md`
as future work ("Jet Smart Filters / AJAX filter integration" listed as
follow-up, meaning **filtered archives + Load More** need an explicit test pass
when filters ship).

---

## 5. UX decision — **locked** (31 Jul 2026)

### 5.1 Filter buttons — dynamic

The horizontal filter button row is **not** a fixed Figma sextet. Buttons are
built from the same **contextual attribute list** as today (§2.3): only
attributes/terms with products in the current archive. Count and labels change
per category (shop ~14, lounges ~7, etc.).

Overflow when buttons exceed the bar width: horizontal scroll and/or wrapped
row — detail in Figma pass; mechanism is dynamic either way.

### 5.2 Panel content — card grid + focus (locked)

Clicking a filter button opens **one shared panel** showing **all available
filters at once**, as a **responsive card grid**:

- Each filter group = one **card** (Price, Material, Colour, …).
- Cards have a **max width** (CSS grid `minmax()` / max-width) so lists and
  swatch rows do not stretch edge-to-edge.
- The clicked bar button **focuses/scrolls** to that card (`data-filter-card`,
  `scrollIntoView`, optional highlight) — user can edit any card without
  closing the panel.
- Panel stays open while applying **multiple** filters; **chips + grid update
  live** after each change (§6).

The bar button is a **shortcut into the shared filter deck**, not a
single-attribute mini-panel.

### 5.3 Panel orientation — **vertical** | **horizontal** (theme options)

Two **overlay** presentations of the same panel markup — same idea as the
**header mega menu** (floating panel, grid does not move). Not an in-flow
accordion that pushes the product grid down.

| Mode | Behaviour |
|---|---|
| **Vertical** | Side off-canvas drawer (slides from edge). Default **desktop**. |
| **Horizontal** | Panel drops **under the filter bar**, floating/sticky to the bar — mega-menu-style dropdown deck. Does **not** push the grid. |

**Theme options** (Chairforce → Theme Options → WooCommerce tab — same pattern
as wishlist):

| Field | Values | Default |
|---|---|---|
| `filters_panel_desktop` | `vertical` \| `horizontal` | **`vertical`** |
| `filters_panel_mobile` | `vertical` \| `horizontal` | **`vertical`** (recommended — full-height drawer fits many cards + touch) |

One DOM structure + modifier classes (`cf-filters-panel--vertical` /
`cf-filters-panel--horizontal`); mostly Sass positioning differences, shared JS
for open/close, focus, AJAX. Lets PM UAT both orientations without a rebuild
while Figma panel states remain undefined.

Expose options to JS via `Chairforce_Public` (e.g. `filtersPanelDesktop`,
`filtersPanelMobile`) and root `data-*` or body classes for SSR.

**Implementation reference:** header mega menu panel positioning
(`src/sass/menu/`, `src/js/site-header.js`, `17-menu-system.mdc`).

### 5.4 Rejected for this rebuild

- **Full page reload on every filter click** — rejected. Multi-filter workflows
  (open panel → colour → material → arms) would force reopen-after-reload and
  is unacceptable UX (see §6).
- **Permanent left sidebar** (live Woodmart layout) — replaced by Figma bar +
  on-demand panel.

---

## 6. Filter application — **AJAX required** (locked)

| Approach | Status |
|---|---|
| **Full page navigation** | ❌ Rejected — painful for multi-filter sessions |
| **Partial AJAX / REST fragments** | ✅ **Required v1** — same family as Load More |
| **Woodmart-style PJAX** | Reference only — live site uses this today (`ajax_shop: 1`) |

### 6.1 Target flow (each filter interaction)

1. User toggles a term / price range **inside the open panel** (panel stays open).
2. JS updates **`history.pushState`** / `replaceState` with standard WC query
   params (`filter_colour=…`, `min_price` / `max_price`, existing params
   preserved).
3. **REST request** to **`GET chairforce/v1/load-more`** with explicit
   **`mode=replace`**, **`page=1`**, and filter/sort params from the URL (extend
   the shipped 3i route — **no second catalog endpoint**). Response includes
   product-only extras when applicable:
   - Product grid (`product-collection` / page-1 batch — **replace**, not append)
   - Active filter chips row
   - Optional: refreshed filter panel (term counts, hide-zero terms)
   - Results count text
   - Updated `queryVars` for the Load More button
4. DOM swap + **`chairforce:content-updated`** so swatches, quick view, wishlist
   delegated handlers stay valid (file `17`).
5. **No full document reload.**

Direct navigation / refresh on a filtered URL must still work (server renders
the same state from query string — bookmarkable, SEO-safe initial load).

### 6.2 Woodmart parity note (updated after PJAX investigation)

The reference site uses **Woodmart PJAX**, not REST micro-fragments. Filter links
(in `woodmart_settings.ajax_links`) trigger a GET to the filter URL with
`_pjax=.wd-page-content`; the server re-renders the **entire shop content shell**
(filters drawer, widgets with updated `Filterer` counts, active chips, grid page 1,
load-more button) and JS replaces `.wd-page-content` innerHTML + `pushState`.

**Full mechanism:** `context/existing-functionality/12A-woodmart-ajax-shop-filtering.md`.

**Rebuild implication:** match the **one-shell partial reload** contract (URL params
+ single server render + optional drawer-state restore). Load More can stay a
separate append channel (Woodmart uses `woo_ajax` JSON for that). Avoid maintaining
independent REST paths that build grid vs panel with separate query assembly — that
pattern already caused sort/pagination drift in the Phase 3f prototype.

---

## 6A. Native WooCommerce vs WP Grid Builder — **do we need a facet plugin?**

**Short answer: No.** Native WooCommerce layered nav + its **`Filterer`** class is
enough for filtering logic and **faceted term counts**. A companion plugin is **not
required** for correctness or for reasonable performance on this catalog.

### What WooCommerce already provides (same engine as live site)

| Capability | WooCommerce native | Notes |
|---|---|---|
| Apply attribute filters | `filter_{attribute_slug}` → `WC_Query` / `tax_query` | Live site uses this today (file `12`) |
| Price filter | `min_price`, `max_price` | Native price filter widget |
| Chosen filters / chips | `WC_Query::get_layered_nav_chosen_attributes()` | Powers clear/remove chip URLs |
| **Faceted term counts** | `Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer` | Counts **given current filters + archive context** — true facet behaviour |
| Performance index | `wp_wc_product_attributes_lookup` table | Denormalized lookup; WC ≥ 5.5 path when `woocommerce_attribute_lookup_enabled` is on |
| OR within attribute | Comma-separated slugs | Live parity |
| AND across attributes | Multiple `filter_*` params | Verified on lounges URL |

The stock **Filter Products by Attribute** widget is “dumb UI” on top of this
engine. Woodmart's widget is styled UI on the same engine. Our Figma bar + card
grid is **also** styled UI on the same engine — the gap is **presentation +
AJAX transport**, not missing facet logic.

### What the live child theme disabled (and rebuild should revisit)

File `07` §5 disables `wc_layered_nav_counts_*` transients and
`woocommerce_layered_nav_count_maybe_cache`. That means the **reference site
already runs live facet COUNT queries** per request — and it works. For AJAX
filters that re-fetch counts on every toggle, consider **re-enabling WC's count
cache** (scoped transients) or a thin theme cache — optimization, not a reason
to adopt WPGB.

**Verify on staging:** WooCommerce → Status → Tools → product attributes lookup
table regenerated and `woocommerce_attribute_lookup_enabled` = yes (standard for
attribute filtering at scale).

### What WP Grid Builder would add (and why we're not defaulting to it)

WP Grid Builder is mentioned in
`context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md` as a
**possible** faceted-filter tool for Gutenberg — **not** because WC lacks facets.

| WPGB | Chairforce 3f |
|---|---|
| Pre-built filter blocks + AJAX | Would still need **heavy customization** to match Figma bar + card grid + focus |
| Facet indexing / UI | WC `Filterer` + lookup table already indexes attributes for counts |
| Grid rendering | We already use **WooCommerce Product Collection** + `parts/product-card.html`, not WPGB grids on shop |
| Config surface | DB/GUI-configured grids — conflicts with “theme-owned, git-diffable” rebuild |
| Live reference | Shop filters on `chairforce.test` are **Woodmart + WC**, not WPGB |

**When WPGB *might* be reconsidered** (decision doc revisit triggers): if a
*future* page (e.g. `/gallery/` Phase 6) needs faceted AJAX and custom REST
proves dramatically more effort than expected — evaluate WPGB **there**, not as
a shop-archive dependency. Even then, prefer scoping down before adding a
plugin.

**Jet Smart Filters:** ruled out for shop archives (file `12`); Elementor-coupled;
same “don't adopt for 3f” conclusion.

### Recommendation for 3f implementation stack

```
┌─────────────────────────────────────────────────────────┐
│ Figma UI — bar, card grid panel, chips (theme Sass/JS)  │
├─────────────────────────────────────────────────────────┤
│ Extend `load-more` REST — mode=replace|append (one route)│
├─────────────────────────────────────────────────────────┤
│ WC_Query + Filterer + lookup table                      │  ← facet counts
├─────────────────────────────────────────────────────────┤
│ Standard filter_* / min_price / max_price URL contract  │
└─────────────────────────────────────────────────────────┘
```

No WP Grid Builder layer required for shop/category archives.

---

## 7. Architecture sketch (implementation-facing)

**Execution plan:** `context/plans/3f-product-filters-plan.md`. Locked inputs: §5
card grid + focus, §5.3 vertical/horizontal options, §6 AJAX, §6A native WC only,
**extend `load-more` REST with explicit `mode`** (no second route).

### 7.1 PHP / data layer

| Piece | Approach |
|---|---|
| **Chosen filters** | `WC_Query::get_layered_nav_chosen_attributes()` + price query vars |
| **Available filters** | Dynamic list: attributes with terms that have **non-zero counts** in current archive + active filter context — via `Filterer::get_filtered_term_product_counts()` (same as layered nav widget) |
| **Term lists** | Per card; respect `order` term meta when decision locked |
| **Swatch HTML** | Reuse `Product_Swatches` for `pa_colour`; text/list for others |
| **Chip labels** | Term name; formatted range for price |
| **Clear / remove chip** | Rebuild query string minus removed param — WC widget algebra |
| **Facet counts on AJAX** | Re-run `Filterer` server-side when rendering panel fragment so counts reflect cross-filter state |

### 7.2 UI layer (block theme)

`parts/product-filters-bar.html` (or JSX block) in `archive-product.html`:

- **Dynamic filter buttons** (one per visible attribute + Price)
- **Panel** — orientation from theme options (§5.3); single markup:
  - **Card grid** of all filter groups, max-width cards
  - `data-filter-card="{slug}"` for focus target from bar click
- **Active chips** row (live-updated via AJAX)
- Sort + view toggle per Figma

Filter controls use **`button` / `checkbox` + JS**, not plain `<a href>` full
navigation — but every state change maps to the **same URL query string** WC
already understands (for pushState + server fallback).

### 7.3 REST / AJAX — extend `GET chairforce/v1/load-more` (one route)

**Do not add `archive-products`.** Extend the shipped Load More endpoint and
shared helpers in `includes/load-more-functions.php`.

| Concern | Approach |
|---|---|
| **Client intent** | Required **`mode`**: `replace` \| `append` — JS sends; server validates (`replace` + `page=1`, `append` + `page≥2`) |
| **Filter refresh** | `product-filters.js` → `mode=replace`, `page=1` — **replace** grid HTML |
| **Load More click** | `load-more.js` → `mode=append`, `page≥2` — **append** (existing 3i) |
| **Product extras** | `chipsHtml`, `panelHtml`, `resultsCountHtml`, `queryVars` when `post_type=product` and `mode=replace` |
| **Blog / posts** | Same route; append-only; no chip fields |
| **UI** | One Load More button in template — filter refresh is not a second control |

Request carries filter/sort params from the URL (and/or extended `query_vars`
JSON). Server builds main-query-equivalent `WP_Query` via WC — **do not invent
custom tax SQL**.

After replace: reset Load More button (`data-next-page`, `data-query-vars`);
dispatch `chairforce:content-updated` with `{ source: 'filters' }`.

**State:** URL query string is source of truth for active filters.

### 7.4 URL contract (preserve)

Continue emitting standard WC filter URLs so:

- Existing bookmarked filtered URLs keep working
- SEO / canonical behaviour stays WC-native
- No migration of filter state

Parameter names confirmed live use **`filter_colour`** (British slug), not
`filter_pa_colour` — match WC attribute slug exactly.

---

## 8. Integration checklist (must not break)

When filters ship, verify on filtered archive URLs:

| Integration | Risk |
|---|---|
| **Load More** | Exported `query_vars` / `tax_query` must include active filters; button hidden or reset when filtered total ≤ page 1 batch |
| **Catalog sorting** | `orderby` survives filter param changes (already fixed once — `fe21084`) |
| **Grid swatches** | Delegated handlers on appended cards after filter+load-more flows |
| **Quick View** | Still works on cards in filtered grid |
| **Wishlist** | Unaffected |
| **Results count** | WC block reflects filtered total |
| **Empty state** | `product-collection-no-results` + clear-filters pattern already in template part |

---

## 9. Open questions (resolve before or during 3f plan)

| # | Question | Owner |
|---|---|---|
| 1 | ~~Filter panel content~~ | ✅ Locked — §5.2 card grid + focus |
| 2 | ~~Panel orientation~~ | ✅ Locked — §5.3 vertical \| horizontal + theme options |
| 3 | ~~Dynamic buttons~~ | ✅ Locked — §5.1 |
| 4 | ~~AJAX vs reload~~ | ✅ Locked — AJAX only, §6 |
| 5 | ~~WPGB / facet plugin~~ | ✅ Locked — native WC `Filterer`, §6A |
| 6 | ~~REST route~~ | ✅ Locked — extend `load-more` with `mode`; no second endpoint |
| 7 | ~~Sort changes~~ | ✅ Locked — `mode=replace` like filters (live parity) |
| 8 | ~~Browser Back/Forward (`popstate`)~~ | ✅ Locked — `mode=replace` sync from URL |
| 9 | ~~Empty filter groups~~ | ✅ Locked — hide filter bar (edge case) |
| 10 | **Button overflow:** horizontal scroll vs wrap when 14 filters on shop? | Design |
| 11 | **Filter button icons:** per-attribute Lucide map vs generic? | Design |
| 12 | **Grid / list view toggle:** 3f or defer? | ✅ **3k** — `context/plans/3k-grid-list-view-toggle-plan.md` |
| 13 | **Multi-select within attribute:** keep WC comma-separated OR? | PM (default: yes) |
| 14 | **Term order:** respect Woodmart `order` meta? | PM / dev |
| 15 | **Price UI:** slider (live) vs preset buckets (Figma chip)? | Design |
| 16 | **Panel refresh on AJAX:** re-render full card grid vs counts-only patch? | Dev |
| 17 | **Re-enable WC layered nav count transients** for AJAX perf? | Dev |
| 18 | **Filtered archive + Load More** QA matrix | Dev |
| 19 | **`pa_material` texture icons** (`cfvsw_image`) | Client |

---

## 10. Next steps

See **`context/plans/3f-product-filters-plan.md`** for chunked implementation.

---

## 11. Related docs

| Doc | Purpose |
|---|---|
| `context/PROGRESS.md` | Phase 3f status + open decisions |
| `context/existing-functionality/12-shop-archive-filters.md` | WC layered nav + Woodmart widget mechanics |
| `context/existing-functionality/02-data-model-and-storage.md` | Term meta + attribute slugs |
| `context/existing-functionality/07-child-theme-overrides.md` §5 | Layered nav count cache disabled |
| `context/existing-functionality/17-load-more-and-event-delegation.md` | Post-AJAX init rules |
| `context/notes/load-more-findings.md` | Load More architecture (filter replay TBD) |
| `context/plans/3f-product-filters-plan.md` | Phase 3f execution plan |
| `context/plans/3i-load-more-plan.md` | Shipped Load More — shares REST partial-HTML pattern |
| `context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md` | WPGB vs custom — custom WC for 3f |
| `context/figma/components/Filters Bar*.png` | Target UI |
| `.cursor/skills/chairforce-woocommerce/` | WC implementation conventions |
