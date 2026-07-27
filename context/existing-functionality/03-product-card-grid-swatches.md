# 03 — Product Card / Grid Swatches (PHP + rendering)

Covers: color swatches on the shop/archive/category product **cards**, and
clicking/hovering a swatch to change the card's thumbnail image.

## Entry point: `woodmart_swatches_list()`

Called from every card-layout template (`content-product-base.php` and its
~11 siblings) inside:

```16:26:wp-content/themes/woodmart/woocommerce/content-product-base.php
		<?php
		if ( 'no' === woodmart_loop_prop( 'grid_gallery' ) || ! woodmart_loop_prop( 'grid_gallery' ) ) {
			woodmart_hover_image();
		}
		?>

		<div class="wrapp-swatches"><?php echo woodmart_get_thumbnails_gallery_pagin(); ?><?php echo woodmart_swatches_list(); ?><?php woodmart_add_to_compare_loop_btn(); ?></div>
```

`woodmart_swatches_list( $attribute_name = false )` (in `swatches.php`):

1. Bails out (`return false`) unless: current global `$product` is a
   `variable` product, quick-shop is enabled for this loop
   (`woodmart_loop_prop('show_quick_shop', true)`), and either an attribute
   name was resolved (see below) or the "quick shop type" is
   `variation_form`.
2. Resolves which attribute to show via `woodmart_grid_swatches_attribute()`
   if no `$attribute_name` argument was passed (see file `02` §3 for the
   per-product-override + theme-setting fallback).
3. Loads `$product->get_available_variations()`, cached in a transient
   `woodmart_swatches_cache_{product_id}` (see file `02` §7 — disabled on
   this site by the child theme).
4. Branches on Theme Setting `quick_shop_variable_type`:
   - `select_options` (default) → `woodmart_swatches_grid_template( $attribute_name, $available_variations )`.
   - `variation_form` → `woodmart_swatches_form_grid_template( $available_variations )`.

## Mode A: `select_options` — `woodmart_swatches_grid_template()`

Renders **one** `<div class="wd-swatches-grid wd-swatches-product wd-swatches-attr …">`
containing one `.wd-swatch` per term of the chosen attribute (in the
product's own term order via `wc_get_product_terms()`), each built with:

- `woodmart_get_option_variations( $attribute_name, $available_variations, false, $product_id )`
  — maps every term slug of that attribute present among the variations to
  `{ variation_id, is_in_stock, image_src?, image_srcset?, image_sizes? }`
  (image fields only populated `if variation image exists`), merged with
  `woodmart_has_swatch()`'s `{ color?, image?, not_dropdown? }`. This whole
  array is itself transient-cached as
  `woodmart_swatches_cache_{attribute_name}_{product_id}`.
- Per-swatch rendering priority (first match wins), exactly mirrored in the
  single-product template (file `04`):
  1. `color` term meta set → filled circle/square, class `wd-bg`.
  2. Theme Setting `swatches_use_variation_images` is on **and** this
     variation has its own featured image → render that image
     (`wp_get_attachment_image( get_post_thumbnail_id( $swatch['variation_id'] ), 'woocommerce_thumbnail' )`),
     class `wd-bg`.
  3. `image` term meta set → render that image, class `wd-bg`.
  4. Otherwise → text-only pill, class `wd-text` (shows the term name).
- Out-of-stock variations get class `variation-out-of-stock`.
- `data-image-src` / `data-image-srcset` / `data-image-sizes` attributes are
  written on the swatch `<div>` whenever the matched variation has its own
  image — **this is exactly the data `swatchesOnGrid.js` reads to swap the
  card's `<img>`** (see file 05 for CSS, this file's next section for JS).
- "Limit swatches" (Theme Setting `swatches_limit` / `swatches_limit_count`,
  default off / 5): once more terms exist than the limit (+1 buffer,
  filterable via `woodmart_show_more_limit_swatches_count`), a
  `<div class="wd-swatch-divider">+N</div>` is injected at the cut point and
  remaining swatches get class `wd-hidden` — expanded client-side by
  `swatchesLimit.js` (file `03` §"Limit swatches" below).
- CSS classes assembled onto the wrapper from the **per-attribute** admin
  settings (file `02` §2): `wd-bg-style-{swatch_style}`,
  `wd-text-style-{swatch_style}`, `wd-dis-style-{swatch_dis_style}`,
  `wd-size-{swatch_size}`, `wd-shape-{swatch_shape}`.

