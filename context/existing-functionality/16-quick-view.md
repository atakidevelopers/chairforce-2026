# 16 — Quick View: Rebuild Notes

Resolves QA question: *"About Quick View, have we made a note that we
shall need to rebuild it? Anything special to salvage from Woodmart or
these plugin groups?"*

**Yes — explicitly noted: Quick View needs a full rebuild**, since it's a
Woodmart-native feature (no separate plugin dependency to worry about
retiring, but also nothing to "keep" — it's part of the theme being
removed). There's no code to port (non-negotiable constraint), but the
*behavior contract* below should be replicated because it's what makes
swatches/gallery/add-to-cart all work identically inside the popup as on
the full single-product page.

## What it is today

- Trigger: a "Quick View" button on product cards, calling AJAX action
  `wp_ajax_woodmart_quick_view` / `wp_ajax_nopriv_woodmart_quick_view`
  (`inc/integrations/woocommerce/modules/quick-view.php`,
  `woodmart_quick_view( $id )`).
- The AJAX response's markup is **not a separate simplified template** —
  it reuses the exact same `variable.php` swatches/variation-form markup
  documented in file `04`, just rendered with `$is_quick_view = true`
  (via `woodmart_loop_prop`) so a couple of CSS/form classes differ
  (`wd-reset-side-lg` etc.).
- After the popup HTML is injected, a `wdQuickViewOpen` custom event fires
  — this is the hook every other swatch/carousel/gallery script listens
  for to (re-)initialize itself *inside the popup's DOM*, without
  double-binding against the main page's own instances of the same
  components:
  - `swatchesVariations.js` (file `04`) re-runs on `wdQuickViewOpen` so
    swatch clicks, gallery-swap, and variation resolution all work inside
    the popup exactly as on the real single-product page.
  - `swiperInit.js` (file `18`) listens for `wdQuickViewOpen300` (a
    slightly delayed variant, presumably to let the popup's layout settle
    before measuring carousel dimensions) to init the popup's own image
    carousel.
  - The child theme (file `07` §2) explicitly *re-enqueues*
    `swatches-variations`, `swatches-limit`, `product-images-gallery`,
    `swiper`/`swiper-carousel`, tooltips, and add-to-cart scripts whenever
    the Quick View button itself is rendered — a defensive guarantee that
    these scripts are actually on the page before `wdQuickViewOpen` needs
    them.
- Popup chrome itself (open/close, backdrop, animation) uses the
  `magnific` popup JS library (`woodmart_enqueue_js_library('magnific')`)
  — a well-known, generic jQuery lightbox/modal library, not anything
  swatch-specific.

## What must be rebuilt (there's no shortcut — this is a real, separate UI surface)

1. **A modal/popup shell** — any modern approach works (a plain React
   portal/dialog component fits this repo's JSX-block conventions better
   than pulling in Magnific Popup, but Magnific itself is a perfectly fine
   minimal choice if a jQuery-free rebuild isn't a priority for this
   specific feature).
2. **An AJAX/REST endpoint** returning the same product markup used on the
   single-product page — literally the single most important thing to
   get right: **do not build a second, parallel "quick view product
   template."** Build the single-product swatch/gallery/add-to-cart
   component once (file `04`'s rebuild requirements) and render *that same
   component* inside the popup, passing whatever product ID was clicked.
   This is exactly Woodmart's own strategy and it's why swatches "just
   work" identically in both places with no extra code.
3. **A custom event** (or equivalent state-management hook, e.g. a React
   effect keyed off "popup opened + product ID") that re-triggers:
   variation-form init, carousel init, and tooltip init for the
   *popup's own DOM subtree* specifically — not a global re-init that
   could double-bind against the main page.

## Nothing plugin-specific to salvage

Quick View has zero dependency on any of the plugins being retired
(Elementor/Jet\*) — it's pure Woodmart theme + WooCommerce core AJAX +
jQuery/Magnific Popup. The only "salvage" that matters is the **behavioral
contract** above (reuse the single-product component, fire one event to
(re-)init it), not any actual code.
