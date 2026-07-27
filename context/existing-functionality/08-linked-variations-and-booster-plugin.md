# 08 — Related/Adjacent Systems: Linked Variations & Booster's Own Swatches

Two more systems render swatch-like UI on this site, both **separate from**
the core Woodmart variation-swatch system documented in files `03`/`04`.
Both should be checked against the live site before assuming they're
inactive.

## A. Woodmart "Linked Variations" (cross-product swatches)

File: `wp-content/themes/woodmart/inc/integrations/woocommerce/modules/linked-variations/class-frontend.php`
(`XTS\Modules\Linked_Variations\Frontend`, a singleton auto-instantiated at
the bottom of the file).

**What it's for**: some catalogs split what's conceptually "one product in
several colors" into **separate, individually-published WooCommerce
products** (rather than variations of one variable product) — e.g. because
each color has very different specs/stock/SEO needs. Linked Variations lets
an admin declare a group of such standalone products as "linked" via a
shared set of attributes, and renders swatches on each product's page that
act as **plain links to the other products' permalinks**, visually
indistinguishable from real variation swatches.

Data model (also documented in file `02` §6):

- Custom post type `woodmart_woo_lv` — one post per "link group".
- `_woodmart_linked_products` (post meta, array of product IDs in the
  group).
- `_woodmart_linked_attrs` (post meta, array of shared attribute taxonomy
  names, e.g. `['pa_color']`).
