# Product card block migration — template part → locked block

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
- Load More does **not** use the template part block: it **parses
  `parts/product-card.html` directly**, wraps output in a synthetic
  `.wp-block-template-part` div, and renders inner blocks via `WP_Block` — a
  **third pipeline** that can diverge from SSR (add-to-cart / SAVE label nuance).

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
the runtime source for the block. **`parts/product-card.html` is left unchanged**
until a dedicated retirement step (Phase E / full cutover); keep it manually
aligned with the helper until then.

- `cf-card-media` → product image, sale badge, wishlist, quick view
- post title, product swatches, product price, product button
- Inner WC/chairforce blocks use `isDescendentOfQueryLoop: true` where required

---

## 3. Current state (5 Aug 2026)

### Done

- [x] **`chairforce/product-card` block** registered (`src-jsx-blocks/product-card/`)
- [x] **`render.php`** uses **`chairforce_get_product_card_blocks_markup()`** (Phase A)
- [x] **FSE templates** use `chairforce/product-card` block (Phase B — shop, related, upsells, search)
- [x] **Editor:** `ServerSideRender` + `urlQueryArgs.post_id` + `pointer-events: none` on `.wp-block-chairforce-product-card`
- [x] **Editor Sass:** woocommerce, swatches, quick-view, icon-font loaded in `sass-admin/index.scss`
- [x] **Layout Sass:** flex column rules moved to `.wp-block-chairforce-product-card`; template-part rules reduced to **temporary debug bg only**
- [x] **List view Sass:** `_product-view-switcher.scss` targets `.wc-block-product > .wp-block-chairforce-product-card`
- [x] **Classic grid Sass:** `_classic-product-grid.scss` includes `.wp-block-chairforce-product-card`
- [x] **Block works on frontend** for page-embedded Product Collections (user verified)
- [x] **Pattern removed** (`patterns/product-card.php` — was experiment only)

### Temporary debug (remove after migration complete)

- [ ] **`product-card/_layout.scss`** — `.wp-block-template-part { background-color: $color-quaternary; }`  
  **Purpose:** visually identify surfaces still rendering via template part (tan/quaternary band inside `<li>`).  
  **Remove when:** no template-part product cards remain on any surface.

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
| Load More REST (`chairforce/v1/load-more`) | Unused — parsed `product-card.html` + synthetic template-part wrapper | **Remove** (dead code) | ⏳ Phase C |
| Classic WC loop | `content-product.php` + PHP hooks | Unchanged (separate path) | — Out of scope |
| `parts/product-card.html` | FSE template part file (unchanged until full retirement) | Retire in dedicated cutover only | ⏳ Do not edit |
| DB-customised template parts | Unknown — check Site Editor | Re-sync templates | ⏳ QA |

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
earlier/alternate design; nothing in the theme frontend consumes them today
(`loadMoreRestUrl` is localized but unused).

---

## 5. Migration todos (step-by-step)

Work through in order. Check off in this file as each lands.

### Phase A — Single source of markup

- [x] **A1.** Extract inline block markup from `render.php` into
  **`chairforce_get_product_card_blocks_markup()`** in
  `includes/product-card-functions.php`.
- [x] **A2.** Update `render.php` to call the shared helper (not a duplicated string).
- [ ] **A3.** **`parts/product-card.html` — do not touch** during Phases B–D.
  Leave the file registered and unchanged until a **dedicated full-retirement
  step** (Phase E4 + explicit delete). Until then: manual parity only — if card
  composition changes, update **both** the PHP helper and the HTML file together.
  Load More still reads the HTML file until Phase C; deleting or rewriting it
  before Phase C would break append cards.

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
- [ ] **B5.** Browser QA: shop archive — no quaternary debug band on cards (confirms template-part gone)
- [ ] **B6.** Browser QA: single product related + upsells
- [ ] **B7.** Browser QA: product search results template
- [ ] **B8.** Check Site Editor for **DB template overrides** (`wp_template` /
  `wp_template_part` posts); re-save or sync theme files if customised copies
  still reference template part

### Phase C — Remove dead Load More REST card pipeline

Shop append parity is **already satisfied** via page-fetch + Phase B templates.
Phase C is cleanup only — drop the unused REST route and its PHP card render
helpers (development leftovers, no production consumer).

- [ ] **C1.** Delete **`includes/rest-api/load-more.php`** and remove its
  `require_once` from **`lib/class-api.php`**.
- [ ] **C2.** Remove **`loadMoreRestUrl`** from **`lib/class-front.php`**
  localized script data (nothing reads it).
- [ ] **C3.** Remove from **`includes/load-more-functions.php`**:
  `chairforce_get_product_card_template_parsed_block()`,
  `chairforce_wrap_product_card_template_part()`,
  `chairforce_render_product_template_item()`,
  `chairforce_render_product_template_items()`,
  `chairforce_get_load_more_block_context()` — only if nothing else references
  them after C1 (grep before delete).
