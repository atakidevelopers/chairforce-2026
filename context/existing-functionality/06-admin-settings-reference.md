# 06 — Admin Settings Reference

All settings below are registered via `XTS\Admin\Modules\Options::add_field()`
in `wp-content/themes/woodmart/inc/admin/settings/shop.php`, under
**Theme Settings → Shop → Variable products** section. They're read at
runtime via `woodmart_get_opt( '{id}', $default )`, which resolves to a
single serialized theme-options array stored in `wp_options` (Woodmart's own
options framework — not individual `wp_options` rows per setting, unlike the
per-attribute swatch settings in file `02` §2 which *are* individual rows).

A rebuild's admin UI does not need to replicate Woodmart's exact options
framework, but should expose equivalent controls (or hardcode sane
equivalents) for every setting below, since site content/behavior may
depend on the currently-configured values on this specific install.

**QA note — this is exactly the data that disappears functionally when
Woodmart is removed.** Because every setting on this page is bundled into
one serialized `wp_options` row (`xts-woodmart-options`) rather than
individual rows, and because `woodmart_get_opt()` itself is theme code,
none of these settings have any independent existence once the theme is
gone — unlike the per-term swatch colors (file `02` §1) or the per-attribute
style settings (file `02` §2), which are plain WordPress term meta /
individual `wp_options` rows with no dependency on Woodmart's code to be
readable. Practical takeaway: don't design the rebuild around "reading
Woodmart's existing settings" for anything in this file — read the *current
values* once (live-site check, file `09` §0.6) to pick sensible hardcoded
defaults or a much smaller new settings UI, then treat Woodmart's copies as
dead.

## Group: "Quick shop"

| Setting id | Type | Default | **Live value on this site** | Purpose |
|---|---|---|---|---|
| `quick_shop_variable` | switcher | `'1'` (on) | `1` (on) | Master toggle: allow variable products to be purchased directly from the shop grid without visiting the product page. |
| `quick_shop_variable_type` | buttons: `select_options` \| `variation_form` | `select_options` | **`select_options`** | **The single most important fork for grid swatches** (file `03`). `select_options`: swatches only preview one attribute's images, "Select options" still links to the product page. `variation_form`: full add-to-cart form embedded in the card. Confirmed: this site only ever uses `select_options` (Mode A) — the full embedded variation-form grid mode (Mode B) is never active, so `quickShopVariationForm.js`'s behavior doesn't need replicating. |
| `quick_shop_clear_action` | buttons: `none` \| `btn` \| `double` | `none` | `none` | Only relevant when `quick_shop_variable_type = variation_form`, which isn't active here — moot. |

## Group: "Attribute swatches"

| Setting id | Type | Default | **Live value on this site** | Purpose |
|---|---|---|---|---|
| `grid_swatches_attribute` | select (populated from `wc_get_attribute_taxonomies()`) | none/empty | **`pa_colour`** | Which single attribute's swatches show on the grid in `select_options` mode. Per-product override: post meta `_woodmart_swatches_attribute` (file `02` §3). |
| `swatches_limit` | switcher | off | **on** | Enable "+N" collapsing of long swatch lists on the **grid**. |
| `swatches_limit_count` | range 1–20 | `5` | **`3`** | How many grid swatches show before collapsing. |
| `single_product_swatches_limit` | switcher | off | **off** | Same, but for the **single product page**. |
| `single_product_swatches_limit_count` | range 1–30 | `10` | (moot, limit is off) | How many single-product swatches show before collapsing. |
| `swatches_use_variation_images` | switcher | off | **off** | When on, swatches render using each **variation's own featured image** (falls back to term `color`/`image` meta if a variation lacks one) instead of always using the attribute term's configured swatch image. Confirmed off — swatches always use term `color`/`image` meta, never variation featured images. |
| `swatches_labels_name` | switcher | off | **on** | Replace the swatch hover tooltip with a persistent text label (`.wd-attr-selected`) next to the attribute title, shown on desktop/tablet (always shown on mobile regardless of this setting, per the JS condition `windowWidth <= 768`). |
| `swatches_scroll_top_desktop` | switcher (device tab: Desktop) | off | **off** | Auto-scroll the page up to the gallery when a swatch with a different image is selected — desktop (`window width >= 1024`). |
| `swatches_scroll_top_mobile` | switcher (device tab: Mobile) | off | **off** | Same, mobile (`window width <= 1024`). |

