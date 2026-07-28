# 02 — Data Model & Storage (the contract a rebuild must preserve)

This is the most important file for the rebuild: it lists **every place**
swatch-related data is stored in the WordPress database today. Since the
requirement is "underlying data for the products will stay the same", a new
theme must read from (and, for admin UX, ideally still write to) these same
locations rather than inventing new meta keys.

> **DB-confirmed:** on this site the real color attribute taxonomy slug is
> **`pa_colour`** (British spelling), not `pa_color` — this file uses
> `pa_color` generically/illustratively throughout, but always confirm the
> real slug against `wp_woocommerce_attribute_taxonomies` for any given
> install rather than assuming. The 15 global attributes on this site:
> `colour`, `size`, `height`, `material`, `features`, `shape`, `mounting`,
> `arms`, `stackable`, `assembly`, `indoor-outdoor`, `base-type`, `folding`,
> `seat`, `backrest`.

## 1. Per-term appearance — `wp_termmeta` (taxonomy term meta)

Registered in `swatches.php::woodmart_swatches_metaboxes()` as a metabox
attached to **every** `pa_*` attribute taxonomy (looped over
`wc_get_attribute_taxonomies()`), shown on the term edit/add screens under
**Products → Attributes → [Attribute] → Configure terms → [Term]**.

| Meta key (on the term, e.g. term of `pa_color`) | Type | Meaning | Read by |
|---|---|---|---|
| `color` | string, CSS color (confirmed live on this site: **`rgb(r,g,b)` strings**, e.g. `rgb(42,142,205)` — not hex `#rrggbb` as the field's color-picker UI might suggest; don't assume a specific format, just store/render whatever CSS-valid string is present) | The swatch's background color. If set, the swatch renders as a filled color circle/square. | `woodmart_has_swatch()`, grid + single templates, `linked-variations/class-frontend.php` |
| `image` | array `['id' => attachment_id, ...]` (ACF/CMB-style upload field) **or** a plain URL string (import/legacy) | A swatch image (e.g. a fabric/material swatch photo). Takes precedence handling is: color > variation-image (if enabled) > this `image` field > text-only. | same as above |
| `not_dropdown` | `'1'`/truthy or empty | If truthy and neither `color` nor `image` is set, forces a "text swatch" (just the term name in a small pill) instead of falling through silently. In practice: swatches always render as text if there's no color/image; this field is mostly informational/legacy. | `woodmart_has_swatch()` |
| `pa_term_hint` | textarea (may contain HTML/markdown-ish text) | Extra hint tooltip text shown next to the term name on the **product "Additional information" attributes table** (not on the swatch itself). | `woodmart_add_term_hint()` filter on `woocommerce_attribute` |

Admin-visible column: `attributes-meta-boxes.php`/`swatches.php` also add a
**"Preview" column** to the attribute terms list table
(`manage_pa_{attribute}_custom_column`) showing a live swatch preview
(`.wd-attr-peview.wd-img|wd-bg|wd-text`) driven by the same `color`/`image`/
`not_dropdown` term meta.

**QA note — does this survive Woodmart's removal?** Yes, completely. This
is plain WordPress term meta (`wp_termmeta`), not part of Woodmart's "Theme
Options" system at all — there's no `register_meta()` call anywhere in the
theme, child theme, or `woodmart-core` plugin for `color`/`image`/
`not_dropdown`/`pa_term_hint` (verified by grep); they're just raw
`get_term_meta()`/`update_term_meta()` custom fields with a metabox UI
layered on top by Woodmart. Deleting Woodmart removes the metabox UI (the
"Configure terms" screen loses its Swatch/Extra fields) but the underlying
`wp_termmeta` rows are untouched — a new theme can read
`get_term_meta( $term_id, 'color', true )` immediately, with zero migration,
and just needs to rebuild an equivalent admin UI for editing them.

### Fourth QA addendum — DB-verified field scope + exact shapes for a `pa_colour` admin-UI rebuild

