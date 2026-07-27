# 04 — Single Product Page: Swatches + Gallery Linkage

Covers: color swatches on the single product page's variation form, and how
selecting a swatch updates the main image gallery / thumbnail rail.

## Template: `woocommerce/single-product/add-to-cart/variable.php`

This is WooCommerce core's own `variable.php` template, patched in place by
Woodmart (marked with `// start/end Woodmart code` comments) rather than
using a separate render path. It is used, with different context flags, in
**four** places:

| Context | How it gets here | Key flags set |
|---|---|---|
| Single product page | Normal WooCommerce `woocommerce_template_single_add_to_cart()` call | `$is_quick_shop2` false, `$is_single_product` true |
| Quick View popup | `quick-view.php` module, `$is_quick_view = true` via `woodmart_loop_prop` | `wd-reset-side-lg` etc. form classes |
| Quick Shop popup (`quick_shop_variable_type = select_options` mode) | `quick-shop.php` → `woocommerce_template_single_add_to_cart()` | `$is_quick_shop` true (detected via `$_REQUEST['action'] === 'woodmart_quick_shop'`) |
| Grid "variation form" quick shop (`quick_shop_variable_type = variation_form` mode) | `woodmart_swatches_form_grid_template()` passes `wd_swatches_limits` | `$is_quick_shop2` true, `$is_single_product` false, form gets class `wd-quick-shop-2` |

For each product attribute row (`foreach ( $attributes as $attribute_name => $options )`):

1. `woodmart_has_swatches( $product->get_id(), $attribute_name, $options, $available_variations, $swatches_use_variation_images )`
   returns the swatch data array (color/image/variation-image) for every
   option value of this attribute (same helper used by the grid, file `03`).
2. If non-empty, renders `<div class="wd-swatches-product wd-swatches-single …" data-id="{sanitized_attr_name}" role="radiogroup">`
   containing one swatch `<div class="wd-swatch" data-value="{term_slug}" data-title="{term_name}" role="radio">`
   per term (in taxonomy order filtered to the product's own `$options`).
   Same color→variation-image→image→text priority as the grid (file `03`).
   Adds `.wd-active` if this term matches the current `$selected_value`
   (from `$_REQUEST['attribute_{name}']`, `$selected_attributes` default
   attrs, or `''` for quick-shop-2). Adds `.wd-enabled`/`.wd-disabled` when
   WooCommerce's global "hide out of stock" setting is on.
3. **Immediately after** the swatches div, the **original** WooCommerce
   `wc_dropdown_variation_attribute_options()` `<select>` is *still*
   rendered (hidden by CSS — `.wd-swatches-product+select{display:none}` in
   `woo-mod-swatches-base.min.css`) — **the swatches are a visual/UX layer
   on top of the real `<select>`, not a replacement for it.** All WooCommerce
   variation-matching logic (stock, price, `found_variation`, availability)
   continues to run off this real, hidden `<select>`; swatch clicks just
   proxy into it (`$select.val(value).trigger('change')` — see JS below).
   If the attribute's admin setting `change_image` is `on`, this `<select>`
   additionally gets class `wd-changes-variation-image`.
4. "Limit swatches on single product" (Theme Setting
   `single_product_swatches_limit` / `_count`, default off / 10) works
   identically to the grid version (file `03`), but only applies to the
   **first** attribute row unless `$is_single_product` (in which case every
   row can be limited).

## JS: `swatchesVariations.js` — the main single-product wiring

Initialized on `document.ready`, on Elementor `add_to_cart` widget render,
and re-run on custom events `wdQuickShopSuccess` / `wdQuickViewOpen` /
`wdUpdateWishlist` (so popups get the same behavior). Iterates every
`.variations_form` on the page (guarded by `.data('swatches')` /
`.wd-quick-shop-2` class so it's not double-bound with the grid-form script).

Key event bindings on `.variations_form`:

- **`click`/`keydown` on `.wd-swatches-single > .wd-swatch`**: reads
  `data-value` + the row's `data-id`, finds the matching real
  `<select id="{data-id}">`, sets its value and triggers `change` (this is
  what actually drives WooCommerce's variation matching — `found_variation`,
  `show_variation`, price/stock updates, etc., all still happen through
  WooCommerce core). Toggles `.wd-active` on the clicked swatch. Also
  supports keyboard activation (`Enter`/`Space`) since swatches use
  `role="radio"`.
- **`found_variation`** (WooCommerce core event, fires once a full
  attribute combination resolves to a real variation):
  - If AJAX-loaded data (`useAjax`, quick-shop-2 case), calls
    `replaceMainGallery(variation.variation_id, $form, variation)` directly
    with the AJAX payload.
  - Otherwise, if the "additional variation images" gallery-replace path
    doesn't apply (`!replaceMainGallery(...)`), falls back to updating just
    the **first thumbnail** (`wc_set_variation_attr`) — WooCommerce's
    lighter-weight default behavior.
  - Calls `scrollToTop()` if Theme Settings
    `swatches_scroll_top_desktop`/`_mobile` are enabled and the new
    variation's full image URL differs from the current one (animates
    `html, body` scroll to `.woocommerce-product-gallery__wrapper`, `-150px`
    offset, over 800ms — hidden if quick-shop/quick-view context).
  - Re-runs the main Swiper carousel's `.update()` + `.slideTo(0)` if it's a
    `.wd-carousel`.
- **`show_variation`**: re-syncs `.wd-active` swatch state after a page
  reload (Firefox-specific fix), and calls `showSelectedAttr()` — when Theme
  Setting `swatches_labels_name` is on (or on narrow screens), replaces the
  swatch's hover tooltip with an inline text label next to the attribute
  title (`<span class="wd-attr-selected">`).
- **`reset_data`** (WooCommerce core event on clear/incomplete selection):
  removes `.wd-active`, calls `replaceMainGallery('default', $form)` to put
  the gallery back to the product's own default images (unless a
  `wd-changes-variation-image` select still holds a value), resets the
  carousel to slide 0.
