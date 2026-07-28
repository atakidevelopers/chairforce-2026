# 10 — JetEngine Product Fields & the "Parts" System

Added after a DB-verification pass confirmed that several product-page
features referenced elsewhere in this research (file `02` §4d dead-code
flag, file `07`'s "Parts" section) are **not Woodmart features at all** —
they're built with **JetEngine** (Crocoblock), rendered via **Elementor +
JetEngine Listing Grid** templates. Per user confirmation, Elementor and all
`Jet*` plugins are **also being retired** alongside Woodmart, so everything
in this file is now in-scope for the rebuild, not just swatches.

> **Environment note (updated/corrected):** the first pass of this file was
> written against the `chairforce-2026` DDEV project (this rebuild's own
> workspace), where `elementor`, `elementor-pro`, `jet-engine`,
> `jet-woo-builder`, `jet-tabs`, `jet-smart-filters`, `jet-popup`,
> `jet-woo-product-gallery`, `woodmart-core`, and `woocommerce-jetpack`
> (Booster) are all **inactive** — that project is a stripped-down copy, not
> the live site. There is a **separate DDEV project, `chairforce`**
> (`~/Projects/wp/chairforce`, served at `chairforce.test`), which is the
> actual **live/full reference site** with every one of those plugins
> genuinely active. All findings in this file have since been **re-verified
> against `chairforce`** (both live rendered HTML at
> `http://chairforce.test/product/regis-bar-stool/` and direct DB queries),
> and everything below reflects that live-verified state — the two
> databases turned out to hold near-identical postmeta/options data (e.g.
> the same field non-empty counts), so the earlier data-level findings
> were already accurate; what changed is confidence about *rendering*
> (§2, §3 below — now resolved, not speculative) and a few newly-discovered
> fields/quirks (§1a, §1b).

## 1. JetEngine custom meta boxes on the product post

Registered via a single `wp_options` row, `jet_engine_meta_boxes`
(confirmed by directly decoding its serialized PHP value on the live
`chairforce` site — this is the authoritative source, not an inference from
templates/rendering). It contains exactly two meta box definitions, both
under the JetEngine "Meta Box" module:

### Meta box "meta-1", titled "Product Additional Information" (`object_type: post`, `allowed_post_type: [product]`)

| Field name (= meta key, unprefixed) | Admin label | Type | Non-empty rows (site-wide, live DB) |
|---|---|---|---|
| `dimensions` | Dimensions | `wysiwyg` | 595 |
| `care` | care | `wysiwyg` | 542 |
| `additional_information` | Additional Information | `wysiwyg` | **1** — essentially unused |

All three are plain `get_post_meta( $product_id, 'dimensions', true )` /
`'care'` / `'additional_information'` reads — no prefix, no namespacing,
straightforward rich-text (HTML) content stored directly on the product
post. Confirmed end-to-end on `regis-bar-stool`: the `dimensions` meta
value ("Seat: 42cm (w) x 36cm (d)...") is exactly what renders in that
product's "Dimensions" tab/accordion item on the live page, and likewise
for `care`. **Important distinction**: this custom `dimensions` field is
*separate* from WooCommerce's own native numeric Shipping-tab dimensions
(`_length`/`_width`/`_height`/`_weight`) — those are untouched core
WooCommerce fields, unaffected by anything in this file. This JetEngine
`dimensions` field is a free-text/rich-text field (e.g. a sizing
description or chart), not the numeric shipping fields.

### Meta box "meta-2", titled "Parts" (`object_type: woocommerce_product_data`, i.e. a **Product Data tab**, `wc_product_data_panel: "related"`)

| Field name (= meta key) | Admin label | Type | Non-empty rows |
|---|---|---|---|
| `parts` | Parts | `posts` (JetEngine relationship field, `is_multiple: true`, `search_post_type: [product]`) | 196 |

This meta box's `object_type` is literally `woocommerce_product_data`
(not `post`) — JetEngine renders it as a genuine **WooCommerce Product
Data panel tab** (it hooks into the same tab system as "General",
"Inventory", "Shipping", etc.), specifically attached to the built-in
**"related"** tab slot. Stored as a **standard PHP-serialized array of
product IDs** via normal `update_post_meta()`, e.g. on `regis-bar-stool`
(product 1507417): `meta_value = a:1:{i:0;s:7:"1506361";}` (one linked
part, product 1506361). This is what feeds the "Parts" system referenced
in file `07` — a list of related "spare part" products attached to a
parent product (e.g. a chair's replaceable feet/stoppers, each a full
standalone WooCommerce product in its own right — `regis-bar-stool`'s
"Product Info" tab text literally says "Spare feet and stoppers are
available. See PARTS tab.").

### 1a. ⚠️ Legacy ACF ghost fields — orphaned, but a real migration-completeness risk

Two of the JetEngine field names above turn out to have started life as
**Advanced Custom Fields (ACF)** fields, before JetEngine's "Meta Box"
module took over. Evidence, straight from `wp_postmeta` on the live site:

| Meta key | What it is | Non-empty rows |
|---|---|---|
| `_dimensions` | ACF field-key shadow → value `field_5fd81d83dcbdf` | 698 |
| `_care_tab` | ACF field-key shadow → value `field_5f84f62021154` | 699 |
| `care_tab` | The **old** ACF field's actual value (a *different* field name from JetEngine's current `care`) | 409 |

The `_fieldname → field_xxxxxxxxxxxxx` pattern is ACF's own internal
bookkeeping (it stores which field-group field produced a value, so it can
resolve the right input type on the edit screen). **ACF/ACF Pro is not
installed at all on the live site today** — confirmed via `wp plugin list`
finding zero acf-related plugin and zero `acf-field-group` posts matching
these fields in `wp_posts`. So `_dimensions`/`_care_tab`/`care_tab` are
100% inert today; nothing reads them.

For `dimensions`, the old ACF field and the new JetEngine field happen to
share the **exact same meta key** (`dimensions`), so no data was stranded
— whatever's in `dimensions` today is readable regardless of which system
originally wrote it.

**For `care`, this is not true — and it's a real, currently-live content
gap.** The old ACF field was named `care_tab` (not `care`), and when the
site moved to JetEngine's `care` field, the content was **not** migrated
across: `care_tab` has non-empty content on 409 products, while the
*currently rendered* `care` field only has content on 542 — the two sets
are not a subset of one another. **Practical implication**: some
unknown number of products almost certainly show a **blank "Care" tab
today** because their real care instructions are stranded under the dead
`care_tab` key.

> **Decision (locked, per client discussion):** the rebuild will **not**
> proactively backfill `care_tab` into `care`. We work only with whatever
> is currently in the live `care` field, exactly as the existing site
> does today — this is a pre-existing content gap on the *current* site,
> not something the rebuild introduces or is responsible for fixing. The
> `SELECT post_id FROM wp_postmeta WHERE meta_key='care_tab' AND
> meta_value != '' AND post_id NOT IN (SELECT post_id FROM wp_postmeta
> WHERE meta_key='care' AND meta_value != '')` audit query above is kept
> in this doc purely as a reference in case the client later asks us to
> investigate or backfill it — **flag it to the client, don't act on it
> unless asked.**

### 1b. Other related per-product fields worth knowing about

- **`_woodmart_swatches_attribute`** (Woodmart's per-product swatch-attribute
  override, file `02` §3/§5) — unrelated to JetEngine, just noting it's a
  different per-product override mechanism that also exists on this same
  post type.
- **`_jet_woo_template`** (JetWoo Builder's per-product single-product
  *template* override) — the field exists and is registered, but **zero
  non-empty values were found across every product on the live site.**
  The whole catalog relies on one single, global Elementor Theme Builder
  "Single Product" template (condition-matched to all products, not
  overridden per product). Nothing to replicate here beyond "the rebuild
  needs exactly one single-product layout."
- **Jet Woo Product Gallery's own per-product fields** — this plugin adds
  its own optional video/360°-image fields to the gallery, entirely
  separate from Woodmart's variation-image system (file `02` §4):
  `_jet_woo_product_video_type`, `_jet_woo_product_youtube_video_url`,
  `_jet_woo_product_vimeo_video_url`, `_jet_woo_product_self_hosted_video`,
  `_jet_woo_product_video_placeholder`, `_product_360_image_gallery`. All
  empty on `regis-bar-stool`; worth a quick site-wide non-empty count
  before finalizing gallery-rebuild scope, in case other products actually
  use gallery video/360° images.
- **`woo_variation_gallery_images`** — a mystery meta key with 549
  non-empty rows (single numeric value per row) that **no currently
  installed plugin or theme file references anywhere** (confirmed by a
  full-text search of every `.php` file in `wp-content/plugins/` and
  `wp-content/themes/`). Orphaned data from a long-since-removed plugin.
  Doesn't affect current behavior; flagged only so it isn't mistaken for
  something load-bearing. See file `02` §4e.

## 2. Frontend rendering — JetEngine Listing Grid + Elementor

The actual "Parts" UI on the single product page is a JetEngine **Listing
Grid** widget, built in an Elementor template post (`_elementor_template_type:
jet-listing-items`, `_listing_type: elementor`), titled **"Spare Parts
Listing - On the Single Product Page"** (post ID `1043111` on this DB — id
will differ per environment, locate by title). A second listing template,
"Spare Parts Listing - add to cart canvas only" (ID `1057192`), is a variant
used in some other (canvas/off-canvas) context — locate and inspect both
before rebuilding.

