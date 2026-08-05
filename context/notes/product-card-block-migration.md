# Product card block migration — template part → locked block

**Status: ✅ Complete (Aug 2026)**

Captured: 5 Aug 2026.

Tracks the move from **`core/template-part` (`product-card`)** to the locked
**`chairforce/product-card`** dynamic block as the single render path for Product
Collection cards. Implements **PROGRESS §3m** with an updated approach: markup
lives in PHP (block `render.php`), not an editor-exposed template part.

**Related docs:**

- `context/PROGRESS.md` — §3m Product card block + full Product Collection parity
- `context/implementation/product-grid.md` — current wiring tracker (update after migration)
- `context/notes/product-grid-cards-and-load-more.md` — card composition history
- `context/notes/load-more-findings.md` — Load More append architecture
- `context/notes/grid-list-switcher-findings.md` — list view DOM expectations

---

## 1. Problem statement

### Template part issues

- **`core/template-part`** inside `woocommerce/product-template` requires a
  **context dance** (`$block->context`, global `$post`, editor SSR `post_id`
  query args) so inner blocks resolve the loop product, not the edited page.
- Template parts are **editable in the Site Editor** — editors can break card
  composition (remove swatches, reorder price/button, detach inner blocks).
- Load More historically used a **separate PHP pipeline**: REST route parsed
  `parts/product-card.html` directly (removed Phase C — see §Phase C). Shop
  append now clones SSR from the same FSE template as page 1 (page-fetch).

### Block approach (chosen)

- **`chairforce/product-card`** — dynamic JSX block, `ancestor`:
  `woocommerce/product-template`, markup in **`render.php`** via inline
  `do_blocks()` string (same block tree as `parts/product-card.html`).
- Output wrapper: **`.wp-block-chairforce-product-card`** (not
  `.wp-block-template-part`).
- Card markup is **theme-controlled**; end users insert one opaque block, not a
  decomposable template part.
- Frontend: loop sets global `$post` before render — **no template-part context
  merge** needed for block technique on shop/page collections (verified).

---

## 2. Render techniques (reference)

Two experiments were kept in git history; **`render.php` now uses the block
technique only.**

| Technique | Mechanism | Wrapper | Context |
|---|---|---|---|
| **Template part** (`render-part.php`) | `WP_Block( core/template-part )` + `$available_context` | `.wp-block-template-part` | Explicit context merge; editor SSR fragile |
| **Block markup** (`render-card.php` → **`render.php`**) | `do_blocks( inline markup )` inside `get_block_wrapper_attributes()` | `.wp-block-chairforce-product-card` | Relies on loop global `$post`; works on frontend |

**Canonical markup** — PHP helper `chairforce_get_product_card_blocks_markup()` is
the sole runtime source. **`parts/product-card.html` retired** (E4, Aug 2026).

- `cf-card-media` → product image, sale badge, wishlist, quick view
- post title, product swatches, product price, product button
- Inner WC/chairforce blocks use `isDescendentOfQueryLoop: true` where required

---

## 3. Current state (5 Aug 2026)

### Done

- [x] **`chairforce/product-card` block** registered (`src-jsx-blocks/product-card/`)
- [x] **`render.php`** uses **`chairforce_get_product_card_blocks_markup()`** (Phase A)
- [x] **FSE templates** use `chairforce/product-card` block (Phase B — shop, related, upsells, search)
- [x] **Phase C:** removed unused Load More REST route + `product-card.html` PHP parse pipeline
- [x] **Phase C QA:** Load More append, filter shell, grid/list toggle (manual — Aug 2026)
- [x] **Phase D:** Sass complete; quaternary debug **removed** (Aug 2026)
- [x] **Phase E–G:** guards, legacy file retirement, editor, docs (Aug 2026)
- [x] **Editor:** `ServerSideRender` + `urlQueryArgs.post_id` + `pointer-events: none` on `.wp-block-chairforce-product-card`
- [x] **Editor Sass:** woocommerce, swatches, quick-view, icon-font loaded in `sass-admin/index.scss`
- [x] **Layout Sass:** flex column rules on `.wp-block-chairforce-product-card`
- [x] **List view Sass:** `_product-view-switcher.scss` targets `.wc-block-product > .wp-block-chairforce-product-card`
- [x] **Classic grid Sass:** `_classic-product-grid.scss` includes `.wp-block-chairforce-product-card`
- [x] **Block works on frontend** for page-embedded Product Collections (user verified)
- [x] **Pattern removed** (`patterns/product-card.php` — was experiment only)

### Retired

- [x] **`parts/product-card.html`** — deleted; removed from `theme.json` (E4)

---

## 4. Surfaces inventory