- **`.reset_variations` click** (the "Clear" link): removes all
  `.wd-active`, clears the selected-option label.
- **`resetSwatches()`** (internal helper, run on most of the above events):
  walks every `<select>` in the form and, for each swatch, adds
  `.wd-enabled`/`.wd-disabled` based on whether the corresponding
  `<option>` in the live `<select>` currently has WooCommerce's own
  `enabled` class (i.e. is a valid combination given the other selected
  attributes) — this is how invalid/incompatible swatch combinations get
  visually greyed out and unclickable.
- **`select.wd-changes-variation-image` `change`** handler: an
  **independent** image-swap path (separate from swatch clicks) for the
  attribute-level "Change product image on attribute click" admin setting
  (file `02` §2, `_change_image`). Fires only once *every* select in the
  form has a value; swaps the main gallery via
  `$form.wc_variations_image_update(variation)` (WooCommerce core) and/or
  `replaceMainGallery`, plus caches the very first main image's
  `src`/`srcset` the first time so a later reset can restore it.

## Gallery replacement — `replaceMainGallery()` / `isVariationGallery()`

This is the mechanism that satisfies "the images grid is also linked to the
swatches" — i.e. selecting a variation swaps **the entire gallery**
(multiple thumbnails/slides), not just the single visible image.

- Branches on Theme Setting `variation_gallery_storage_method` (read from
  the localized `woodmart_settings.variation_gallery_storage_method` JS
  var):
  - `'old'` → `isVariationGalleryOld(key)` / `replaceMainGalleryOld(key, $form)`,
    reading the **global** `window.woodmart_variation_gallery_data` (or
    `woodmart_qv_variation_gallery_data` in Quick View) object keyed by
    variation ID, localized server-side (file `02` §4b).
  - `'new'` (default) → `isVariationGalleryNew(key, $form)` /
    `replaceMainGalleryNew(imagesData, $form, key)`, reading
    `additional_variation_images` / `additional_variation_images_default`
    **embedded directly in the WooCommerce variation JSON**
    (`$form.data('product_variations')`), parsed by
    `getAdditionalVariationsImagesData()` (file `02` §4a).
- A gallery replacement only actually happens if the target key (variation
  ID, or `'default'`) has **more than 1 image** (`isVariationGallery`
  check) — i.e. a variation with just its single main image does *not*
  trigger this path; WooCommerce's normal single-image swap handles that
  case instead (see the `found_variation` fallback above).
- `key === 'default'` (resetting) only replaces the gallery if
  `variationGalleryReplace` is currently `true` — a flag that tracks
  "we've swapped away from default at least once" so an *initial* page
  load (already showing the default gallery) doesn't unnecessarily rebuild
  the DOM.
- Both `replaceMainGalleryOld`/`New` **empty and completely rebuild** the
  `.woocommerce-product-gallery__wrapper` (or its `.wd-carousel-wrap` if
  Swiper carousel mode is on) — appending one `<div class="wd-carousel-item">`
  per image, each an `<a href="{full_src}"><img …></a>` figure with
  `data-thumb="{thumbnail_src}"` (consumed by `product-thumbnails.php`'s
  thumb-rail carousel, which reads each main-gallery figure's `data-thumb`
  to build its own thumbnail images — see WooCommerce core /
  `product-thumbnails.php`).
- After replacement: destroys any active image-zoom plugin instance
  (`.trigger('zoom.destroy')`), fires `wdReplaceMainGallery` (+
  `wdReplaceMainGalleryNotQuickView` unless in Quick View), and triggers a
  window `resize` (so the Swiper carousel/lightbox re-measures).

## What a rebuild must reproduce, functionally

1. A **visible swatch layer** bound to a **real, hidden `<select>`** per
   attribute (don't replace WooCommerce's own variation-matching data
   model — proxy into it, exactly like Woodmart does). This keeps
   price/stock/`found_variation` logic "free" from WooCommerce core.
2. On `found_variation`/variation resolution: check whether the resolved
   variation (or the reset "default" state) has **more than one**
   associated image (main image + "additional variation images" from file
   `02` §4). If yes, **rebuild the whole gallery/thumbnail-rail markup**; if
   no, just swap the single current image (cheaper DOM update).
3. Swatch **disabled/enabled** state must reflect WooCommerce's own
   per-`<option>` `enabled`/invalid-combination classes, recomputed after
   every attribute change — not just a static "does this term exist"
   check.
4. Optional UX affordances that are configurable per-site and worth keeping
   configurable in the rebuild too: swatch size/shape/4 active styles/3
   disabled styles per attribute; "limit swatches + expand" for
   long attribute lists; "scroll to gallery on select" (desktop/mobile
   toggle); "show selected option name as text label" vs. tooltip.
