# 07 — `woodmart-child` Theme Overrides

The child theme (`wp-content/themes/woodmart-child/`) does **not** replace
Woodmart's swatch system — it layers site-specific behavior on top,
concentrated almost entirely in one giant `functions.php` (2223 lines).
Below are every swatch/gallery-relevant piece, in the order they appear.

## 1. Hover-to-select swatches on product cards (`woodmart_child_add_swatch_hover`)

```181:213:wp-content/themes/woodmart-child/functions.php
function woodmart_child_add_swatch_hover() {
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            var isTouchDevice = ("ontouchstart" in window || navigator.maxTouchPoints > 0);
            if (!isTouchDevice) {
                $(document).on("mouseenter", ".product-grid-item .wd-swatch, .product-grid-item .woodmart-swatch, .product-grid-item .swatch-on-grid, .product-teaser .wd-swatch, .product-teaser .woodmart-swatch, .product-teaser .swatch-on-grid", function() {
                    var $this = $(this);
                    if (!$this.hasClass("active-swatch") && !$this.hasClass("current-swatch")) {
                        $this.trigger("click");
                        $this.addClass("hover-selected");
                        setTimeout(function() {
                            $(document).trigger("woodmart-swatch-selected", [$this]);
                        }, 100);
                    }
                });
                $(document).on("mouseleave", ".product-grid-item, .product-teaser", function() {
                    $(this).find(".hover-selected").removeClass("hover-selected");
                });
            }
        });
    ', 'after');
}
add_action('wp_enqueue_scripts', 'woodmart_child_add_swatch_hover', 99);
```

- Injected as an inline script attached to the `jquery` handle (runs after
  jQuery loads, before Woodmart's own scripts necessarily finish — relies
  on event delegation via `$(document).on(...)` so binding order doesn't
  matter).
- Detects touch capability (`ontouchstart` / `maxTouchPoints`) and **only**
  activates on non-touch (desktop/mouse) devices — touch devices keep
  Woodmart's stock click-only behavior.
