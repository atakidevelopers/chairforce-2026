# Grid / list view switcher — investigation notes

Captured: 1 Aug 2026.

Findings for adding a **grid/list toggle** to the shop archive filter bar (Figma)
without breaking WooCommerce Blocks patterns, Product Collection queries, filter
AJAX, or Load More.

**Related:**

- `context/notes/product-filters-findings.md` — Figma bar (sort + view toggle), §3.1
- `context/plans/3f-product-filters-plan.md` — deferred grid/list in 3f v1
- `context/notes/load-more-findings.md` — append contract on `.wc-block-product-template`
- `context/existing-functionality/12A-woodmart-ajax-shop-filtering.md` — legacy PJAX
- `parts/product-collection.html` — canonical Product Collection template part
- `parts/product-card.html` — shared card markup (grid + Load More)

---

## 1. Executive summary

**Recommendation: client-side view toggle (CSS + `localStorage`), not a URL query
param and not a server-side layout swap.**

WooCommerce Blocks exposes **grid vs stack (list) layout on the Product Collection
block in the editor**, but ships **no storefront switcher block** and no documented
frontend API to flip layout at runtime. Woodmart’s `?shop_view=list|grid` is a
**theme feature**, not WooCommerce core — it triggers a PJAX reload and swaps PHP
loop templates.

For Chairforce, the lowest-risk path that preserves the WC block stack:

1. Keep **`woocommerce/product-collection`** + **`parts/product-card.html`** unchanged.
2. Add toggle UI beside **`woocommerce/catalog-sorting`** in the filters band.
3. Toggle a theme class (e.g. `cf-products-view-list`) on the collection wrapper.
4. Style list mode in Sass (single column + horizontal card row).
5. Persist preference in **`localStorage`** (optionally mirror to a cookie for SSR
   anti-flash later).
6. Re-apply the class after **filter shell swap** and **Load More append** via
   `chairforce:content-updated` — no REST or query changes.

This avoids polluting filter URLs, keeps one card renderer for Load More, and does
not fight WC Interactivity directives on `<li class="wc-block-product">`.

---

## 2. Current Chairforce state

| Piece | Today |
|---|---|
| **Collection** | `parts/product-collection.html` — `woocommerce/product-collection` with `displayLayout: { type: "flex", columns: 5 }` (WC maps legacy `flex` → grid) |
| **Cards** | `parts/product-card.html` inside `woocommerce/product-template` — image, swatches, title, price, add-to-cart |
| **Toolbar** | `parts/shop-archive-shell.html` — filter chrome + **`woocommerce/catalog-sorting`**; **no view toggle** |
| **Filter AJAX** | Replaces `.cf-shop-archive-shell` HTML; `product-filters.js` + `load-more` REST `mode=replace` |
| **Load More** | Appends `<li class="wc-block-product">` into `.wc-block-product-template`; cards rendered via `chairforce_render_product_template_item()` reading **`product-card.html` directly** |
| **List layout Sass** | None — cards are grid-first (centered title/price/button) |

Figma shows grid (selected) + list icons to the **right of Sort** — same row as
the filter bar on desktop and stacked on mobile (`product-filters-findings.md` §3).

---

## 3. What WooCommerce provides (native)

### Product Collection block layouts