A follow-up pass counted real usage of all four fields **scoped specifically
to `pa_colour`** (the only attribute that actually renders swatches — see
§K/file `11`), to determine exactly which fields a rebuilt admin UI needs to
support, and their exact live data shape. Checked against both DDEV
projects (`chairforce-2026` and the live `chairforce` reference) —
identical results in both.

**Only 2 of the 4 fields carry real data on `pa_colour` (81 terms total);
the other 2 are confirmed universally empty and need no admin UI at all:**

| Meta key | Real/populated | Confirmed exact shape (from raw `wp_termmeta` values) |
|---|---|---|
| `color` | **68 / 81** | Plain string, always `rgb(r,g,b)` — e.g. `rgb(0,0,0)`, `rgb(42,142,205)`. Zero terms use hex or `rgba()`; every populated value follows this exact pattern (checked all 8+ samples). |
| `image` | **13 / 81** ⚠️ (see correction below) | PHP-serialized array with exactly two string keys: `a:2:{s:3:"url";s:N:"https://.../uploads/.../file.png";s:2:"id";s:N:"1502845";}` — i.e. `['url' => <full attachment URL string>, 'id' => <attachment ID, as a numeric **string**, not int>]`. |
| `not_dropdown` | **0 / 80** | Confirmed universally empty on `pa_colour` — no admin field needed. |
| `pa_term_hint` | **0 / 81** (and 0 / 109 across *every* attribute, not just `pa_colour`) | Confirmed universally empty site-wide — no admin field needed. |

**⚠️ Correction to an earlier same-day count**: `image` was first reported
as "81/81 populated" — that was a false positive from checking only
`meta_value != ''`. Every `pa_colour` term has an `image` meta *row*, but
**68 of those 81 rows are just the field's empty-shell default**,
`a:2:{s:3:"url";s:0:"";s:2:"id";s:0:"";}` (a non-empty *string*, but with
blank `url`/`id` inside it) — not real data. The corrected, meaningful count
is **13 real image swatches**, confirmed by excluding that exact empty-shell
pattern.

**Clean split, no overlap or gaps**: cross-referencing both fields per-term
confirms every one of the 81 `pa_colour` terms has **exactly one** of
`color` (68 terms) or `image` (13 terms) populated — zero terms have both,
and zero terms have neither. Woodmart's documented `color > image > text`
fallback precedence (line above) is real code but never actually exercised
by this site's data — no term currently relies on it.

**Net field scope for any rebuilt `pa_colour` admin UI**: just `color` and
`image`, in the exact shapes above. `not_dropdown` and `pa_term_hint` can be
dropped from scope entirely — confirmed no data to preserve or expose.

## 2. Per-attribute styling — `wp_options` (NOT term meta!)

This is a common point of confusion: swatch **style/shape/size** are not
stored per taxonomy *term* — they're stored **once per attribute
taxonomy**, as individual rows in `wp_options`, keyed by attribute name.
Set from **Products → Attributes → Edit/Configure "[Attribute]"** (the
attribute itself, not its terms) via `attributes-meta-boxes.php`.

Option name pattern: `woodmart_pa_{attribute_name}_{field}` (note:
`attribute_name` here is the raw name *without* the `pa_` prefix, e.g. for
taxonomy `pa_color` the attribute_name is `color`, giving option name
`woodmart_pa_color_swatch_style`) — **except** `_thumbnail` and `_hint`
which use `sanitize_title_with_dashes($attribute['attribute_name'])` as the
key fragment (should be identical for normal slugs).