- `_woodmart_linked_use_product_image` (post meta, array of attribute names
  where the swatch should render using **that linked product's own
  featured image** rather than the attribute term's `color`/`image` meta).

Rendering (`output()`, hooked `woocommerce_single_product_summary` priority
25 — i.e. appears in the normal single-product summary flow, near where
the add-to-cart form usually sits, gated by Theme Setting
`linked_variations`):

1. Looks up whether the *current* product's ID appears in any
   `woodmart_woo_lv` post's `_woodmart_linked_products` (a `WP_Query`
   `meta_query` `LIKE` match against the serialized array — `numberposts=1`,
   so a product should really only belong to **one** link group; behavior
   if it's in multiple is undefined/first-match-wins).
2. For each shared attribute, resolves **the current product's own term**
   for that attribute (`get_the_terms()`, first result) plus, for every
   *other* linked product, whichever of its own terms matches — building a
   `{taxonomy: {terms: {term_slug: {id, permalink, image, title,
   stock_status, is_purchasable, attributes}}}}` structure
   (`get_linked_variations()` / `get_linked_variations_data_cached()`).
3. Emits markup structurally identical to the real single-product swatches
   (file `04`) — `<div class="wd-swatches wd-swatches-product {classes}">`
   with one swatch per term — **except each swatch is an `<a href="{linked
   product permalink}">`** instead of a clickable `<div>` bound to a
   `<select>`. Same color→image→text priority, same
   style/shape/size/disabled-style classes sourced from the identical
   per-attribute admin settings (file `02` §2) via
   `woodmart_wc_get_attribute_term()`.
4. Out-of-stock or non-purchasable linked products get `.wd-disabled
   wd-linked` (visually dimmed like a real disabled swatch, but still
   technically a working link since it's just an `<a href>`).
5. Also respects "Limit swatches on single product"
   (`single_product_swatches_limit`/`_count`) and reuses
   `swatches-limit`/`woo-opt-limit-swatches` for the expand-on-click "+N"
   behavior — identical UX to the main swatch system.

**Rebuild implication**: if this site actually uses Linked Variations for
any product family, a rebuild needs an equivalent "swatch that links to a
sibling product" concept, keyed on the **same three meta keys** above (on
a `woodmart_woo_lv`-equivalent CPT, or migrated into whatever grouping
mechanism the new theme uses) — check the live `wp_posts` table for any
`post_type = 'woodmart_woo_lv'` rows and the Theme Setting
`linked_variations` value before assuming this feature is unused.

**RESOLVED via DB check**: `ddev wp post list --post_type=woodmart_woo_lv`
returns **zero** posts, despite the `linked_variations` theme setting
being `on`. This feature has never actually been used on this site (the
toggle being on with no data behind it is presumably just the shipped
default). **Recommendation: drop this feature entirely from the rebuild.**
No CPT to re-register, no meta to migrate, no admin UI to rebuild.

**QA finding — the CPT itself is registered by a plugin, not the theme.**
`register_post_type( 'woodmart_woo_lv', ... )` lives in the
`wp-content/plugins/woodmart-core/class-post-types.php` plugin file, not
anywhere in `wp-content/themes/woodmart` or `woodmart-child` (confirmed by
grepping `register_post_type` across both themes — zero matches). This
plugin was outside this research's original theme-only scope. Implication:
if `woodmart-core` stays active after the theme swap, this CPT keeps
working with zero changes needed; if it's deactivated too, the CPT stops
being registered and existing `woodmart_woo_lv` posts/postmeta become
orphaned (data intact, but no admin UI/queryable type without
re-registering it). Decide this based on whether the plugin is staying or
going, independent of the theme decision.

## B. Booster for WooCommerce — "Product Variation Swatches" module

File: `wp-content/plugins/woocommerce-jetpack/includes/class-wcj-product-variation-swatches.php`
(class `WCJ_Product_Variation_Swatches`, part of the **Booster for
WooCommerce** (`woocommerce-jetpack`) plugin — a large, unrelated
all-in-one WooCommerce utility plugin that happens to bundle its own
independent swatches feature).

**This is a completely separate implementation from Woodmart's**, with its
own data storage and its own frontend markup/JS
(`wcj-frontend-pvs-script.js`, `wcj-frontend-pvs-style.css` — not read in
this research pass, referenced only via enqueue calls here). Key points:

- **Only activates if the module itself is enabled** (`$this->is_enabled()`,
  Booster's own per-module admin toggle — check Booster's settings screen
  or the relevant `wcj_*` option to confirm live status; not verifiable
  from static code alone).
- **Per-attribute type**, chosen via a dropdown injected into the
  **Products → Attributes → Edit/Add** screen (`wcj_add_column_on_product_attributes`,
  hooked to the *same* `woocommerce_after_edit_attribute_fields`/
  `_after_add_attribute_fields` actions Woodmart's own
  `woodmart_render_product_attrs_admin_options()` uses — **both plugins'
  admin UIs render on the same screen simultaneously if both are active**).
  Saved as `wp_options` row `wcj_attribute_type_{attribute_id}` (keyed by
  attribute **ID**, not name — different from Woodmart's naming scheme in
  file `02` §2), value one of `wcj_color` / `wcj_image` (a `wcj_button`
  type also exists in the code but isn't offered in this free-tier UI —
  likely a Booster Elite/paid upsell feature per the "Upgrade to Booster
  Elite" notice in the admin markup).
- **Per-term data**, stored as **term meta** (again, separately from
  Woodmart's own term meta keys in file `02` §1):
  - `wcj_product_attribute_color` — hex color.
  - `wcj_product_attribute_image` — attachment ID.
- **Frontend hook**: filters `woocommerce_dropdown_variation_attribute_options_html`
  — the exact WooCommerce core function Woodmart's `variable.php` calls
  (`wc_dropdown_variation_attribute_options()`) to render each attribute's
  hidden `<select>` (file `04` step 3). If this filter is active for a
  given attribute (i.e. `wcj_attribute_type_{id}` is set to `wcj_color` or
  `wcj_image`), Booster **replaces the entire returned HTML** with its own
  `<select class="hide wcj_pvs_select …">` (still present, still hidden,
  still holding the real value WooCommerce needs) **plus** a sibling
  `<ul class="wcj_variable_items_wrapper" role="radiogroup">` of
  `<li class="variable-item wcj_color-variable-item-{slug}">` swatches.

### ⚠️ Potential double-swatch conflict — verify on the live site

Woodmart's `variable.php` (file `04`) renders **its own** `.wd-swatches-product`
swatch markup **directly inline** (a hand-rolled `foreach` over terms, not
going through `wc_dropdown_variation_attribute_options_html`) and *then*
separately calls `wc_dropdown_variation_attribute_options()` purely to get
the real, hidden `<select>`. If Booster's module is enabled for the same
attribute, that second call's return value is intercepted by Booster's
filter and turned into **both** a hidden `<select>` *and* a **second,
visible** `<ul class="wcj_variable_items_wrapper">` swatch list — meaning
the page could end up rendering **two independent, differently-styled
swatch UIs for the same attribute** (Woodmart's `.wd-swatch` divs, plus
Booster's `.variable-item` `<li>`s immediately after), unless Booster's own
CSS specifically hides its list, or the module has simply never been
enabled for any attribute on this site.

**Action item for the rebuild** (not resolvable from static code alone):
inspect the live product page HTML/DOM (or query `wp_options` for any
`wcj_attribute_type_%` rows and check whether Booster's PVS module is
toggled on in Booster's settings) to determine whether this second system
is actually contributing any visible UI today. If it is **not** currently
producing visible output, the rebuild only needs to replicate Woodmart's
own system (files `03`/`04`); if it **is**, treat it as a second,
independent swatch feature with its own data model (`wcj_product_attribute_color`/
`wcj_product_attribute_image` term meta, `wcj_attribute_type_{id}` options)
that also needs preserving/reading.

**RESOLVED via DB check against the real live/reference site — confirmed
dead, for a more specific reason than first thought.** Earlier passes in
this research checked the `chairforce-2026` DDEV project (this rebuild's
own workspace) and found `woocommerce-jetpack` **inactive** there — but
that project turns out to be a stripped-down copy with several plugins
already deactivated in prep for this rebuild. The actual live/full
reference site is a **separate** DDEV project, `chairforce`
(`~/Projects/wp/chairforce`), and there, `woocommerce-jetpack` (Booster)
**is active**. What's still true either way: the PVS module's own enable
toggle, `wcj_product_variation_swatches_enabled`, is explicitly set to
`no`, and querying for any `wcj_attribute_type_%` option rows returns
**zero results** on the real live site too — meaning the module has never
been configured for any attribute, regardless of the plugin's overall
active/inactive state. Net effect is identical either way: there is no
double-swatch conflict risk and nothing from this module to preserve or
migrate. **Recommendation: ignore this entire section for the rebuild** —
it's included here only as a documented, closed investigation in case the
module is ever turned on for an unrelated reason in the future. (Take-away
for the rest of this research folder: always double-check *which* DDEV
project/DB you're querying — `chairforce` is the real reference site,
`chairforce-2026` is this rebuild's own, partially-stripped workspace copy.)