**Important limitation of this mode**: clicking a swatch here only changes
the card thumbnail image — it never selects a purchasable variation. To buy,
the customer must still click through to the single product page (or use
"Quick view"/"Quick shop" popup, which reuse the *single-product* swatches
form — file `04`).

## Mode B: `variation_form` — `woodmart_swatches_form_grid_template()`

Instead of the single-attribute list above, this renders the **entire**
`single-product/add-to-cart/variable.php` template (same one used on the
single product page — see file `04`) directly inside the card, with
`$is_quick_shop2` context flag (`isset($wd_swatches_limits)`) set, which:

- Shows swatches for **every** variation attribute (not just one).
- Either passes `$available_variations` inline (if
  `count($product->get_children()) <= ajax_variation_threshold` theme
  setting, default 30) or `false`, forcing `quickShopVariationForm.js` to
  lazy-AJAX-load them via `wp_ajax_woodmart_load_available_variations`
  (`quick-shop.php`) on first hover/click.
- Adds form class `wd-quick-shop-2` and `wd-clear-{quick_shop_clear_action}`.
- Wires add-to-cart directly on the card (see `quickShopVariationForm.js`
  below) — no navigation to the single product page needed.

## JS: `swatchesOnGrid.js` (Mode A image swap)

Registered on `document.ready` and on relevant Elementor widget-render
hooks. Delegated click handler on `.wd-swatches-grid .wd-swatch`:

- Reads `data-image-src`/`-srcset`/`-sizes` off the clicked swatch; bails if
  empty (swatch has no dedicated image, e.g. a color-only swatch whose
  variation doesn't have its own photo).
- Finds the card's `<img>` (`.product-image-link > img` or `> picture > img`)
  and caches its **original** `src`/`srcset`/`sizes` into jQuery `.data()`
  the first time (so later toggling off can restore them).
- Toggle behavior: clicking an already-`wd-active` swatch **deselects** it
  (restores original image, removes `.wd-active`/`.product-swatched`,
  fires `wdImagesGalleryInLoopOn`) — clicking a different/new swatch selects
  it (swaps to that swatch's image, adds `.wd-active`/`.product-swatched`,
  fires `wdImagesGalleryInLoopOff`).
- The `wdImagesGalleryInLoopOn`/`Off` custom events on the `.wd-product`
  element are how this coordinates with the **separate** "grid image
  gallery" hover feature (`imagesGalleryInLoop.js` — hovering pagination
  dots to preview other gallery photos): swatches "taking over" the image
  temporarily unbind the gallery-hover handlers so they don't fight over
  the same `<img>`, then rebind them when the swatch is deselected.
- Adds/removes `.wd-loading-image` class on the card while the new `<img>`
  loads (CSS-driven fade/skeleton, see file `05`).

## JS: `quickShopVariationForm.js` (Mode B — full form on card)

Bound on `mouseenter`/`touchstart`/`mousemove` of `.wd-product.product-type-variable`
(lazy per-card init, guarded by `.wd-variations-inited` class):

- Calls jQuery `.wc_variation_form()` (WooCommerce core) on the embedded
  `<form class="variations_form">`.
- On swatch click: same active/disabled pattern as the single-product JS
  (see file `04`) — sets the underlying `<select>` value and triggers
  `change`. First interaction triggers `loadVariations($form)` if
  `data('product_variations')` is `false` (AJAX-deferred case), which calls
  `wp_ajax_woodmart_load_available_variations` and then
  `.trigger('reload_product_variations')`.
- On WooCommerce's own `show_variation` event: swaps card `<img>`
  `src`/`srcset`/`sizes` to `variation.image.thumb_src`, updates
  `.price`, `.wd-product-stock`, `.wd-product-sku`, and the qty input's
  `min`/`max`, and enables the add-to-cart button (`updateProductImage()`).
- On `hide_variation` (deselect / incomplete selection): restores all of the
  above to their cached original values.
- The card's own **"Add to cart"** button click is intercepted: instead of
  navigating, it submits the embedded `variations_form` directly
  (`$form.trigger('submit')`), giving true "buy from the grid" behavior.
- `wd-clear-double` form class (Theme Setting `quick_shop_clear_action =
  double`) makes clicking an already-active swatch **deselect** it (rather
  than being a no-op), restoring the gallery-hover feature via
  `wdImagesGalleryInLoopOn`.