WC Product Collection supports editor layout modes (see [WC Product Collection docs](https://woocommerce.com/document/woocommerce-store-editing/customizing-shop-page-catalog/product-collection-block/)):

| `displayLayout.type` | UI label | Storefront behaviour |
|---|---|---|
| `flex` / `grid` | Grid | Multi-column responsive grid |
| `stack` | Stack (list) | Single-column vertical list |
| `carousel` | Carousel | Horizontal scroll (not relevant for main shop) |

Layout is a **block attribute** set in the Site Editor. There is **no core
`woocommerce/catalog-view`** block analogous to `woocommerce/catalog-sorting`.

Implications:

- Changing layout “the WooCommerce way” in the editor means picking grid **or**
  stack in `product-collection.html` — not both at once.
- Runtime toggling via block attributes would require either re-rendering the
  collection server-side or manipulating WC-emitted layout classes client-side
  (same net effect as a theme class, but coupled to WC internals).

### What WooCommerce does *not* provide

- No standard query var for list view (unlike `orderby`, `filter_*`).
- No list-specific product card template in blocks — **one `product-template`**
  inner blocks layout serves both grid and stack; stack mainly changes the outer
  column structure.
- Third-party plugins (e.g. “Grid/List View for WooCommerce”) target **classic**
  `content-product.php` loops and are a poor fit for FSE Product Collection
  (`product-filters-findings.md` already rejected facet plugins for similar reasons).

---

## 4. Legacy Woodmart behaviour (reference site)

Woodmart implements grid/list **outside** WooCommerce core:

| Aspect | Woodmart |
|---|---|
| **Toggle UI** | `.wd-products-shop-view` links in `wd-shop-tools` toolbar |
| **State** | `?shop_view=list\|grid` query param; fallback **`shop_view` cookie**; optional `?per_row=N` for grid density |
| **Rendering** | Same `content-product.php` wrapper; **`wc_get_template_part( 'content', 'product-' . $hover )`** → `content-product-list.php` when view is list |
| **List content** | Richer than grid: excerpt, SKU, categories, rating, swatches, add-to-cart in a horizontal row |
| **Transport** | View links in **`woodmart_settings.ajax_links`** → **PJAX** replaces `.wd-page-content` (full shop shell reload) |

Source: `~/Projects/wp/chairforce/wp-content/themes/woodmart/` — `woodmart_get_shop_view()`,
`woodmart_products_view_select()`, `woocommerce/content-product-list.php`.

**Do not port `?shop_view=` verbatim.** It would:

- Add a non-WC param to every toggle click (bookmark noise; filter URL assembly must preserve it).
- Force shell reload for a **pure presentation** change (filters already use shell reload for catalog changes — view toggle should feel instant).
- Require a second card template path in PHP Load More unless list is CSS-only on the same markup.

---

## 5. Integration constraints (must not break)

### 5.1 Product Collection + Interactivity API

Load More and SSR cards emit:

```html
<li class="wc-block-product …" data-wp-interactive="woocommerce/product-collection" …>
```

`includes/load-more-functions.php` loads experimental interactivity context per
product. **Do not replace the collection block or re-parse cards on view toggle.**
A class on an ancestor (`.wp-block-woocommerce-product-collection` or
`.cf-shop-archive-main`) is safe.

### 5.2 Filter AJAX shell replace

`product-filters.js` swaps `.cf-shop-archive-shell` inner HTML on filter/sort/clear.
Server response is built from the same template parts; it will **always SSR grid
layout** from `product-collection.html`.

→ View preference must be **re-applied client-side** after every shell swap and
`popstate` navigation. Hook: existing `dispatchContentUpdated({ source: 'filters' })`.

### 5.3 Load More append

Append path only returns `<li>` fragments; it does not re-render the collection
wrapper. Parent class from step 1 applies to appended rows automatically — **no
PHP changes** if list mode is CSS on shared markup.

### 5.4 Sort block

`woocommerce/catalog-sorting` already triggers shell reload on change (same as
filters). View toggle should **not** trigger shell reload — orthogonal to catalog
query.

### 5.5 Related / upsells collections

`parts/product-upsells.html` and `parts/product-related.html` reuse
`product-card.html` but are **not** on the shop archive. Scope toggle to
**`.cf-shop-archive-shell`** only — do not add `cf-products-view-list` globally.

---

## 6. Approach options

### A. Client-side class toggle + CSS (recommended)

| Pros | Cons |
|---|---|
| No REST/query changes | List layout limited to what **one** `product-card.html` can express via CSS |
| Works with Load More + filter AJAX immediately | Does not change WC `displayLayout` attribute (editor preview stays grid-only) |
| Instant toggle (no network) | Rich list extras (excerpt, rating) need new blocks in card or a follow-up template |
| Same pattern as many block themes | `localStorage` not shared across devices |

**Mechanism:**

```text
User clicks list
  → localStorage.setItem('cf_products_view', 'list')
  → .cf-shop-archive-main.classList.add('cf-products-view-list')
  → Sass: .wc-block-product-template { grid-template-columns: 1fr }
          .wc-block-product { display: grid; grid-template-columns: … }
```

Optional: inline script in `wp_footer` on shop archives reads cookie/localStorage
and sets class before paint to reduce flash.

### B. URL query param + server-side class (Woodmart-style)

 e.g. `?cf_view=list` preserved across filter URLs.

| Pros | Cons |
|---|---|
| SSR can render correct class (no flash) | Must merge param in **every** `buildCatalogUrl()` / filter link / sort handler |
| Bookmarkable view mode | Shell reload on toggle if tied to navigation; instant toggle lost |
| | Not a WC-native param — SEO/crawl noise (rel=nofollow does not stop indexing) |

**Verdict:** Only if PM requires shareable list URLs. Otherwise avoid.

### C. Server render with `displayLayout.type: stack`

Toggle triggers `mode=replace` with `view=stack` → PHP re-renders
`product-collection.html` with different block attrs.

| Pros | Cons |
|---|---|
| Aligns with WC block semantics | **Heavy**: full shell reload for presentation |
| | Must thread `view` through REST + template rendering |
| | Load More `chairforce_get_load_more_block_context()` hardcodes `flex`/columns — must stay in sync |
| | Stack alone may not match Figma horizontal list card |

**Verdict:** Overkill for shop toolbar toggle.

### D. Second template part (`product-card-list.html`)

Conditional template in `product-template` or `render_block` filter.

| Pros | Cons |
|---|---|
| True list markup (excerpt, meta row) | **Two** Load More render paths |
| | Filter shell must pick correct template |
| | Harder to maintain parity with grid card features (swatches, quick view) |

**Verdict:** Phase 2 if Figma list comp needs content grid cannot show.

### E. JSX block switcher controlling WC Interactivity store

Hypothetical integration with `@woocommerce/stores/woocommerce/product-collection`.

| Pros | Cons |
|---|---|
| “Official” block extension | Experimental APIs; WC version lock-in |
| | No public docs for frontend layout switching at time of writing |
| | High breakage risk per `wc_interactivity_api_load_product` acknowledgement string |

**Verdict:** Do not pursue until WC documents a stable store action.

---

## 7. Recommended implementation plan

### 7.1 UI placement

**Block:** `<!-- wp:chairforce/product-view-switcher /-->` in
`shop-archive-shell.html` (sibling of `woocommerce/catalog-sorting`). Separate
JSX block — rearrangeable / reusable in Site Editor; see
`context/plans/3k-grid-list-view-toggle-plan.md` chunk 1.

Extend filters band grid in `_shop-archive-layout.scss` (grid area `view`).

### 7.2 JS module

New `src/js/product-view-switcher.js` (import from `public.js` on shop archives):

- Constants: `STORAGE_KEY = 'cf_products_view'`, `ROOT = '.cf-shop-archive-shell'`
- `applyView(view)` — toggle class on `.cf-shop-archive-main` or
  `.wp-block-woocommerce-product-collection`
- `init()` — read storage, apply, bind delegated clicks on `[data-cf-products-view]`
- Listen `document` for `chairforce:content-updated` → re-`applyView()`

**Do not** call filter REST or mutate URL.

### 7.3 Sass

New `src/sass/woocommerce/_product-view-switcher.scss`:

- Toolbar icon button active state (match sort/grid Figma)
- `.cf-products-view-list` overrides:
  - `.wc-block-product-template` → single column
  - `.wc-block-product` → CSS grid: image column + content column
  - Reset centered text alignment on title/price/button
  - Optional: hide swatches row or move below title in list

Reference Woodmart list proportions only — do not copy `content-product-list.php`
hook structure.

### 7.4 PHP / archive class

Minimal: enqueue script on product filter archives (`WooCommerce_Archive` or
`chairforce_is_product_filter_archive()`).

Optional helper:

```php
function chairforce_get_products_view_default(): string {
    return 'grid';
}
```

No theme option required for v1 unless PM wants default list on mobile.

### 7.5 Load More + filters — no backend changes

Confirm in QA:

| Scenario | Expected |
|---|---|
| List → Load More | Appended cards inherit list layout |
| List → filter toggle | Shell replaces; JS restores list from storage |
| List → sort change | Shell replaces; JS restores list |
| Grid → hard refresh | SSR grid; JS applies stored list if set |
| Related products block | Unaffected (outside shell) |

---

## 8. Figma / product card gaps

Current `product-card.html` is **compact grid card**. Woodmart list showed
**excerpt, rating, SKU, categories** — not in our card today.

| Figma / live need | v1 (CSS toggle) | v2 (if required) |
|---|---|---|
| Horizontal image + details | ✅ CSS grid on `.wc-block-product` | — |
| Short description in list | ❌ | Add `post-excerpt` block to `product-card.html` + `display: none` in grid |
| Star rating in list | ❌ | Woo `product-rating` block or custom |
| Category line | ❌ | `product-meta` or ACF |

**PM decision:** Is list mode a **layout change** only (Figma icons) or **content
parity** with Woodmart list? v1 assumes layout-only.

---

## 9. What to avoid

1. **`?shop_view=`** — Woodmart namespace; use `cf_products_view` in storage only
   unless PM mandates URL state.
2. **Replacing `woocommerce/product-collection`** with a custom query loop — breaks
   WC catalog inheritance, sort integration, and future WC features.
3. **Separate REST field for list HTML** — duplicates Load More card renderer.
4. **Global `displayLayout` edit in DB template** — list-only shop in editor confuses
   merchants; keep editor on grid, toggle on frontend.
5. **Plugin-based switchers** — classic-loop assumptions.

---

## 10. Open decisions

| # | Question | Default recommendation |
|---|---|---|
| 1 | Layout-only vs Woodmart content parity? | Layout-only v1 |
| 2 | Persist in URL for shareable list links? | No — `localStorage` only |
| 3 | Default view on first visit? | Grid (matches WC template + Figma selected state) |
| 4 | Per-row grid density (Woodmart `per_row` 3/4/5)? | Out of scope — Figma shows single grid + list |
| 5 | Own JSX block vs PHP partial for toggle? | ✅ **JSX block** — `chairforce/product-view-switcher`, inserter + reusable |
| 6 | Phase bucket? | **3f-follow-up** or **3g** — independent of filter mechanism |

---

## 11. Suggested QA matrix (when implemented)

- [ ] Shop, category, attribute archive — toggle grid ↔ list
- [ ] Toggle → Load More page 2 — layout consistent
- [ ] List active → apply filter — stays list after shell swap
- [ ] List active → change sort — stays list
- [ ] Browser Back after filter — view restored from storage
- [ ] Mobile filter bar — toggle visible and tappable
- [ ] Quick View / wishlist / swatches work in list layout
- [ ] Keyboard / `aria-pressed` on toggle buttons

---

## 12. Key files (implementation touch list)

| File | Change |
|---|---|
| `parts/shop-archive-shell.html` | Add switcher next to catalog sorting |
| `partials/product-view-switcher.php` | ~~New~~ — use block `render.php` instead |
| `src-jsx-blocks/product-view-switcher/` | New block |
| `src/js/product-view-switcher.js` | New — storage + class + content-updated |
| `src/public.js` | Import switcher on archives |
| `src/sass/woocommerce/_product-view-switcher.scss` | New — list layout |
| `src/sass/woocommerce/_shop-archive-layout.scss` | Grid areas for sort + view |
| `lib/class-woocommerce-archive.php` | Enqueue if needed |

**No changes required (if approach A):**

- `includes/load-more-functions.php`
- `parts/product-collection.html`
- `includes/rest-api/load-more.php`
- `src/js/product-filters.js` (except optional shared content-updated listener)

**Implementation plan:** `context/plans/3k-grid-list-view-toggle-plan.md`

---

## 14. Product-card template-part wrapper (investigation, 1 Aug 2026)

### Observed DOM (SSR Product Collection)

`parts/product-collection.html` nests `<!-- wp:template-part {"slug":"product-card"} /-->`
inside `woocommerce/product-template`. WordPress renders:

```html
<li class="wc-block-product …">
  <div class="wp-block-template-part">
    <div class="wp-block-group cf-card-media">…</div>
    <div class="wp-block-group cf-wrapp-swatches">…</div>
    <h2 class="wp-block-post-title">…</h2>
    …
  </div>
</li>
```

List-view CSS that targets `.cf-card-media` as a **direct** grid child of
`<li>` fails because `cf-card-media` is nested one level deeper.

**Load More append** (`chairforce_render_product_template_item()`) renders
`parts/product-card.html` via `parse_blocks()` + synthetic `core/null` — **no**
`wp-block-template-part` wrapper. Page-1 SSR and appended rows can diverge unless
CSS accounts for both shapes.

### Can WordPress skip the wrapper?

**No supported core option today.**

| Approach | Supported? | Notes |
|---|---|---|
| Block attribute to omit wrapper | ❌ | Open Gutenberg issues [#36853](https://github.com/WordPress/gutenberg/issues/36853), [#53760](https://github.com/WordPress/gutenberg/issues/53760) — not shipped |
| `tagName` on template-part | Partial | Chooses wrapper element (`div`, `section`, …) — **does not** remove wrapper |
| `get_template_part()` in PHP | N/A | Classic API — no block wrapper, but breaks Site Editor composability |
| `render_block` filter | ✅ Workaround | Strip wrapper for specific slugs (e.g. `product-card`) — theme-owned, must keep editor parity in mind |
| **`display: contents` on wrapper** | ✅ CSS (shipped) | Promotes inner card blocks to the `<li>` grid without changing PHP markup |

Core renders template parts in `render_block_core_template_part()` — always wraps
inner content in `$html_tag` + `get_block_wrapper_attributes()` unless filtered.

**Recommendation (locked for 3k):** use **`display: contents`** on
`.wc-block-product > .wp-block-template-part` in list mode. Keeps the template
part in the editor, avoids a global `render_block` unwrap that could affect
header/footer parts, and matches Load More card structure for grid placement.

**Alternative (future):** `render_block` on `core/template-part` when
`slug === 'product-card'` return inner HTML only — would align SSR DOM with Load
More exactly, but adds PHP complexity and bypasses block wrapper attributes
(alignment, etc.) on the card shell.

### Switcher icons not showing (root cause)

Icon-only Sass used `font-size: 0` on `.wp-block-button__link`. Lucide glyphs
use `::before { font-size: 1em }` in `button-icon-font.css`, so **`1em` resolved
to 0**. Fix: hide label with clip/overflow, set explicit `font-size` on
`::before` in `_product-view-switcher.scss`.

---

## 13. Related docs

| Doc | Purpose |
|---|---|
| `context/notes/product-filters-findings.md` | Figma toolbar spec |
| `context/plans/3f-product-filters-plan.md` | Original deferral note §Out of scope |
| `context/plans/3k-grid-list-view-toggle-plan.md` | Implementation plan (Phase 3k) |
| `context/implementation/product-grid.md` | Card + Load More architecture |
| `context/existing-functionality/12A-woodmart-ajax-shop-filtering.md` | Why not PJAX for view |
| `.cursor/skills/chairforce-woocommerce/SKILL.md` | WC integration conventions |