Structure of the main listing template (per part/related product):

- `theme-post-featured-image` widget — the part's own featured image, inside
  a container with class `partsGalleryImage`.
- `jet-listing-dynamic-field` widget — the part's title.
- `jet-listing-dynamic-field` widget with `dynamic_field_post_object: get_price`
  and CSS class **`parts-price`** — the part's price (this is the exact
  class file `07`'s JS reads/protects).
- A "View Details" button linking to the part's own permalink
  (`post-url` dynamic tag).
- **A full embedded `woocommerce-product-add-to-cart` widget** — i.e. each
  "part" card renders its own independent, real WooCommerce add-to-cart
  form (with its own variation swatches if the part is itself a variable
  product).
- The whole listing container has CSS class **`custom-Parts-section`**
  (confirmed exact match to file `07`'s JS selector).
- Inline `<script>` widget disabling lazy-load for `.partsGalleryImage img`
  (a workaround, not swatch-related).

**This confirms exactly why the child theme's `MutationObserver` protection
exists** (file `07` §6): because each part card embeds its own real,
independent add-to-cart/variation form, WooCommerce's variation JS running
inside *those* nested forms could otherwise fire events that bubble up and
interfere with the *main* product's gallery — the child theme's JS
explicitly guards against that cross-contamination.

**Confirmed live, verbatim, in the rendered HTML of `regis-bar-stool`**:
the exact inline `<script>` block implementing this (function names
`setupVariationSwitcher()`, `initSwitchers()`, `initSection()`,
`handleSwatchSelection()`, `protectMainGallery()`, selectors
`.custom-Parts-section`, `.wd-swatch`, `.parts-price`,
`.woocommerce-product-gallery`) is present verbatim in the page source,
confirming this whole description is accurate to what's actually running
today, not just what the (deactivated, in `chairforce-2026`) child theme
file says it *should* do. On `regis-bar-stool` specifically, the "Parts"
tab/accordion panel renders empty in the initial HTML (no `.custom-Parts-section`
markup present at all in that tab's container) despite the product having
one linked part (§1 above) — the JetEngine Listing Grid for this section
appears to lazy-render only once its containing Elementor Nested-Tabs tab
is actually activated (consistent with the polling/interval-based
activation-detection logic visible in the script itself, which exists
specifically to handle this kind of deferred/lazy rendering).

**One loose thread, likely dead**: the same template's custom CSS includes
a selector `.woo-variation-swatches.wvs-show-label` — class names from a
**third**, different WooCommerce swatches plugin ("Variation Swatches for
WooCommerce" by emran-alhaddad). This plugin **is not in the installed
plugins list at all** on this environment. Most likely leftover CSS from a
plugin that was swapped out before Woodmart was even installed. Don't treat
it as live without separately confirming (e.g. check an old backup/changelog,
or just ignore it — zero installed plugin currently produces that markup).

## 3. ✅ RESOLVED: the single-product gallery is Woodmart's own — Jet Woo Product Gallery only supplies the lightbox

This was the single highest-priority open question from earlier passes.
Checked directly against the live rendered HTML of
`http://chairforce.test/product/regis-bar-stool/`:

- The visible gallery markup is unambiguously Woodmart/WooCommerce native:
  `<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images
  ... wd-has-thumb thumbs-position-bottom images image-action-none">`
  wrapping `<div class="woocommerce-product-gallery__wrapper wd-carousel wd-grid">`
  — exactly the selectors file `04`'s `replaceMainGallery()` targets
  (`.woocommerce-product-gallery__wrapper`, `.wd-carousel-wrap`). **No
  re-targeting needed; file `04`'s gallery-swap JS is describing the real,
  correct DOM.**
- The `jet-woo-product-gallery-settings` option's "Slider" toggle being
  `true` does **not** mean the plugin's widget replaces the gallery on
  this template. What the plugin actually contributes on this page is a
  single, global, initially-empty PhotoSwipe lightbox container appended
  once in `wp_footer` — `<div class="pswp jet-woo-product-gallery-pswp" ...>`
  — which is a lightbox/zoom **shell**, populated dynamically only when a
  gallery image is clicked. Its job is to intercept clicks on the
  *existing* Woodmart gallery images and show them in a nicer lightbox;
  it is not rendering the gallery grid/carousel itself.
- The plugin's other real contribution is the **optional video/360° image
  fields** listed in §1b — a legitimate gallery *enhancement* (extra
  gallery items), not a gallery *replacement*.

**Rebuild implication**: file `04` can be implemented as-is, targeting
Woodmart's native gallery structure, with no changes needed to account for
Jet Woo Product Gallery. The only thing worth carrying forward from that
plugin is the *concept* of a lightbox/zoom-on-click experience (a rebuild
should decide independently whether to keep a PhotoSwipe-style lightbox or
use something else) and, if any products use them, the optional gallery
video/360° items from §1b.

## 4. Other Jet* plugins present — partially audited this pass

Beyond `jet-engine` and `jet-woo-product-gallery` above, these are also
installed and active on the live `chairforce` site:

| Plugin | Typical purpose | Checked here? |
|---|---|---|
| `jet-woo-builder` | Elementor widgets/templates for WooCommerce (product page/archive builder, per-product single-product layout override) | **Checked.** Its per-product template-override field (`_jet_woo_template` post meta) is registered but has **zero non-empty values across every product** on the live site — see §1b. The catalog uses one single global Elementor Theme Builder template for all products, not per-product JetWooBuilder overrides. The "Regis Bar Stool" single-product layout (tabs, dimensions/care/parts sections, gallery, swatches) is most likely assembled by an **Elementor Pro Theme Builder** "Single Product" template (condition-matched site-wide), not JetWooBuilder specifically — JetWooBuilder's own widgets weren't identified in the rendered page; low priority for further audit. |
| `jet-tabs` | Custom tabbed-content widget | Checked in an earlier pass — zero options/posts found under that name. However, the actual "Dimensions/Care/Parts/Additional Info/Delivery Info/Product Info" tabbed UI on the live product page turned out to be built with **Elementor Pro's own Nested Tabs widget** (`elementor-widget-n-tabs` / `e-n-tabs`), not `jet-tabs` at all — so this plugin does appear genuinely unused for this feature. Low priority, likely safe to ignore for the rebuild. |
| `jet-smart-filters` | Shop archive filter widgets (can include color-swatch-style attribute filters) | **Resolved — ruled out as the shop-archive color filter.** It's still active, and its class name string still appears inside 29 posts'/product-variations' own `_elementor_data` (serialized Elementor page data) — but the *actual* live shop-page color filter (checked directly on `chairforce.test/shop/`) has zero `jet-smart-filters` markup. It's Woodmart's own `WOODMART_Widget_Layered_Nav` widget instead — see the new file `12`. Whatever `jet-smart-filters` is doing in those 29 posts' Elementor data remains unconfirmed and low-priority (not shop/category filtering). |
| `jet-popup` | Popup/modal builder | Not checked this pass — possibly powers a Quick View–style popup independent of Woodmart's own (file `07` already documents Woodmart's native Quick View script enqueues). |

**How the single-product page tabs are actually built (now confirmed):**
Elementor Pro's Nested Tabs widget (desktop) plus a parallel Nested
Accordion widget (mobile, `e-n-accordion`) — both driven from the same
underlying content — with each tab/panel containing an Elementor
Text-Editor widget. Each Text-Editor widget's content is populated via an
**Elementor Dynamic Tag bound to the corresponding JetEngine meta field**
(§1 above: `dimensions`, `care`, `additional_information`) for the plain
content tabs, while the "Parts" tab/panel is left as an empty container
that JetEngine's own Listing Grid + the child theme's inline script
populate dynamically (§2). "Delivery Information" and "Product Info" tabs
observed on `regis-bar-stool` are **static, hand-written marketing copy**
in the Elementor template itself (not per-product fields) — same for
every product, not something to migrate per-product.

## 5. Rebuild implications summary

- **Care / Dimensions / Additional Information**: rebuild as a plain custom
  metabox on the product edit screen (new theme's own PHP, per this
  theme's existing ACF-based patterns — see `10-acf-integration.mdc` — or a
  hand-rolled metabox, no JetEngine dependency needed), reading/writing the
  **exact same meta keys** (`care`, `dimensions`, `additional_information`)
  — zero migration needed for the currently-active data, this is genuine
  plain post meta. `additional_information` is barely used; consider
  dropping it rather than rebuilding it, subject to confirming with content
  editors first. **Do not** proactively run the `care`/`care_tab` backfill
  from §1a — that's now a locked decision, not an open task; just flag the
  gap to the client and only act on it if/when they ask.
- **Parts**: rebuild as (a) a relationship field on the product edit screen
  (could be a plain ACF or JSX-block relationship field per this repo's own
  patterns) writing to the same `parts` meta key/array-of-IDs format, and
  (b) a new JSX block or template partial replacing the JetEngine Listing
  Grid, replicating: image + title + price + link + **an embedded, fully
  independent add-to-cart form per related part** — and replicating the
  child theme's gallery-isolation behavior (file `07` §6) so these nested
  forms can never affect the main product's gallery. Note the "Parts" tab
  content appears to lazy-render on tab activation in the live template
  (§2) — the rebuild doesn't need to preserve that specific lazy-load
  quirk, just the end behavior.
- **Gallery rendering — resolved, no risk**: file `04`'s documented
  Woodmart-native gallery markup **is** confirmed to be what's on screen
  today (§3). Build the gallery-swap logic exactly as file `04` describes;
  no Jet Woo Product Gallery compatibility work is needed. If any products
  use that plugin's optional gallery video/360° fields (§1b), decide
  separately whether the rebuild needs to support those as an unrelated,
  additive gallery-item-type feature.
- **Single-product tabs (Dimensions/Care/Parts/Additional Info/Delivery/
  Product Info)**: rebuild as a normal template section (e.g. a JSX
  accordion/tabs block) reading the JetEngine meta fields above for the
  per-product tabs, with "Delivery Information" and "Product Info" as
  static template copy (not per-product data) — no Elementor/JetEngine
  Nested Tabs dependency needed. **Located, DB-confirmed**: "Delivery
  Information" is actually stored, editable content via the
  `delivery-information-for-product-page` JetEngine Options Page (see
  file `14` §E — correction to the "static" assumption above, migrate its
  current WYSIWYG value). "Product Info" genuinely is static template
  copy, and its exact source text lives in two Woodmart `cms_block` posts
  — "Product Info dynamic content" (ID `1063733`) and "Single product
  description" (ID `1063673`) on the live `chairforce` site — just copy
  their content into the new template once, no CPT/registration needed
  for these two (see file `19` §1 for the full `cms_block` audit).
- **Other Jet\* plugins**: `jet-woo-builder` and `jet-tabs` are now
  confirmed low-priority/effectively unused for this feature set (§4).
  `jet-smart-filters` is confirmed **not** the shop/category attribute
  filter (that's a native Woodmart widget — see file `12`); whatever it
  does elsewhere is low-priority and outside this research's scope.