## Group: "Variations images" (the "additional variation images" / mini-gallery-per-variation feature)

| Setting id | Type | Default | **Live value on this site** | Purpose |
|---|---|---|---|---|
| `variation_gallery` | switcher | `'1'` (on) | **on** | Master toggle for the whole "additional variation images" feature (file `02` §4). When off, selecting a variation only ever swaps the single current image via WooCommerce's own `variation.image`, never a whole gallery. |
| `variation_gallery_storage_method` | buttons: `new` ("Variations products meta") \| `old` ("Parent product meta (deprecated)") | `new` | **`new`, DB-confirmed** (1,023 non-empty `wd_additional_variation_images_data` rows vs. 0 `woodmart_variation_gallery_data` rows) | **Critical for the data-model contract.** Determines whether variation-gallery data lives in `wd_additional_variation_images_data` post meta on each variation (`new`) or in a single serialized `woodmart_variation_gallery_data` array on the parent product (`old`). |
| `ajax_variation_threshold` | range 1–500 | `30` | `30` (moot — `quick_shop_variable_type = select_options`, never uses this AJAX path) | If a variable product has more children (variations) than this, the grid's "variation form" quick-shop mode (file `03`) defers to AJAX-loading variation data instead of embedding it inline in the page HTML, to avoid bloating page size. |

> ⚠️ **Also confirmed important, but from outside this settings screen**:
> the Jet Woo Product Gallery plugin has its "Slider" widget enabled
> (`jet-woo-product-gallery-settings` option) — the live single-product
> gallery may actually be rendered by that plugin's widget, not Woodmart's
> own `product-image.php`. See file `10` §3 before assuming any of the
> gallery-swap behavior documented in file `04` is what's really on screen.

## Attribute-level settings (Products → Attributes, per-attribute "Edit")

Registered by `attributes-meta-boxes.php` (see file `02` §2 for full option
key mapping). Surfaced as a metabox on the *attribute* edit/add screen
(`woocommerce_after_edit_attribute_fields` / `_after_add_attribute_fields`),
not the theme's global Settings screen:

- Swatch style (1–4), disabled style (1–3), shape (round/rounded/square),
  size (XS/S/M/L/XL/XXL). **DB-confirmed live values for the `pa_colour`
  attribute** (the only attribute this actually matters for, since it's
  the sole `grid_swatches_attribute`): `woodmart_pa_colour_swatch_style = 3`,
  `_swatch_dis_style = 3`, `_swatch_shape = round`, `_swatch_size = m` —
  matches exactly what the captured product-card/single-product classes
  decoded to (files `03`/`05`).
- "Show attribute label on products" (`show_on_product`) — unrelated
  toggle, shows a small badge with the selected term's name on the product
  image; not swatch-click behavior.
- "Change product image on attribute click" (`change_image`) — the
  `wd-changes-variation-image` `<select>` behavior (file `04`).
- "Attribute icon" (`thumbnail`) + "Attribute hint content" (`hint`) — both
  shown on the **additional information table** (the attributes list
  further down the product page), not on the swatches themselves.

## Per-term settings (Products → Attributes → [Attribute] → Configure terms → [Term])

Registered by `swatches.php::woodmart_swatches_metaboxes()` as a metabox
titled **"Extra"** on the term edit screen, with fields grouped under
**"Swatch"**: Image swatch (upload), Color swatch (color-picker, hex),
Text swatch (checkbox `not_dropdown`) — and grouped under **"Extra"**: Term
hint content (`pa_term_hint` textarea). Full storage details in file `02` §1.

## Linked Variations feature settings

- `linked_variations` (switcher, master toggle) — gates
  `linked-variations/class-frontend.php`'s entire output (file `08`).
  **DB-confirmed: this is `on`, but zero `woodmart_woo_lv` posts exist** —
  the feature is enabled but has never actually been used on this site.
  Safe to drop entirely from the rebuild.
- Per-linked-group configuration (which products, which shared attributes,
  which attributes use the linked product's own image) is **not** a global
  Theme Setting — it's managed via a dedicated custom post type UI
  (`woodmart_woo_lv` posts), out of scope for the global Shop settings page.
  Locate the actual admin screen for this CPT in wp-admin before assuming
  its exact field layout; only the resulting post-meta data shape is
  documented here (file `02` §6).