## Limit-swatches expansion — `swatchesLimit.js`

Delegated click on `.wd-swatch-divider` (the "+N" pill) or on any swatch
inside a not-yet-expanded `.wd-swatches-product:not(.wd-all-shown)`:
removes `.wd-hidden` from all swatches in that group and marks the group
`.wd-all-shown` (works for both grid and single-product contexts, and
triggers a masonry `.isotope('layout')` relayout if the grid uses masonry).

**QA note — `wd-all-shown` is never server-rendered.** Grepping
`swatches.php` and `variable.php` confirms the PHP only ever emits
`wd-swatches-limited` on the wrapper when the "Limit swatches" setting
trims a long term list (file `02` §2 / `06`); `wd-all-shown` is added
**exclusively client-side** by `swatchesLimit.js` above, once the "+N"
divider (or a swatch inside a collapsed group) is clicked. If you inspect
a live product card and see `wd-all-shown` already present, it means either
the limiter never actually triggered for that attribute (term count ≤
limit — though in that case the class simply wouldn't be added at all,
`wd-swatches-limited` would just be absent too) or, more likely on this
site, the child theme's hover-to-select customization (file `07`)
synthetically fires a swatch click on hover, which happens to bubble into
this same delegated handler. **Treat `wd-all-shown` as a UI *state*, not a
style variant to implement** — the rebuild's default behavior should simply
be "show all swatches, no collapsing" unless the "Limit swatches" toggle is
deliberately carried over.

**DB-confirmed**: `swatches_limit` is actually **on** on this site, with
`swatches_limit_count = 3` — any product with more than 3 colors *will*
show the `+N` divider and start collapsed on the grid. So `wd-all-shown`
in a captured class list is consistent with genuine user interaction
(including the child theme's hover-triggered synthetic click) on a product
with >3 colors. Decide explicitly whether the rebuild should carry over
this 3-swatch collapsing behavior or default to always-expanded.

**QA note — confirmed live style values on this site.** The captured
product-card class list (`wd-bg-style-3 wd-text-style-3 wd-dis-style-3
wd-size-m wd-shape-round`) decodes directly from the attribute-level admin
settings (file `02` §2) via the assembly code below — i.e. this attribute
has `swatch_style = '3'`, `swatch_dis_style = '3'`, `swatch_size = 'm'`,
`swatch_shape = 'round'` configured on **Products → Attributes → Edit**.
Per file `05`, style 3 = plain 1px border (shifts to gray-500 on
hover/active), disabled-style 3 = 40% opacity + one gray diagonal line. QA
has scoped the rebuild to hardcode exactly this one combination rather than
the full 4-style × 3-disabled-style × 3-shape × 6-size matrix Woodmart
supports (see file `09` §3):

```380:387:wp-content/themes/woodmart/inc/integrations/woocommerce/modules/swatches.php
$wrapper_class .= ' wd-bg-style-' . $swatch_style;
$wrapper_class .= ' wd-text-style-' . $swatch_style;
$wrapper_class .= ' wd-dis-style-' . $swatch_dis_style;
$wrapper_class .= ' wd-size-' . $swatch_size;
$wrapper_class .= ' wd-shape-' . $swatch_shape;
...
$out .= '<div class="wd-swatches-grid wd-swatches-product wd-swatches-attr' . esc_attr( $wrapper_class ) . '">';
```

## Adjacent, non-swatch grid feature: "grid image gallery" hover

`woodmart_template_loop_product_thumbnails_gallery()` (in
`inc/integrations/woocommerce/functions.php`) is a **separate** feature
(Theme Setting `grid_gallery`) that renders small pagination dots
(`woodmart_get_thumbnails_gallery_pagin()`) plus hidden `.wd-product-grid-slide`
divs for *all* of a product's own gallery images (not swatches/variations).
`imagesGalleryInLoop.js` swaps the card image on hover/arrow-click through
these slides. It's included here only because it **shares the same
`<img>` element** as the swatch-driven image swap and the two features
explicitly hand control back and forth via the `wdImagesGalleryInLoopOn`/
`wdImagesGalleryInLoopOff` events described above — a rebuild must
replicate this coordination or the two hover/click image-swap behaviors
will visibly conflict (flicker / wrong image winning).