| Surface | Current mechanism | Target | Status |
|---|---|---|---|
| Page content Product Collection | `chairforce/product-card` block | Block | ✅ Done |
| Shop archive (`product-collection.html`) | `chairforce/product-card` block | Block | ✅ Done |
| Related products (`product-related.html`) | `chairforce/product-card` block | Block | ✅ Done |
| Upsells (`product-upsells.html`) | `chairforce/product-card` block | Block | ✅ Done |
| Product search (`product-search-results.html`) | `chairforce/product-card` block | Block | ✅ Done |
| Load More shop append | **Page-fetch** of `/shop/page/N/` → clone `<li>` from SSR (same FSE template as page 1) | Block via template (Phase B) | ✅ Done |
| Load More REST (`chairforce/v1/load-more`) | Removed (was unused dead code) | — | ✅ Phase C |
| Classic WC loop | `content-product.php` + PHP hooks | Unchanged (separate path) | — Out of scope |
| `parts/product-card.html` | Retired (deleted + unregistered from theme.json) | — | ✅ E4 |
| DB-customised template parts | None (orphan `slim-header` removed Aug 2026) | Theme files only | ✅ Done |

**Chain for shop archive:**

```
archive-product.html → shop-archive-shell → product-collection → [product-card]
```

Only the innermost `[product-card]` reference changes per row.

### Load More transport (important)

Shop Load More **does not** call `chairforce/v1/load-more`. Frontend
(`src/js/shared/load-more.js`) fetches the **full catalog page URL** for page
2+, parses `.wc-block-product-template > li`, and appends those nodes. Appended
cards therefore match whatever `product-collection.html` SSR renders — after
Phase B, that is **`chairforce/product-card`** automatically. No separate PHP
card renderer is involved in the live append path.

The REST route and `chairforce_render_product_template_*()` helpers were an
earlier/alternate design; **removed in Phase C** (Aug 2026). Nothing in the
theme frontend consumed them (`loadMoreRestUrl` was localized but unused).

**Goal after Phase C:** one PHP card render path only —
`chairforce/product-card` → `chairforce_get_product_card_blocks_markup()`. No
parallel REST/HTML-file parser.

---

## 5. Migration todos (step-by-step)

Work through in order. Check off in this file as each lands.

### Phase A — Single source of markup

- [x] **A1.** Extract inline block markup from `render.php` into
  **`chairforce_get_product_card_blocks_markup()`** in
  `includes/product-card-functions.php`.
- [x] **A2.** Update `render.php` to call the shared helper (not a duplicated string).
- [x] **A3.** **`parts/product-card.html` retired** (E4) — canonical markup is
  `chairforce_get_product_card_blocks_markup()` only.

### Phase B — FSE templates (replace template-part with block)

Replace in each file inside `woocommerce/product-template`:

```html
<!-- wp:chairforce/product-card /-->
```

instead of:

```html
<!-- wp:template-part {"slug":"product-card","theme":"chairforce"} /-->
```

- [x] **B1.** `parts/product-collection.html` (shop archive + Load More page 1)
- [x] **B2.** `parts/product-related.html`
- [x] **B3.** `parts/product-upsells.html`
- [x] **B4.** `templates/product-search-results.html`
- [x] **B5.** Browser QA: shop archive — block cards, no template-part usage
- [x] **B6.** Browser QA: single product related + upsells
- [x] **B7.** Browser QA: product search results template
- [x] **B8.** Check Site Editor for **DB template overrides** — **none** for product
  templates/parts; removed orphan **`slim-header`** `wp_template_part` (ID 1514425,
  Aug 2026) so FSE uses theme files only.

### Phase C — Remove dead Load More REST card pipeline

Shop append parity is **already satisfied** via page-fetch + Phase B templates.
Phase C is cleanup only — drop the unused REST route and its PHP card render
helpers so there is **one PHP card pipeline** (block helper), not a parallel
REST/`product-card.html` parser.

- [x] **C1.** Delete **`includes/rest-api/load-more.php`** and remove its
  `require_once` from **`lib/class-api.php`**.
- [x] **C2.** Remove **`loadMoreRestUrl`** from **`lib/class-front.php`**
  localized script data (nothing reads it).
- [x] **C3.** Remove from **`includes/load-more-functions.php`**:
  `chairforce_get_product_card_template_parsed_block()`,
  `chairforce_wrap_product_card_template_part()`,
  `chairforce_render_product_template_item()`,
  `chairforce_render_product_template_items()`,
  `chairforce_get_load_more_block_context()`.
- [x] **C4.** Update **`context/plans/3i-load-more-plan.md`** and
  **`context/notes/load-more-findings.md`** — document **page-fetch append** as
  the shipped transport; REST route removed.
- [x] **C5.** Browser QA (regression): Load More append still works; swatches,
  Quick View, wishlist, add-to-cart, SAVE label, in-cart quantity match page 1.
- [x] **C6.** Browser QA: Load More after **filter shell swap** — appended cards
  still match filtered page 1.
