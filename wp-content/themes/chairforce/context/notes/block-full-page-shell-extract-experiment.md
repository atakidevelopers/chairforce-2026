# Block full-page shell extract experiment

**Branch:** `experiment/block-full-page-shell-extract`  
**Date:** 2026-08-03

## Hypothesis

Filter/sort refresh on the **block-based** shop archive may behave more like SSR if we fetch the **full catalog page** and extract `.cf-shop-archive-shell` with `DOMParser`, instead of hitting the dedicated `_cf_archive=shell` partial endpoint.

If classic shop fixes worked because partial rendering matched SSR, the same idea might fix block-template mismatches (filter counts, Product Collection state, etc.) without abandoning blocks on the catalog.

## What changed

| Area | Change |
|------|--------|
| `templates/archive-product.html` | Restored block template (`shop-archive-shell` + Product Collection), reverted main's `legacy-template` spike |
| `src/js/product-filters.js` | `buildArchiveShellFetchUrl()` no longer adds `_cf_archive=shell`; fetches normal catalog URL (page 1, no shell arg). `parseArchiveShellFromHtml()` unchanged — already selects `.cf-shop-archive-shell` from any HTML document |
| Server | `chairforce_maybe_render_archive_shell_fragment()` left in place but unused by filter JS on this branch |

## What did NOT change

- **Load More** — still REST append to `.wc-block-product-template` (block path). This experiment targets **filter refresh** only.
- **Classic spike** — preserved on `spike/classic-shop-archive-shell` for fallback if this fails.

## How to test

1. `cd wp-content/themes/chairforce && npm run build:assets`
2. Shop / category archive with block Product Collection
3. Apply filters, change sort, clear chips — shell should swap without full page reload
4. Compare filter counts, swatches, price slider, grid/list toggle vs SSR first load
5. Load More after filter refresh — note whether Add to Cart / YITH still break (expected unchanged on block path)

## Success criteria

- Filter refresh works reliably (no blank shell, no wrong query)
- Post-filter SSR and AJAX shell markup match (counts, active states, collection grid)
- No regression vs partial `_cf_archive=shell` path for filter UX

## If this fails

Finalize **dual-path** approach from classic spike:

- Classic PHP loop + shell partial for shop/category archives
- Block Product Collection for related products, upsells, etc.

Merge `spike/classic-shop-archive-shell` after follow-ups in `context/notes/classic-shop-archive-spike-followups.md`.