| Option key suffix | Values | Meaning | Default (if unset) |
|---|---|---|---|
| `_swatch_style` | `'1'` \| `'2'` \| `'3'` \| `'4'` | Visual style of the "active/enabled" swatch (underline, border+bg-highlight, border-only, overlay chevron). See file `05`. | `'1'` |
| `_swatch_dis_style` | `'1'` \| `'2'` \| `'3'` | Visual style of a *disabled* swatch (opacity only / opacity+diagonal-strike / diagonal-strike only). | `'1'` |
| `_swatch_shape` | `'round'` \| `'rounded'` \| `'square'` | Swatch shape (border-radius). | `'round'` |
| `_swatch_size` | `'xs'` \| `'default'` (labelled "S") \| `'m'` \| `'large'` \| `'xlarge'` \| `'xxl'` | Swatch pixel size (drives the `--wd-swatch-size` CSS var). | `'default'` |
| `_show_on_product` | `'on'` \| `'off'`/empty | Whether to show this attribute's selected-term as a small label badge on the product image (separate feature, not swatch clicking itself). | off |
| `_change_image` | `'on'` \| `'off'`/empty | **Important for the "change image on attribute click" requirement.** When `'on'`, this attribute's plain `<select>` dropdown (not its swatches — see caveat below) is tagged with class `wd-changes-variation-image`, and a `change` handler swaps the single product's main image to that variation's image, even mid-way through selecting other attributes. This exists *in addition to* the swatch-click-triggered image change that already happens via `found_variation`. | off |
| `_thumbnail` | attachment ID | An icon shown for the *attribute itself* on the additional-information table (not per-term). | none |
| `_hint` | text | Attribute-level hint tooltip on the additional-information table. | none |

Read via the single accessor `woodmart_wc_get_attribute_term( $attribute_name, $field, $default = false )`
→ `get_option( 'woodmart_' . $attribute_name . '_' . $field, $default )`
(note: despite the setter using `woodmart_pa_{name}_{field}`, the getter is
called as `woodmart_wc_get_attribute_term( 'pa_color', 'swatch_style' )`,
i.e. callers always pass the **full taxonomy name including `pa_`**, so the
resulting option key is consistently `woodmart_pa_color_swatch_style`).

Global (not per-attribute) equivalents of some of these live in **Theme
Settings → Shop** instead (see file `06`), e.g. the swatch style/shape/size
used for the *grid* "simple swatch list" mode falls back to
`woodmart_get_opt('grid_swatches_attribute')` + the same
`woodmart_wc_get_attribute_term()` calls — there is no separate global
style/shape/size option; the grid and single product pages both read the
**same per-attribute** style/shape/size options above.

**DB-confirmed (live reference site, `chairforce` project): every attribute
has these rows, but only `pa_colour` is customized.** All 14 non-color
attributes on this site (`assembly`, `backrest`, `base-type`, `brand`,
`features`, `folding`, `indoor`, `indoor-outdoor`, `last-chance-to-buy`,
`material`, `seat`, `size`, `stackable`) sit at Woodmart's untouched factory
defaults (`swatch_style=1`, `swatch_dis_style=1`, `swatch_shape=round`,
`swatch_size=default`) — these rows exist simply because Woodmart
auto-creates them the moment an attribute's admin edit screen is ever
loaded, **not** because the attribute is actually intended to render as a
swatch. Only `pa_colour` deviates (`style=3`, `dis_style=3`, `shape=round`,
`size=m`). Confirming the actual switch that decides "swatch vs. plain
dropdown" per attribute: WooCommerce's own `wp_woocommerce_attribute_taxonomies.attribute_type`
column is `'select'` for **every** attribute on this site, including
`colour` — Woodmart does **not** consult that column at all. The real
switch is simply *"do this attribute's terms have `color`/`image` term
meta set (§1)?"* — `pa_colour`'s terms do, `pa_size`'s (and every other
attribute's) terms don't, so only `pa_colour` ever visually renders as
swatches regardless of what these style options say. **Confirms the
rebuild only needs to hardcode one style/shape/size combination for one
real attribute (`pa_colour`)** — the other 14 attributes' option rows are
noise, not signal.

