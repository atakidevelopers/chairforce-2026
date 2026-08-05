# Product grid — implementation tracker

**Status: ✅ Done (Aug 2026)** — shared card via **`chairforce/product-card`** block
(`a295196`+); Load More page-fetch append (`d7d7acc`, REST removed Phase C).

Short reference for how **Quick View**, **Swatches**, and **Load More** land on Product Collection cards. Migration history: `context/notes/product-card-block-migration.md`.

---

## Shared card markup

Canonical card composition lives in **`chairforce_get_product_card_blocks_markup()`**
(`includes/product-card-functions.php`), rendered by the locked **`chairforce/product-card`**
dynamic block (`.wp-block-chairforce-product-card` wrapper).

| FSE consumer | Card block inside `woocommerce/product-template` |
|---|---|
| `parts/product-collection.html` | Archive — `inherit: true` + Load More pagination |
| `parts/product-upsells.html` | Single product — upsells collection |
| `parts/product-related.html` | Single product — related collection |
| `templates/product-search-results.html` | Product search archive |

**Templates:** `archive-product.html` → `product-collection` · `templates/single-product.html` → upsells + related · page content → insert `chairforce/product-card` in Product Collection loops.

**DB overrides:** none — FSE uses theme files only (orphan `slim-header` part removed Aug 2026).

**Retired:** `parts/product-card.html` template part (removed Aug 2026); do not re-register in `theme.json`.

---

## Quick View

**Block in card markup** — `chairforce/quick-view-button` inside `cf-card-media`.

| Piece | Location |
|---|---|
| Block render | `src-jsx-blocks/quick-view-button/render.php` — reads `postId` from block context |
| Popup content | REST `GET /wp-json/chairforce/v1/quick-view/{id}` — `includes/rest-api/quick-view.php` |
| Frontend | `src/js/quick-view.js` — delegated click on `.cf-quick-view-trigger` |
| Assets | `WooCommerce_Archive::enqueue_quick_view_assets()` when `chairforce_boots_product_card_features()` |
| Styles | `src/sass/quick-view/` — trigger over image; `cf-card-media` is `position: relative` |

Classic loop outputs equivalent trigger markup via `Classic_WC_Compatibility` hooks.

---

## Swatches

**Block in card markup** — `chairforce/product-swatches` in card composition string.

| Piece | Location |
|---|---|
| Block render | `src-jsx-blocks/product-swatches/render.php` → `Product_Swatches::render_grid_swatches()` |
| PHP class | `lib/class-product-swatches.php` |
| Grid interactions | `src/js/single-product-swatches.js` — delegated hover/click, image swap |
| Context | Block uses `postId` from `woocommerce/product-template` ancestor |

Classic loop: `Classic_WC_Compatibility::render_loop_swatches()`.

---

## Load More

**Page-fetch append** — fetches full catalog page HTML, extracts `.wc-block-product-template > li` (see `context/notes/load-more-findings.md`).

| Piece | Location |
|---|---|
| Frontend | `src/js/shared/load-more.js` — `GET /shop/page/N/`, append SSR `<li>` nodes |
| Query helpers | `includes/load-more-functions.php` — query var export/sanitize for button payload |
| Button | `lib/class-load-more.php` — replaces page-1 pagination when `loadMore: true` |

**Scope (v1):** shop / category / attribute archives — inherited Product Collection (`inherit: true`).

### Card render path

```
product-collection.html → chairforce/product-card → chairforce_get_product_card_blocks_markup()
  → page 2 full HTML fetch → extract <li> → append to page-1 grid
```

Appended cards rely on **delegated events** (`src/js/shared/delegated-events.js`).

---

## Classic loop

**Separate PHP path** — `woocommerce/content-product.php` + `Classic_WC_Compatibility` hooks.
Uses **`.wp-block-chairforce-product-card`** on the inner wrapper for shared Sass with block cards; does not render the block.

---

## Card composition summary

| Feature | Block cards | Classic loop |
|---|---|---|
| Card shell | `chairforce/product-card` block | `content-product.php` + hooks |
| Swatches | `chairforce/product-swatches` block | `render_loop_swatches()` |
| Quick View | `chairforce/quick-view-button` block | PHP helper markup |
| Load More | Page-fetch clones FSE SSR | N/A |