- On `mouseenter` of any `.wd-swatch`/`.woodmart-swatch`/`.swatch-on-grid`
  inside a `.product-grid-item` or `.product-teaser` (note: `.product-teaser`
  is not a class seen elsewhere in this research — likely a WP Grid Builder
  or Blokki-equivalent card wrapper class used on this site's actual grid
  markup; verify it's still present if grid markup changes in the rebuild),
  **synthetically triggers a `click`** on the swatch — i.e. hover on
  desktop behaves exactly like clicking (which itself triggers whichever
  vanilla-Woodmart click handler is bound: `swatchesOnGrid.js` in
  `select_options` mode, or `quickShopVariationForm.js` in
  `variation_form` mode).
- Note this handler does **not** implement its own image-swap logic — it
  purely simulates a click and lets Woodmart's native `swatchesOnGrid.js`
  (or `quickShopVariationForm.js`) do the actual work. **A rebuild that
  reimplements swatch click-to-swap-image natively should add this same
  hover-triggers-click behavior as a thin wrapper**, rather than
  duplicating image-swap logic in two places.
- `mouseleave` on the card cleans up a `.hover-selected` marker class (used
  only by this snippet itself, not consumed by any Woodmart core JS).

## 2. Single-product swatch/limit scripts explicitly (re-)enqueued for Quick View

`woodmart_quick_view_btn()` is **fully redefined** in the child theme
(function-exists-guarded override is *not* used here — the child's
`functions.php` loads before the parent's, so this simply wins outright)
to add explicit script/style enqueues not present, or present differently,
in the vanilla theme's version — notably:

```312:329:wp-content/themes/woodmart-child/functions.php
	woodmart_enqueue_js_library( 'swiper' );
	woodmart_enqueue_js_script( 'swiper-carousel' );
	woodmart_enqueue_js_library( 'magnific' );
	woodmart_enqueue_inline_style( 'mfp-popup' );
	woodmart_enqueue_js_script( 'product-images-gallery' );
	woodmart_enqueue_js_script( 'quick-view' );
	woodmart_enqueue_js_library( 'tooltips' );
	woodmart_enqueue_js_script( 'btns-tooltips' );
	woodmart_enqueue_js_script( 'swatches-variations' );
	woodmart_enqueue_js_script( 'add-to-cart-all-types' );
	woodmart_enqueue_js_script( 'woocommerce-quantity' );
	wp_enqueue_script( 'wc-add-to-cart-variation' );
	wp_enqueue_script( 'imagesloaded' );

	if ( woodmart_get_opt( 'single_product_swatches_limit' ) ) {
		woodmart_enqueue_js_script( 'swatches-limit' );
	}
	if ( woodmart_get_opt( 'single_product_variations_price' ) ) {
		woodmart_enqueue_js_script( 'variations-price' );
	}
```
Functionally this just guarantees `swatches-variations`/`swatches-limit`/
`btns-tooltips` (tooltips library, used by `.wd-tooltip` swatch hover-hints)
are loaded whenever the Quick View button is rendered — a defensive
belt-and-suspenders enqueue, not a behavioral change vs. stock Woodmart.

## 3. `single-product/product-image.php` override — currently a no-op passthrough

```348:356:wp-content/themes/woodmart-child/functions.php
add_filter('wc_get_template', function($template, $template_name, $args, $default_path) {
    if ($template_name === 'single-product/product-image.php') {
        $new_template = get_stylesheet_directory() . '/woocommerce/single-product/product-image.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}, 100, 4);
```
The child theme **does** have its own copy at
`wp-content/themes/woodmart-child/woocommerce/single-product/product-image.php`,
but at last inspection its contents were a placeholder/no-op (effectively
delegating back to the parent's markup) — **verify this hasn't changed**
before assuming the parent's `product-image.php` (documented in file `01`)
is the live template; if the child's file has since been filled in with
real markup, that supersedes the parent version for gallery structure.

**Related, higher-priority open question**: even if this specific
WooCommerce template filter is a no-op, the Jet Woo Product Gallery
plugin's "Slider" widget is confirmed enabled in `wp_options` (file `10`
§3) — if a JetWooBuilder/Elementor single-product template override
renders the gallery through that plugin's widget instead of calling
`woocommerce_template_single_add_to_cart()`/the native gallery template at
all, this filter would never even fire on the live page. Resolve file `10`
§3's open question first; it supersedes this one.

## 4. Gallery image count padding — `woocommerce_product_get_gallery_image_ids` filter

```359:402:wp-content/themes/woodmart-child/functions.php
add_filter('woocommerce_product_get_gallery_image_ids', function($gallery, $product) {
    if ($product->is_type('variable')) {
        if (!empty($gallery) && count($gallery) >= 8) {
            return $gallery;
        }
        $variation_images = [];
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation || !is_object($variation)) {
                continue;
            }
            $variation_image_id = $variation->get_image_id();
            if ($variation_image_id) {
                $variation_images[] = $variation_image_id;
            }
            $variation_gallery = get_post_meta($variation_id, '_wc_additional_variation_images', true);
            if (!empty($variation_gallery) && is_array($variation_gallery)) {
                $variation_images = array_merge($variation_images, $variation_gallery);
            }
        }
        $final_images = !empty($gallery) ? $gallery : [];
        $final_images = array_merge($final_images, $variation_images);
        while (count($final_images) < 8 && !empty($final_images)) {
            $final_images[] = $final_images[count($final_images) % count($final_images)];
        }
        return array_unique($final_images);
    }
    return $gallery;
}, 10, 2);
```

- Hooks WooCommerce core's own `WC_Product::get_gallery_image_ids()`
  getter (**not** Woodmart's variation-gallery system) — this changes what
  `$product->get_gallery_image_ids()` returns *everywhere* it's called,
  including inside Woodmart's own `woodmart_avi_get_default_data()` (file
  `02` §4a), so the "default" (no variation selected) gallery is itself
  affected by this filter.
- For variable products with fewer than 8 gallery images: pulls in each
  variation's own featured image (`$variation->get_image_id()`) **plus**
  images from meta key **`_wc_additional_variation_images`** on each
  variation, then **pads the array by duplicating existing entries** (not
  by fetching more real images) until at least 8 total image IDs exist,
  then dedupes with `array_unique()` (which, combined with the padding
  loop, means the final count can actually end up **below** 8 again after
  dedup — a likely minor bug/quirk worth being aware of, not necessarily
  worth reproducing exactly).
- **Data-model discrepancy — resolved.** The meta key read here,
  `_wc_additional_variation_images`, does **not** match either of
  Woodmart's own two variation-gallery storage keys
  (`wd_additional_variation_images_data` new-method, or
  `woodmart_variation_gallery_data` old-method — file `02` §4), and no
  code anywhere in this codebase *writes* to it. **DB-confirmed dead**:
  `SELECT COUNT(*) FROM wp_postmeta WHERE meta_key =
  '_wc_additional_variation_images'` returns **zero** rows. This branch is
  fully inert and can be ignored in the rebuild (the padding-by-duplication
  behavior driven by `$variation->get_image_id()` alone still applies,
  however, since that part doesn't depend on this dead meta key).

## 5. Swatches transient cache explicitly disabled

```2171:2188:wp-content/themes/woodmart-child/functions.php
add_action('muplugins_loaded', function() {
    add_filter('pre_set_transient', function($value, $transient, $expiration) {
        if (strpos($transient, 'woodmart_swatches_cache') !== false) {
            return false;
        }
        if (strpos($transient, 'wc_layered_nav_counts') !== false) {
            return false;
        }
        if (strpos($transient, 'wc_var_prices') !== false) {
            return false;
        }
        return $value;
    }, 999, 3);
}, 1);

add_filter( 'woodmart_swatches_cache', '__return_false' );
add_filter( 'woocommerce_layered_nav_count_maybe_cache', '__return_false' );
```
Belt-and-suspenders double-disable: the `woodmart_swatches_cache` filter
(consumed directly by `swatches.php`'s `apply_filters('woodmart_swatches_cache', true)`
checks, file `01`/`02` §7) turns off caching logic entirely, **and** a
low-level `pre_set_transient` filter additionally blocks the transient from
ever being written even if some code path ignored the filter. Practical
implication for the rebuild: **on this live site, swatch/variation data is
always computed fresh on every request** — there is no stale-cache
correctness concern to reproduce, but there may be a performance
consideration if a rebuild is much slower per-computation than Woodmart's
(cheap) transient lookups would have been.

## 6. "Parts" custom color-variation switcher (`custom_variation_image_switcher`)

**Resolved — see new file `10` for the full data model and frontend
template.** The largest and most bespoke piece of child-theme logic — a
**completely separate, hand-rolled swatch/variation system** that only
activates inside a specific page section (class `.custom-Parts-section`),
an Elementor Nested Tabs panel used on product pages that have "parts"
(accessories/components — e.g. a chair's replaceable arms/legs/cushions,
each its own full WooCommerce product) related via a JetEngine
relationship field, with their own color swatches, entirely decoupled from
the main product's own variation form and gallery. What follows here is
the *JS behavior*; file `10` §§1–2 has the *data model* (`parts` post
meta) and the *markup source* (a JetEngine Listing Grid + Elementor
template that embeds one full WooCommerce add-to-cart form — and hence one
set of real `.wd-swatch` elements — per related part product).

```1094:1408:wp-content/themes/woodmart-child/functions.php
function custom_variation_image_switcher() {
    if (!is_product()) return;
    add_action('wp_footer', function() {
        ?><script> … </script><?php
    });
}
add_action('template_redirect', 'custom_variation_image_switcher');
```

Key behaviors (full inline `<script>` reproduced in file's own comments —
see the child theme source for exact code):

- **Activation gating**: only initializes once the "Parts" tab
  (`button.e-n-tab-title[data-tab-index="5"]`, **Elementor Pro's native
  Nested Tabs widget** — confirmed not `jet-tabs`, which was checked
  separately and found to have zero options/posts, i.e. unused) becomes
  active — detected via click listener, Elementor's own
  `elementor/tabs/show` event, an on-load check, **and** a 1-second polling
  interval (cleared after 10s) as a defensive fallback. The markup for
  `.custom-Parts-section` itself is not in this theme's PHP templates —
  **it's the JetEngine Listing Grid + Elementor template identified in
  file `10` §2** (post title "Spare Parts Listing - On the Single Product
  Page"), which is presumably embedded as one panel of this Nested Tabs
  widget on relevant product page(s).
- **Global click-blocking**: once initialized, *every* `.wd-swatch` click
  inside `.custom-Parts-section` is intercepted at the document level
  (`e.stopPropagation(); e.preventDefault(); return false;`) — meaning
  Woodmart's own `swatchesVariations.js`/`quickShopVariationForm.js`
  handlers for these particular swatches are effectively neutralized
  system-wide once a Parts tab has been opened once on the page (the
  document-level delegated handler persists for the rest of the page's
  life, not just while the tab is visible).
- **Per-section init** (`initSection($section)`): finds the section's own
  `.variations_form`, `.wd-swatch` swatches, a candidate image element
  (first of `.elementor-widget-theme-post-featured-image img`,
  `.partsGalleryImage img`, or `.elementor-widget-image img`), and a
  `.parts-price` element. Skips entirely if any of these are missing.
- **Swatch click → `handleSwatchSelection()`**: parses
  `$form.attr('data-product_variations')` (the same JSON WooCommerce/
  Woodmart embed on any `variations_form`) as raw JSON, finds the
  variation whose `attributes.attribute_pa_colour` **or**
  `attributes.attribute_pa_color` matches the clicked swatch's
  `data-value` (hardcoded to only these two attribute-name spellings —
  British "colour" vs. American "color" — presumably because this site's
  actual "Parts" attribute taxonomy is named one of these two ways
  inconsistently; **verify actual taxonomy name(s) in use** before
  assuming other attribute names are supported here), then manually
  updates: the section's own image `src`/`srcset`, the section's own price
  HTML, `.wd-active`/`.selected-swatch` classes, and (via
  `updateFormForCart()`) the hidden `<select>`'s value, the add-to-cart
  button's disabled state, and the form's `variation_id` hidden input —
  i.e. it **reimplements** WooCommerce's variation-resolution logic by
  hand instead of triggering WooCommerce's own `change`/`found_variation`
  pipeline, specifically so it does **not** disturb the main product's own
  gallery/variation state.
- **Auto-selects the first swatch** 300ms after section init
  (`$swatches.first().trigger('click' + eventNamespace)`), so a "Parts"
  section always starts with a color pre-selected rather than blank.
- **`protectMainGallery()`**: the mechanism ensuring the "Parts" section's
  own swatch-driven image swapping never leaks into the main product
  gallery. Runs once (`mainGalleryProtected` flag):
  - Caches every `<img>` inside `.woocommerce-product-gallery`'s current
    `src`/`srcset` into jQuery `.data()`.
  - Installs a `MutationObserver` on those same `<img>` elements
    (`attributes: true, subtree: true`) that **reverts** any `src`/`srcset`
    change back to the cached original value, for the lifetime of the
    page. This is a blunt-force safety net against any stray code (this
    script's own bugs, or a WooCommerce/Woodmart event firing unexpectedly)
    touching the main gallery images.
  - Also monkey-patches `$.fn.wc_variations_image_update` (WooCommerce
    core's own image-update jQuery plugin function) so that calls
    originating from *outside* `.custom-Parts-section` still work
    normally, but any call whose `this` context is inside
    `.custom-Parts-section` is silently ignored — a second, more targeted
    safety net at the API level rather than the DOM level.
- **`resetSection()`** (bound to `.reset_variations` clicks inside the
  section): restores the section's own cached original image/price, clears
  swatch active state, disables add-to-cart, clears the `variation_id`
  input.

### Rebuild implication

This "Parts" feature is a **fully separate, second swatch system**
layered on top of the WooCommerce/Woodmart one, scoped to a specific class
(`.custom-Parts-section`) generated by a JetEngine Listing Grid + Elementor
template (file `10` §2), not any PHP template in this repo. Before
replicating it in a new theme:
1. ~~Confirm on the live site which product pages actually use a "Parts"
   tab/section, and capture their exact HTML structure~~ — **resolved**,
   see file `10` §§1–2 for the data model (`parts` relational post meta)
   and template structure (image/title/price/link/embedded add-to-cart
   form per related part).
2. The real global attribute taxonomy is confirmed `pa_colour` (file `02`'s
   top note) — the JS's fallback check for `attribute_pa_color` too is
   likely just defensive/leftover coding rather than evidence of a second,
   differently-named taxonomy actually in use; verify there's no second
   color attribute before assuming this fallback is dead, but treat
   `pa_colour` as authoritative.
3. Decide whether this bespoke, event-blocking approach is still desired,
   or whether the new theme's "Parts" swatches should instead use a
   properly scoped/namespaced version of the *standard* single-product
   swatch wiring (file `04`) against a **second, independent**
   `variations_form` for the Parts product — which would remove the need
   for the aggressive global click-blocking and `MutationObserver`
   gallery-protection hacks entirely, since two independent forms
   naturally wouldn't interfere with each other's galleries as long as
   each targets its own image element (which, per `initSection()`'s image
   lookup, they already do).