**QA note — does this survive Woodmart's removal?** The `wp_options` rows
themselves (e.g. `woodmart_pa_color_swatch_style`) physically remain in the
database — WordPress never deletes options on theme deactivation — but they
become **inert**: nothing left in the codebase calls
`woodmart_wc_get_attribute_term()` to read them. They're individual rows
(not part of the bundled Theme Options blob described in §3 below), so they
*could* be read once during a migration with a plain `get_option(
'woodmart_pa_color_swatch_style' )` if you want to seed a new theme's
equivalent setting with the currently-configured value, but there's no
requirement to keep reading this exact option name going forward — see
file `09` §3 for the confirmed live values on this site (`style=3`,
`dis-style=3`, `size=m`, `shape=round`), which QA has since scoped the
rebuild to hardcode rather than re-implement as a configurable option.

## 3. "Which attribute to show on the grid" — a Theme Setting + optional per-product override

- Global default: Theme Settings → Shop → Attribute swatches → **"Grid
  swatch attribute to display"** → option `grid_swatches_attribute` (a
  single attribute taxonomy name). **DB-confirmed live value on this site:
  `pa_colour`.**
- Per-product override: post meta **`_woodmart_swatches_attribute`** on the
  product post. If set, wins over the global setting. **DB-confirmed
  (live reference site): registered/checked on essentially every product,
  but only populated (non-empty) on 1 product site-wide.** Real, working
  code path — just almost never actually used. Worth a cheap fallback
  check in the rebuild (`if product override set, use it; else use the
  hardcoded pa_colour default`) but not worth building UI/tooling around.
- Resolved by `woodmart_grid_swatches_attribute()`:
  ```php
  function woodmart_grid_swatches_attribute() {
      $custom = get_post_meta( get_the_ID(), '_woodmart_swatches_attribute', true );
      return empty( $custom ) ? woodmart_get_opt( 'grid_swatches_attribute' ) : $custom;
  }
  ```
  (This is only consulted in the "simple swatch list" grid mode — the "full
  variation form" grid mode shows every attribute, so this setting doesn't
  apply there.)

**QA note — does this survive Woodmart's removal?** No, and this is the key
contrast with §1/§2 above. `grid_swatches_attribute` (and every other Theme
Settings → Shop toggle in file `06`) is **not** an individual `wp_options`
row — it's one key inside a single serialized array stored under one
`wp_options` row, `xts-woodmart-options`, populated at runtime by Woodmart's
own `Options` class (`inc/admin/modules/options/class-options.php`) on
`init`. That row will still exist in the DB after removal, but nothing will
ever populate the `$woodmart_options` PHP global again, so
`woodmart_get_opt('grid_swatches_attribute')` simply can't be called
anymore (the function itself, `woodmart_get_opt()`, is theme code). This
setting is 100% theme-owned infrastructure with no independent existence —
a rebuild should just hardcode the equivalent choice (or build its own,
much simpler settings field) rather than trying to preserve any read path
to this option.

## 4. Variation images ("swap the whole main gallery, not just one photo")

Two **mutually exclusive** storage strategies exist, switched by Theme
Setting `variation_gallery_storage_method` (`'new'` is default/current;
`'old'` is legacy/deprecated but still fully functional code-wise):

### 4a. New method (default) — per-variation post meta

- Meta key: **`wd_additional_variation_images_data`** on the **variation
  post** itself (`post_type = product_variation`), a comma-separated list of
  attachment IDs (string, e.g. `"123,456,789"`).
- Saved via `woocommerce_save_product_variation` action
  (`woodmart_avi_save_images()`), from a hidden input
  `wd_additional_variation_images[{variation_id}]` in the admin variation
  panel (rendered by `woodmart_avi_admin_html()` on
  `woocommerce_variation_options`).
