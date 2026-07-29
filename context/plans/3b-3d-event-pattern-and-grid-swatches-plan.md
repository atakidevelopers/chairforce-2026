# 3b + 3d — Event Delegation Pattern & Product Card/Grid Swatches — Implementation Plan

## Status: ✅ Done (29 Jul 2026)

| Chunk | Scope | Commit |
|---|---|---|
| 1 (3b) | Event delegation convention | `50ab984` |
| 2 (3d-i) | PHP swatch helpers + `chairforce/product-swatches` block | `52847e0` |
| 3 (3d-ii) | Hover/click image swap + limit-swatches JS | `6e6c1b0` |
| 4 (3d-iii) | Sass (style 3/M/round) + `archive-product.html` | `83393c0` |

Verified on `/product-category/chairs/cafe-chairs/` against variable
`pa_colour` products (Eros Chair hover swap, +N expand, delegated handlers).

## Goal

Execute the next two roadmap items from `context/PROGRESS.md`:

- **3b** — the foundational rendering-infrastructure convention that
  everything from 3d onward depends on (file `17`'s event-delegation rule).
- **3d** — product card/grid color swatches + click-to-swap-image, per file
  `03` (behavior) and `05`/`09` §3 (styling, hardcoded to the one confirmed
  live combination).

**Source of truth for behavior/data:** `context/existing-functionality/03-product-card-grid-swatches.md`,
`05-css-styling-reference.md`, `09-rebuild-considerations.md` §1–3,
`17-load-more-and-event-delegation.md`. Hand the agent doing this work
those files directly, not a paraphrase.

## Scope decision — read this before starting (why 3b is narrower than the roadmap entry implies)

`PROGRESS.md`'s 3b description bundles two shared pieces together: the
event-delegation convention **and** a shared Swiper carousel component.
Checking the current codebase before writing this plan found:

- **No page currently uses "Load More"/infinite-scroll pagination.**
  `templates/archive.html` and `templates/index.html` (the only archive-
  style templates that exist in this theme so far) both use standard
  numbered `query-pagination`/`-previous`/`-next`/`-numbers` blocks, and
  neither WordPress core's Query block nor WooCommerce's `product-
  collection` block ships a built-in "Load More" button or infinite-scroll
  mode (confirmed — no such attribute/JS in the installed WooCommerce
  plugin). There is currently no live Load More button anywhere to make
  delegation-safe.
- **Swiper is not installed** (`package.json` has no `swiper` dependency)
  and **not referenced anywhere** in `src/` or `src-jsx-blocks/`. Nothing
  in 3d needs a carousel — that's 3e's single-product gallery.

Building the actual Load More UI and the shared carousel component now
would be speculative work with no consumer yet. **This plan only covers:**

1. The event-delegation **convention** itself (cheap, foundational, needed
   by 3d's click handler from day one) — not an actual Load More feature.
2. The full 3d grid-swatches feature.

The Swiper carousel component and the actual Load More button/infinite-
scroll feature are deferred to whichever phase first needs them (3e for
Swiper; whenever a page's design actually calls for Load More instead of
numbered pagination) — tracked as a new "Open decisions" item in
`PROGRESS.md`, not silently dropped.

## Architecture decision — how swatches attach to the product grid

Checked before writing this plan:

- No `templates/archive-product.html` override exists in this theme yet.
  `product_cat`/`product_tag`/`pa_*` attribute archives currently render
  through **WooCommerce's own bundled block template**
  (`ProductCategoryTemplate` etc., `wp-content/plugins/woocommerce/src/Blocks/Templates/`),
  whose default markup (`templates/templates/blockified/archive-product.html`
  inside the WooCommerce plugin) is:
  `woocommerce/product-collection` → `woocommerce/product-template` →
  `woocommerce/product-image` + `post-title` + `woocommerce/product-price` +
  `woocommerce/product-button`.
- **Recommendation:** build the swatch UI as a new **dynamic JSX block**,
  `chairforce/product-swatches` (per this repo's block-system rules — JSX
  preferred, `render.php` for server-side logic), not a PHP template-string
  partial glued into a `content-product.php`-equivalent. Inside any Query
  Loop / Product Collection / Product Template context, WooCommerce
  automatically provides `$block->context['postId']` — `render.php` just
  calls `wc_get_product( $block->context['postId'] )`, no manual `$product`
  global wrangling needed.
- **Why a block, not a template override alone:** a block can be inserted
  into the Product Collection's inner blocks on *any* current or future
  page that uses that block — shop/category/attribute archives now, and
  Phase 4's home-page best-sellers grid later — without duplicating the
  swatch-resolution logic per page.
- **Still need one small template change:** since no `archive-product.html`
  override exists yet, this plan includes creating one (copy of
  WooCommerce's blockified default + `chairforce/product-swatches` inserted
  right after `woocommerce/product-image`, matching Woodmart's own DOM
  order — swatches render immediately under the image in `wrapp-swatches`).

## Chunk breakdown

### Chunk 1 (3b) — Event delegation convention

- **What:** a small shared JS module establishing the one rule that makes
  everything downstream Load-More-safe: delegate every interactive handler
  on `document` (or another stable, never-replaced ancestor) — never bind
  directly to card/grid elements at page-load time. Also define a
  `chairforce:content-updated` custom event, dispatched on `document`
  whenever grid content is appended in the future (Load More/AJAX filters),
  for the rare case a feature genuinely needs to react to *new* DOM nodes
  rather than just delegating clicks (e.g. future carousel re-init — not
  swatches, which are pure delegated click handling and need no re-scan).
- **Why now:** 3d's hover-to-swap-image handler (see chunk 3 — client
  decision: hover, not click, matching the live child-theme customization)
  must be written this way from the start — retrofitting delegation onto
  an already-shipped direct-bind handler is exactly the mistake file `17`
  documents Woodmart's own codebase avoiding on purpose, everywhere,
  consistently.
- **Explicitly not in this chunk:** an actual Load More button/infinite-
  scroll feature, and the shared Swiper carousel component — see scope
  decision above.
- **Deliverable:** `src/js/shared/delegated-events.js` (exact name/shape up
  to the implementing agent) + a short paragraph documenting the
  convention (in this plan or a `.cursor/rules` addition) for 3e–3h to
  follow without re-deriving it.

### Chunk 2 (3d-i) — PHP swatch data resolution + the block itself

- Port the *read-side* logic of `woodmart_has_swatch()` /
  `woodmart_get_option_variations()` / `woodmart_grid_swatches_attribute()`
  (file `09` §1's explicit recommendation — near-literal port, renamed to
  the theme's own namespace, not reinvented) as small PHP helper
  function(s), reading:
  - **Which attribute to show:** per-product override post meta
    `_woodmart_swatches_attribute` (real key, real data — 1 product live,
    but a real code path) → else a **hardcoded default of `pa_colour`**
    (file `02` §3 — confirmed live value; there's no Woodmart Options
    framework to replace with equivalent config, and no request yet to
    build a new settings UI for this one value).
  - **Per-term swatch data:** `color`/`image`/`not_dropdown` term meta
    (already has its own ACF admin UI from 3c) + each term's matching
    variation (`get_available_variations()`), same color → variation-image
    → term-image → text priority as file `03`.
- New block: `src-jsx-blocks/product-swatches/` (`block.json`, `index.js`,
  `edit.js`, `save.js` returning `null`, `render.php`). `render.php` bails
  (renders nothing) unless the resolved product is a `variable` product
  with at least one term of the resolved attribute present among its
  variations — mirrors Woodmart's own bail conditions minus the "quick
  shop enabled" Theme Setting, which has no equivalent in the new theme
  (always render when the above conditions hold).
- **Scope confirmed: Mode A only** (`select_options`) — swatches swap the
  card's image; they never expose a full add-to-cart form on the card.
  Mode B (`variation_form`) is not the live config on this site (file `09`
  §0.6) — out of scope.

### Chunk 3 (3d-ii) — Frontend JS: hover-to-swap-image + limit-swatches

**Both open questions from the original draft are now resolved (client
decisions below) — chunk scope unchanged otherwise.**

- **Interaction trigger is hover, not click** — confirmed: mirrors the
  live child-theme customization already layered on top of vanilla
  Woodmart (file `07` §1, `woodmart_child_add_swatch_hover`), not
  Woodmart's own stock click-only behavior. On non-touch/desktop
  (`!("ontouchstart" in window || navigator.maxTouchPoints > 0)`),
  `mouseenter` on a swatch swaps the card's `<img>`
  (`data-image-src`/`-srcset`/`-sizes`) to that swatch's variation image;
  touch devices keep click. No auto-revert on `mouseleave` — the image
  stays swapped until a different swatch is hovered/clicked (matches the
  live behavior exactly: `mouseleave` in the child theme only clears an
  internal `hover-selected` tracking class, it doesn't restore the
  original image). Clicking a swatch still works too (touch devices, and
  as a no-JS-hover fallback) — implement hover as a thin wrapper that
  triggers the same underlying swap logic click uses, per file `07`'s own
  explicit recommendation, not a duplicate code path.
- **"Limit swatches" (+N collapse): confirmed in scope.** Client decision:
  yes to +N (not always-expanded). The limit count is a **hardcoded
  constant in the JS/PHP**, not an admin-configurable field/ACF
  setting — if the client wants a different number later, that's a small
  code change on request, not a UI to build now. (Live reference value
  was `swatches_limit_count = 3` — confirm with the client/design whether
  to reuse that number or pick a new one when building, since Figma may
  show a different grid density.)
- **Explicitly out of scope:** the separate "grid image gallery" hover-
  preview feature (`imagesGalleryInLoop.js`, file `03`'s "adjacent,
  non-swatch grid feature" section) and its `wdImagesGalleryInLoopOn`/`Off`
  coordination — confirmed not needed. This is a genuinely different
  feature (hovering pagination dots to preview a product's *own* other
  photos, unrelated to swatches) — no coordination logic needed since it's
  not being built.

### Chunk 4 (3d-iii) — Styling + template wiring

- **Sass:** implement the one confirmed style combination — active style 3
  (plain 1px border, gray-500 on hover/active), disabled style 3 (40%
  opacity + one gray diagonal line), size M, shape round (file `05`) — as
  theme-owned classes (own naming, not `wd-*`) in a new
  `src/sass/swatches/` feature directory per this repo's assets-styling
  rule. **Do not** build the full 4-style × 3-disabled-style × 3-shape ×
  6-size admin-configurable matrix — hardcoded per file `09` §3.
- **Template:** create `templates/archive-product.html` (currently
  missing) — WooCommerce's blockified default plus `chairforce/product-
  swatches` inserted into `product-template`'s inner blocks, right after
  `woocommerce/product-image`.
- **Note — one Woodmart CSS rule that does *not* apply here:** file `05`'s
  `.wd-swatches-product + select { display:none }` (hiding the native
  `<select>` once swatches take over) is a **single-product-page** rule
  (3e's concern, real `<select>`-bound variation form). Grid/Mode-A
  swatches never render or hide a `<select>` at all — don't carry this
  rule over into the grid styling by mistake.

## Verification rule

For each sub-chunk, before marking it done:

1. Test against a **real variable product with genuine per-term color/
   image data** (not the first product you click) — e.g. spot-check a
   `pa_colour`-varying chair on `/product-category/chairs/cafe-chairs/`
   (confirmed live/rendering correctly per `PROGRESS.md`'s known-issues
   history).
2. Confirm the color/variation-image/term-image/text rendering priority
   matches file `03` exactly, including out-of-stock variations getting
   the disabled treatment.
3. Confirm hover-swap works on desktop/mouse (swap on `mouseenter`, stays
   swapped on `mouseleave`, changes again on hovering a different swatch),
   click-swap still works on touch devices, and the handler survives being
   re-tested after a full page navigation (delegation sanity check — not
   meaningful yet with no Load More button live, but confirms the handler
   really is delegated and not accidentally bound to specific card nodes).
4. Screenshot before/after on at least one grid page for the PM/client
   record, same as prior phases' verification screenshots.

## Related files

- `context/existing-functionality/03-product-card-grid-swatches.md` —
  full behavioral spec (both quick-shop modes, JS event coordination)
- `context/existing-functionality/05-css-styling-reference.md` — CSS
  class/style reference, confirmed live combination
- `context/existing-functionality/09-rebuild-considerations.md` §1–3 —
  data layer, rendering-layer design guidance, confirmed live settings
- `context/existing-functionality/17-load-more-and-event-delegation.md` —
  the event-delegation rule this plan's chunk 1 implements
- `context/existing-functionality/18-carousel-library.md` — Swiper
  decision, deferred out of this plan (see scope decision above)
- `context/existing-functionality/02-data-model-and-storage.md` §3/§5 —
  `_woodmart_swatches_attribute`/`grid_swatches_attribute` data contract
- `context/PROGRESS.md` — project-wide phase tracker this plan updates
  once chunks land