- [x] **C7.** Browser QA: **grid/list view toggle** with Load More appended cards.

### Phase D — Sass cleanup

- [x] **D1.** Removed quaternary debug `.wp-block-template-part` rule from `_layout.scss`.
- [x] **D2.** Flex/layout rules on `.wp-block-chairforce-product-card` only.
- [x] **D3.** **`_product-view-switcher.scss`** comment + selector fixed for block wrapper.
- [x] **D4.** **`_classic-product-grid.scss`** + classic PHP use `.wp-block-chairforce-product-card` shell (hooks, not block render).
- [x] **D5.** **`npm run build:assets`** — compiled without quaternary debug rule.

### Phase E — PHP / hooks / guards

- [x] **E1.** **`chairforce_boots_product_card_features()`** — no shop-archive gate;
  runs on any front-end request (collections on any page). WC, admin, and cart-fragment
  guards kept. **`chairforce_is_product_shop_archive()`** retained for other callers.
- [x] **E2.** Removed **`chairforce_product_card_resolve_product_id()`** (unused).
- [x] **E3.** Updated **`class-classic-wc-compatibility.php`** docblock.
- [x] **E4.** Deleted **`parts/product-card.html`**; removed **`product-card`** from
  `theme.json` `templateParts`.

### Phase F — Editor curation

- [x] **F1.** Block `ancestor` / inserter — verified OK.
- [x] **F2.** Legacy template part removed from theme (E4); no Site Editor listing.
- [x] **F3.** Editor SSR + `urlQueryArgs` — verified OK.

### Phase G — Docs & PROGRESS

- [x] **G1.** Updated **`context/implementation/product-grid.md`**
- [x] **G2.** Updated **`context/PROGRESS.md`** §3m
- [x] **G3.** Cross-link in **`product-grid-cards-and-load-more.md`**

---

## 6. Verification matrix

Manual QA passed Aug 2026:

| Surface | Status |
|---|---|
| Shop archive page 1 | ✅ |
| Shop Load More append | ✅ |
| Shop after filter shell | ✅ |
| List view toggle | ✅ |
| Related products | ✅ |
| Upsells | ✅ |
| Product search | ✅ |
| Page-embedded collection | ✅ |

---

## 7. Key files

| Role | Path |
|---|---|
| Block render (canonical) | `src-jsx-blocks/product-card/render.php` |
| Markup helper | `includes/product-card-functions.php` → `chairforce_get_product_card_blocks_markup()` |
| FSE consumers | `parts/product-collection.html`, `product-related.html`, `product-upsells.html`, `templates/product-search-results.html` |
| Load More | `includes/load-more-functions.php` (query + button helpers); `src/js/shared/load-more.js` (page-fetch append) |
| Card hooks | `includes/product-card-hooks.php`, `includes/product-card-functions.php` |
| Layout Sass | `src/sass/woocommerce/product-card/_layout.scss` |
| List view Sass | `src/sass/woocommerce/_product-view-switcher.scss` |
| Classic loop | `woocommerce/content-product.php` (`.wp-block-chairforce-product-card` shell), `lib/class-classic-wc-compatibility.php` |

---

## 8. Out of scope (this migration)

- **Classic WooCommerce loop** — separate PHP hook path; shares `.wp-block-chairforce-product-card` Sass shell, does not render the block.
- **New block attributes** — card stays zero-config.

---

## 9. Decision log

| Date | Decision |
|---|---|
| 5 Aug 2026 | Standardise on **`chairforce/product-card`** block with inline `do_blocks()` markup, not `core/template-part` render. |
| 5 Aug 2026 | Remove experimental **`patterns/product-card.php`** (duplicate markup). |
| 5 Aug 2026 | Temporary **quaternary background** on `.wp-block-template-part` inside `.wc-block-product` to flag remaining template-part surfaces during migration. |
| 5 Aug 2026 | **A1/A2:** `chairforce_get_product_card_blocks_markup()` — single PHP source for block render. |
| 5 Aug 2026 | **B1–B4:** FSE templates switched to `chairforce/product-card`; **`parts/product-card.html` untouched** (A3). |
| 5 Aug 2026 | Load More shop append uses **page-fetch**, not REST — Phase B gives block parity without Phase C refactor. |
| 5 Aug 2026 | Phase C rescoped: **remove unused REST route + PHP card render helpers**, not block-via-REST migration. |
| 5 Aug 2026 | **Phase C done:** REST route + `product-card.html` parse pipeline removed; single PHP card path via block helper. |
| 5 Aug 2026 | Phase C manual QA passed; migration **complete** (E–G, quaternary debug removed). |
| 5 Aug 2026 | **E4:** `parts/product-card.html` deleted; `product-card` removed from `theme.json`. |
| 5 Aug 2026 | **E1:** `chairforce_boots_product_card_features()` — no shop-archive gate (page collections). |