- [ ] **C4.** Update **`context/plans/3i-load-more-plan.md`** and
  **`context/notes/load-more-findings.md`** — document **page-fetch append** as
  the shipped transport; REST route removed.
- [ ] **C5.** Browser QA (regression): Load More append still works; swatches,
  Quick View, wishlist, add-to-cart, SAVE label, in-cart quantity match page 1.
- [ ] **C6.** Browser QA: Load More after **filter shell swap** — appended cards
  still match filtered page 1.
- [ ] **C7.** Browser QA: **grid/list view toggle** with Load More appended cards.

### Phase D — Sass cleanup

- [ ] **D1.** Remove **temporary** `.wp-block-template-part { background-color: $color-quaternary }` from `_layout.scss`.
- [ ] **D2.** Remove obsolete `.wp-block-template-part` flex/layout rules if any remain (flex rules should live only on `.wp-block-chairforce-product-card`).
- [ ] **D3.** Audit **`_product-view-switcher.scss`** — confirm list layout only
  needs `.wp-block-chairforce-product-card` (drop stale template-part comments/selectors).
- [ ] **D4.** Audit **`_classic-product-grid.scss`** — classic loop still uses
  `.cf-product-card__inner.wp-block-template-part`; do not remove unless classic
  path is migrated.
- [ ] **D5.** Run `npm run build:assets` after Sass changes.

### Phase E — PHP / hooks / guards

- [ ] **E1.** Restore **`chairforce_boots_product_card_features()`** to shop-archive
  scope (revert temporary `return true`) **or** document intentional widening
  for page-embedded collections and split guards if needed.
- [ ] **E2.** Re-evaluate **`chairforce_product_card_resolve_product_id()`** —
  keep if editor SSR still needs it; remove if unused after block-only path.
- [ ] **E3.** Update **`class-classic-wc-compatibility.php`** docblock (still references
  `parts/product-card.html` for block cards — update wording).
- [ ] **E4.** **`theme.json`** — remove or deprecate `product-card` template part
  registration when file is retired.

### Phase F — Editor curation

- [ ] **F1.** Confirm **`chairforce/product-card`** `supports.inserter: true` only
  inside `product-template` (`ancestor` in block.json) — OK as-is.
- [ ] **F2.** Optional: hide **`product-card` template part** from Site Editor
  template part list once retired (`Editor_Curation` or remove from theme.json).
- [ ] **F3.** Editor preview: confirm SSR + `urlQueryArgs` still show correct
  product per loop item (not page title).

### Phase G — Docs & PROGRESS

- [ ] **G1.** Update **`context/implementation/product-grid.md`** — block as
  canonical path; Load More renders same block.
- [ ] **G2.** Update **`context/PROGRESS.md`** §3m status and locked approach
  (template part → locked block, not wrapper around template part).
- [ ] **G3.** Cross-link this note from `product-grid-cards-and-load-more.md`
  (supersession note only).

---

## 6. Verification matrix

After Phases B + C, confirm each surface:

| Surface | Card DOM | Swatches | QV | Wishlist | Add to cart | SAVE label | In-cart label |
|---|---|---|---|---|---|---|---|
| Shop archive page 1 | | | | | | | |
| Shop Load More append | | | | | | | |
| Shop after filter shell | | | | | | | |
| List view toggle | | | | | | | |
| Related products | | | | | | | |
| Upsells | | | | | | | |
| Product search | | | | | | | |
| Page-embedded collection | | | | | | | |

---

## 7. Key files

| Role | Path |
|---|---|
| Block render (canonical) | `src-jsx-blocks/product-card/render.php` |
| Block editor | `src-jsx-blocks/product-card/edit.js`, `editor.scss` |
| Template part file (legacy) | `parts/product-card.html` |
| FSE consumers | `parts/product-collection.html`, `product-related.html`, `product-upsells.html`, `templates/product-search-results.html` |
| Load More | `includes/load-more-functions.php` (query helpers only after C); dead REST: `includes/rest-api/load-more.php` |
| Load More JS | `src/js/shared/load-more.js` (page-fetch append) |
| Card hooks | `includes/product-card-hooks.php`, `includes/product-card-functions.php` |
| Layout Sass | `src/sass/woocommerce/product-card/_layout.scss` |
| List view Sass | `src/sass/woocommerce/_product-view-switcher.scss` |
| Classic loop (unchanged) | `woocommerce/content-product.php`, `lib/class-classic-wc-compatibility.php` |

---

## 8. Out of scope (this migration)

- **Classic WooCommerce loop** (`content-product.php` + hook-based card) — separate
  parity path; keeps `.wp-block-template-part` class on inner wrapper for Sass.
- **Product card behaviour on non-shop surfaces** without intentional guard
  widening — decide in Phase E1.
- **New block attributes** — card stays zero-config; no editor-facing options.

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