- Exposed to the frontend by hooking `woocommerce_available_variation`
  (`woodmart_avi_update_available_variation()`), which **merges into
  WooCommerce's own variation JSON** two new keys:
  - `additional_variation_images` — array of image-data objects for *this*
    variation (its featured image first, if any set, then the extra IDs).
  - `additional_variation_images_default` — array of image-data objects for
    the **parent product's own** default gallery (used when a variation is
    de-selected / reset). Computed by `woodmart_avi_get_default_data()`
    from `$product->get_gallery_image_ids()` + the product's own thumbnail.
  - Image-data object shape (from `woodmart_avi_get_image_data()`): `width`,
    `height`, `src`, `full_src`, `thumbnail_src`, `class`, `alt`, `title`,
    `data_caption`, `data_src`, `data_large_image`,
    `data_large_image_width/height`, plus optional `srcset`/`sizes`.
  - Because this rides on WooCommerce's existing `data-product_variations`
    JSON blob (already output in `variable.php`), **no extra AJAX request or
    localized script is needed** for this method.

### 4b. Old method (legacy, still supported) — parent-product post meta

- Meta key: **`woodmart_variation_gallery_data`** on the **parent product
  post**, a serialized associative array `{ variation_id => "id1,id2,..." }`.
- Saved via the same `woocommerce_save_product_variation` hook
  (`woodmart_save_vg_images()`), from hidden input
  `woodmart_variation_gallery[{variation_id}]`.
- Cleaned up on `save_post` (`woodmart_remove_unnecessary_vg_data()`) to drop
  entries for variations that no longer exist.
- Exposed to the frontend differently: **not** merged into WooCommerce's
  variation JSON. Instead, `woodmart_get_vg_data()` builds the *entire*
  per-variation image HTML server-side and the whole dataset is dumped as a
  **global JS variable** via `wp_localize_script()`
  (`woodmart_variation_gallery_data`, or `woodmart_qv_variation_gallery_data`
  for the Quick View popup — see `woodmart_quick_view_vg_data()`, an inline
  `<script>` echo rather than `wp_localize_script`). `swatchesVariations.js`
  reads `window.woodmart_variation_gallery_data[key]` directly (see
  `isVariationGalleryOld()` / `replaceMainGalleryOld()` in that file).
- **DB-confirmed on this site**: `variation_gallery_storage_method = 'new'`,
  and directly checking `wp_postmeta` confirms it — **1,023 non-empty rows**
  of `wd_additional_variation_images_data` exist, vs. **zero** rows of
  `woodmart_variation_gallery_data` anywhere. The new/per-variation method
  is exclusively and heavily used; the old method is fully dormant on this
  site. (This was resolved via `ddev wp db query` against the actual
  database — if working against a different environment/site, re-verify
  the same way rather than assuming.)

### 4c. Whether this feature is even on at all

