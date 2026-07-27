# 01 — Architecture Overview

## Where the code lives

All of the *core* swatches logic lives in the **parent** theme,
`wp-content/themes/woodmart/`. The **child** theme,
`wp-content/themes/woodmart-child/`, adds site-specific behavior on top (see
file `07`) but does not replace the core system.

> Note for future readers: `wp-content/themes/woodmart` and
> `wp-content/themes/woodmart-child` are listed in this repo's
> `.gitignore` (`wp-content/themes/*`), so standard `grep`/ripgrep-with-default-ignore
> tools (including the IDE's built-in search) silently skip them. Use
> `grep -rI` / `rg --no-ignore` or open files directly by path.

> **QA finding — one piece of swatch-adjacent registration lives outside
> the theme entirely.** Neither the theme nor the child theme calls
> `register_post_type()`, `register_taxonomy()`, or `register_meta()`
> anywhere for swatch data (verified by grepping both themes plus the
> `woodmart-core` plugin). The **one** exception: the "Linked Variations"
> custom post type `woodmart_woo_lv` (file `08` §A) is registered in the
> **`woodmart-core` plugin** (`wp-content/plugins/woodmart-core/class-post-types.php`),
> not the theme. If that plugin is deactivated alongside the theme and this
> site actually has any `woodmart_woo_lv` posts, that data becomes an
> orphaned/unregistered post type (rows remain in `wp_posts`/`wp_postmeta`,
> but no admin UI or CPT support exists for them without re-registering the
> post type). Everything else — `pa_*` attribute taxonomies (registered by
> WooCommerce core from the `wp_woocommerce_attribute_taxonomies` table) and
> all term meta / `wp_options` swatch data — has zero registration
> dependency on the theme and is unaffected by its removal.

### PHP — core logic

| File | Responsibility |
|---|---|
| `inc/integrations/woocommerce/modules/swatches.php` | The heart of the system. Registers the per-term "Extra" metabox (color/image/text-only/hint fields), and the functions that decide *if* a product has swatches and *what* to render: `woodmart_has_swatch()`, `woodmart_has_swatches()`, `woodmart_get_option_variations()`, `woodmart_swatches_list()`, `woodmart_swatches_grid_template()`, `woodmart_swatches_form_grid_template()`, `woodmart_have_product_swatches_template()`. Also owns a transient-based cache (`woodmart_swatches_cache_*`) invalidated on `save_post` / `woocommerce_after_product_object_save`. |
| `inc/integrations/woocommerce/modules/attributes-meta-boxes.php` | The **per-attribute admin settings** (swatch style/shape/size/disabled-style, "show label on product", "change image on click", attribute icon, attribute hint). These are saved as individual `wp_options` rows keyed by attribute name (NOT term meta — see `02-data-model-and-storage.md`). Also defines `woodmart_wc_get_attribute_term()`, the getter every other file uses to read these options. |
| `inc/integrations/woocommerce/modules/variation-gallery-new.php` | "Additional variation images" feature, **new/default storage method**. Lets admins attach extra gallery images to *each variation* (stored as post meta on the variation itself: `wd_additional_variation_images_data`). Exposes the data to the frontend via the `woocommerce_available_variation` filter (`additional_variation_images` / `additional_variation_images_default` keys added to WooCommerce's own variation JSON blob) so no separate AJAX call or localized script is needed. |
| `inc/integrations/woocommerce/modules/variation-gallery.php` | Same feature, **old/legacy storage method** (data stored as a single serialized array in *parent* product post meta: `woodmart_variation_gallery_data`). Data is pushed to the frontend via `wp_localize_script()` into a global JS var `woodmart_variation_gallery_data` (or `woodmart_qv_variation_gallery_data` for Quick View), rather than embedded per-variation. Which method is active is a theme setting (`variation_gallery_storage_method`, default `new`) — see file `06`. |
| `inc/integrations/woocommerce/modules/linked-variations/*` | A **different, related** feature: swatches that link between *separate* published products (not variations of one variable product) that share attribute values. Renders the same `.wd-swatch` markup but as `<a href>` tags to other product permalinks. See file `08`. |
| `inc/integrations/woocommerce/modules/quick-shop.php` / `quick-view.php` | AJAX endpoints (`wp_ajax_woodmart_quick_shop`, `wp_ajax_woodmart_load_available_variations`, `wp_ajax_woodmart_quick_view`) used by the "quick shop" and "quick view" popups, which both reuse the same swatches markup/JS. |
| `inc/admin/settings/shop.php` | Registers every swatches-related **Theme Settings → Shop** field (see file `06`). |
| `inc/integrations/woocommerce/modules/show-single-variations/*` | Unrelated-but-adjacent feature that can turn each variation into its own indexable/orderable "pseudo product" page; not part of swatches, mentioned for completeness only. |

### PHP — templates that *use* the above

| File | Responsibility |
|---|---|
| `woocommerce/content-product-base.php` (and its ~12 siblings: `content-product-alt.php`, `-button.php`, `-buttons-on-hover.php`, `-fw-button.php`, `-icons.php`, `-info.php`, `-info-alt.php`, `-list.php`, `-quick.php`, `-standard.php`, `-tiled.php`) | Every one of these product-card "hover style" templates calls `woodmart_swatches_list()` inside a `<div class="wrapp-swatches">`, overlaid on the product image. This is the single injection point for grid swatches regardless of which card layout/hover-effect is active. |
| `woocommerce/content-product.php` | Wraps the above; decides which `content-product-{hover}.php` partial to load, and adds the `product-no-swatches` class when `woodmart_have_product_swatches_template()` returns false (used for CSS to avoid reserving swatch-row space). |
| `woocommerce/single-product/add-to-cart/variable.php` | WooCommerce's own `variable.php` template, heavily patched by Woodmart. Renders the `<table class="variations">` with one `<div class="wd-swatches-product wd-swatches-single …">` per attribute row (single product page) **and** is reused, with different `$wd_swatches_limits`/context flags, for the grid "quick shop" variation form and for Quick View. |
| `woocommerce/single-product/product-image.php` | Renders the main image gallery (Swiper carousel) that swatch selection swaps images inside. |
| `woocommerce/single-product/product-thumbnails.php` | Renders the thumbnail-rail carousel kept in sync with the main gallery via `data-thumb`/`wc_set_variation_attr` calls from `swatchesVariations.js`. |

### JS — frontend behavior

All under `wp-content/themes/woodmart/js/scripts/wc/` (registered as handles
in `inc/configs/js-scripts.php`, each compiled to a matching `.min.js`):

| Script (handle) | File | Where it applies | What it does |
|---|---|---|---|
| `swatches-on-grid` | `swatchesOnGrid.js` | Product card, "simple swatch list" mode | Click a `.wd-swatch` inside `.wd-swatches-grid` → swap the card's `<img>` `src`/`srcset`/`sizes` to the swatch's `data-image-*` attributes. Toggling off restores the original image (cached in jQuery `.data('original-src')` the first time). Fires `wdImagesGalleryInLoopOn/Off` to coordinate with the separate "grid image gallery" hover feature (file `08`). |
| `quick-shop-with-form` | `quickShopVariationForm.js` | Product card, "full variation form" mode (`quick_shop_variable_type = variation_form`) | Lazily AJAX-loads `get_available_variations()` data on first hover/touch (`woodmart_load_available_variations` endpoint), then wires the embedded `.wd-swatch` clicks to the real `<select>` (`.trigger('change')`), listens to WooCommerce's `show_variation`/`hide_variation` events to swap the card image/price/stock/SKU, and restores everything on `hide_variation`. |
| `swatches-variations` | `swatchesVariations.js` | Single product page, Quick View, Quick Shop popup | The main single-product wiring. Handles swatch click → `<select>` value+change, `found_variation`/`show_variation`/`reset_data` handling, replacing the *entire* main gallery via the "additional variation images" data, scroll-to-gallery-on-select, and the "show selected option name" label mode. Full breakdown in file `04`. |
| `swatches-limit` | `swatchesLimit.js` | Both grid and single product, when "Limit swatches" is enabled | Clicking the "+N" divider (`.wd-swatch-divider`) reveals the remaining hidden `.wd-swatch` elements. |
| `image-gallery-in-loop` | `imagesGalleryInLoop.js` | Product card | A **separate, adjacent** feature (not swatches) that lets hovering/arrow-clicking a small pagination dot row swap the card image through the product's *own* gallery images. Coordinates with swatches via `wdImagesGalleryInLoopOn/Off` custom events so the two features don't fight over the same `<img>`. |

### CSS

All swatch CSS lives in the compiled theme stylesheet (`style.css` /
`style-elementor.css`, and their `-rtl` and `.min` variants), assembled at
build time from small named "parts" in `css/parts/woo-mod-swatches-*.min.css`
and `css/parts/woo-opt-limit-swatches.min.css`. These parts are conditionally
enqueued inline (`woodmart_enqueue_inline_style(...)`) only on pages that
actually render swatches, rather than being always-loaded. Full class
reference in file `05`.

## Two independent "modes" for grid swatches

This is the most important architectural fork to understand before a
rebuild. `woodmart_swatches_list()` (in `swatches.php`) branches into one of
two completely different rendering paths, controlled by the theme setting
**"Quick shop" type** (`quick_shop_variable_type`):

1. **`select_options` (default)** → `woodmart_swatches_grid_template()`
   — renders *only* the swatches for **one chosen attribute**
   (`grid_swatches_attribute` setting, e.g. always show the "Color"
   attribute's swatches, regardless of how many attributes the product
   actually has). Clicking a swatch here does **not** select a purchasable
   variation — it only swaps the product-card thumbnail image
   (`swatchesOnGrid.js`). Add-to-cart still goes through the normal
   "Select options" link → product page.
2. **`variation_form`** → `woodmart_swatches_form_grid_template()` — renders
   the **full** `variable.php` add-to-cart form (all attributes, all
   swatches) directly inside the card, AJAX-loading variation data on first
   interaction. This lets a customer add-to-cart *without leaving the grid*.
   Wired by `quickShopVariationForm.js`.

Both modes reuse the exact same swatch *markup builder* function
(`woodmart_has_swatch()` / the swatch-div output block in `variable.php`), so
the swatch HTML/classes look identical in both modes — only the surrounding
form and JS wiring differ.

## Data flow at a glance

```
Attribute taxonomy term (e.g. pa_color "Red")
  ├─ term meta: color / image / not_dropdown / pa_term_hint   (appearance + hint)
  └─ wp_options: woodmart_pa_color_swatch_style / _shape / _size / _dis_style
                  / _show_on_product / _change_image / _thumbnail / _hint
                                                              (styling, per attribute)
        │
        ▼
woodmart_has_swatch() / woodmart_has_swatches()   (swatches.php)
        │
        ├─▶ Grid:   woodmart_swatches_list() → *_grid_template() → swatchesOnGrid.js /
        │            quickShopVariationForm.js  →  swap <img> in .wd-product card
        │
        └─▶ Single: variable.php swatch markup  →  swatchesVariations.js
                       │
                       ├─▶ WooCommerce core: found_variation/show_variation → variation.image.*
                       │      → thumbnails carousel + main gallery single image
                       │
                       └─▶ Woodmart "additional variation images"
                              (variation-gallery-new.php / variation-gallery.php)
                              → replaces the ENTIRE main gallery carousel
                                (multiple slides), not just one image
```