Both methods are gated by a single master switch, Theme Setting
`variation_gallery` (default **on**, `'1'`). If disabled, neither
`wd_additional_variation_images_data` nor `woodmart_variation_gallery_data`
is read/written and swatch clicks only ever change the *single* current
image (via WooCommerce's native `variation.image` object), not a whole
gallery.

### 4d. A THIRD, unrelated meta key referencing "additional variation images" — likely dead code

The **child theme** (`woodmart-child/functions.php`, in a
`woocommerce_product_get_gallery_image_ids` filter — see file `07`) reads a
meta key **`_wc_additional_variation_images`** on variations, which does
**not** match either of the two keys above (`wd_additional_variation_images_data`
or `woodmart_variation_gallery_data`). No code anywhere in this codebase
*writes* `_wc_additional_variation_images`. This is very likely a leftover
reference to some other plugin (possibly an older "WooCommerce Additional
Variation Images" plugin that predated Woodmart's own built-in feature and
is no longer installed).

**DB-confirmed: this code path is dead.** `SELECT COUNT(*) FROM
wp_postmeta WHERE meta_key = '_wc_additional_variation_images'` returns
**zero** rows on this site. Safe to ignore entirely in the rebuild — no
need to replicate this filter's behavior at all.

### 4e. A FOURTH meta key, `woo_variation_gallery_images` — populated but unowned

DB-confirmed (live reference site): a meta key `woo_variation_gallery_images`
has **549 non-empty rows** across products (each a single numeric value,
e.g. `1060740` — looks like a single attachment/post ID, not a list). This
is a genuinely different key from all three above (`wd_additional_variation_images_data`,
`woodmart_variation_gallery_data`, `_wc_additional_variation_images`). A
full-text search of every `.php` file in `wp-content/plugins/` and
`wp-content/themes/` on the live reference site turns up **zero** matches
for this string — no installed plugin or theme code reads or writes it
today. It's real data from a plugin that has since been completely removed
from the filesystem. Since nothing currently reads it, it has **zero
effect on live behavior** and is out of scope for replicating current
functionality — but because real per-product data still exists under this
key, don't delete it during any migration/cleanup pass on a hunch; flag it
for the site owner in case it turns out to matter for something not
covered by this research (e.g. a reporting/export tool, or data someone
intends to resurrect).

## 5. Product-level swatch/quick-shop overrides

| Meta key | On | Meaning |
|---|---|---|
| `_woodmart_swatches_attribute` | product post | Per-product override of which attribute's swatches show on the grid (see §3). |
| `_product_attributes` | product post | Standard WooCommerce attribute assignment (taxonomy + term slugs). Swatch rendering always starts from here (`get_post_meta($post_id, '_product_attributes', true)` is used by the cache-busting code to know which attributes to invalidate). |

## 6. Linked Variations (cross-product swatches) — separate data island

Not swatches-on-a-variable-product; this is Woodmart's separate "Linked
Variations" feature (file `08` has more detail). Its data lives on a
dedicated custom post type `woodmart_woo_lv`, not on the products
themselves:

| Meta key | On | Meaning |
|---|---|---|
| `_woodmart_linked_products` | `woodmart_woo_lv` post | Array of linked product IDs. |
| `_woodmart_linked_attrs` | `woodmart_woo_lv` post | Array of attribute taxonomy names shared across the linked products. |
| `_woodmart_linked_use_product_image` | `woodmart_woo_lv` post | Array of attribute names where the swatch should use each *linked product's* own featured image instead of the term's `color`/`image` meta. |

**DB-confirmed: this feature is currently 100% unused on this site.**
`ddev wp post list --post_type=woodmart_woo_lv` returns **zero** posts,
despite the master toggle `linked_variations` being **on** in Theme
Settings. Safe to drop this entire feature from the rebuild — no data
exists to migrate or replicate.

**QA note — where is `woodmart_woo_lv` actually registered?** Not in the
theme. `register_post_type( 'woodmart_woo_lv', ... )` lives in the
**`woodmart-core` plugin** (`wp-content/plugins/woodmart-core/class-post-types.php`,
inside `Class_Post_Types::linked_variations()` or equivalent — grep
`woodmart_woo_lv` in that file to confirm the exact method name at the time
of reading). This plugin was outside this research's original scope
(theme-only) but matters here: if `woodmart-core` is also deactivated when
Woodmart is replaced, this CPT stops being registered, and any existing
`woodmart_woo_lv` posts become an orphaned/unregistered post type (data
rows remain in `wp_posts`/`wp_postmeta`, but there's no admin edit screen
and `WP_Query`/REST won't surface them without re-registering the type
somewhere). Check whether this site actually has any `woodmart_woo_lv`
posts before deciding whether Linked Variations needs porting at all (see
file `08` §A and file `09` §0.5).

## 7. Caching layer (not "data" per se, but affects correctness)

`swatches.php` caches the (relatively expensive)
`$product->get_available_variations()` call and the derived per-attribute
swatch arrays in **transients**:

- `woodmart_swatches_cache_{product_id}` — raw available variations.
- `woodmart_swatches_cache_{attribute_name}_{product_id}` — swatches-to-show
  array for one attribute.

TTL: `WEEK_IN_SECONDS`, filterable via `woodmart_swatches_cache_time`.
Invalidated on `save_post` and `woocommerce_after_product_object_save`.
Can be disabled entirely via the `woodmart_swatches_cache` filter returning
`false` — **this site's child theme does exactly that** (see file `07`),
so on this specific install these transients are effectively unused and
swatch data is recomputed on every request.
