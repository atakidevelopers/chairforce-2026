# Woodmart Color/Attribute Swatches — Existing Functionality Research

**Looking for overall rebuild progress/status?** See `context/PROGRESS.md`
(project-wide phase tracker) — this README only indexes the research docs.

This folder documents, in detail, how the **attribute swatches system** (color
swatches, image swatches, text swatches) currently works in:

- `wp-content/themes/woodmart` (parent theme — Woodmart by XTemos)
- `wp-content/themes/woodmart-child` (this site's child theme, "chairforce")
- Supporting plugin: `wp-content/plugins/woocommerce-jetpack` (Booster for
  WooCommerce — contains a *second*, fully independent swatches
  implementation whose live activation status could not be confirmed from
  static code; see file `08` for what to check on the live site)
- Supporting plugin: `wp-content/plugins/woodmart-core` — not originally in
  scope (theme-only), but pulled in during QA because it's where the
  "Linked Variations" custom post type (`woodmart_woo_lv`) is actually
  `register_post_type()`-ed (the theme itself never calls
  `register_post_type`/`register_taxonomy`/`register_meta` for anything
  swatch-related). See files `02` §6 and `08` §A.
- Scope expansion confirmed with the site owner: **Elementor, Elementor
  Pro, and all `Jet*` plugins (JetEngine, JetWooBuilder, JetTabs,
  JetSmartFilters, JetPopup, JetWooProductGallery) are also being retired**
  alongside Woodmart. A DB-verification pass turned up several
  product-page features built on these plugins that are adjacent to (and
  in one case, directly load-bearing for) the swatch/gallery system — see
  the new file `10-jetengine-fields-and-parts-system.md`.

**Purpose:** This is pure research/reverse-engineering documentation to
support replacing Woodmart with a new custom theme while preserving the same
end-user behavior:

1. Color/attribute swatches on product cards (shop/category grid), where
   clicking or hovering a swatch swaps the card's product image.
2. Color/attribute swatches on the single product page, wired to the
   variation form and to the main product image gallery (including an
   "additional variation images" mini-gallery per variation).

**Non-negotiable constraints that shaped this research:**

- No code in the live theme/plugins was modified. This is read-only research.
- All output lives under `context/existing-functionality/` only.
- The underlying WordPress/WooCommerce data (products, attribute taxonomies,
  term meta, variation meta) is assumed to stay exactly as-is. Any rebuild
  must keep reading the **same** meta keys / option keys documented here
  (see file `02-data-model-and-storage.md`) so no data migration is required.

## How to read this folder

| File | Contents |
|---|---|
| `01-architecture-overview.md` | High-level map of all moving parts (PHP modules, templates, JS, CSS) and how they connect. Start here. |
| `02-data-model-and-storage.md` | Exactly where swatch data lives: taxonomy term meta, `wp_options` rows, post/variation meta. This is the contract a rebuild must honor to reuse existing product data. |
| `03-product-card-grid-swatches.md` | Deep dive: product card / shop-loop swatches, the two rendering modes (simple swatch list vs. full variation-form "quick shop"), and the click/hover → image-swap JS behavior. |
| `04-single-product-page-swatches-and-gallery.md` | Deep dive: single product page variation form, swatches markup, and how swatch selection drives the main image gallery + thumbnails + the "additional variation images" mini-gallery. |
| `05-css-styling-reference.md` | CSS architecture for `.wd-swatch*` classes: sizes, shapes, states (active/disabled/hover), the 4 visual "styles" and 3 "disabled styles". |
| `06-admin-settings-reference.md` | Every admin control that affects swatches: Theme Settings → Shop → Attribute swatches, the per-attribute settings screen (Products → Attributes → edit), and the per-term "Extra" metabox. |
| `07-child-theme-overrides.md` | Site-specific overrides/hacks layered on top of the parent theme in `woodmart-child` (swatch hover-to-select, a custom "Parts" swatch sub-system, disabled swatches caching, gallery image id merging). |
| `08-linked-variations-and-booster-plugin.md` | Adjacent/overlapping systems to be aware of: Woodmart's "Linked Variations" (cross-product swatches) and WooCommerce Jetpack/Booster's own, independent "Product Variation Swatches" module. |
| `09-rebuild-considerations.md` | Practical checklist/recommendations for reimplementing this in a new theme without changing product data. |
| `10-jetengine-fields-and-parts-system.md` | Authoritative (pulled straight from the `jet_engine_meta_boxes` option) definition of JetEngine's two product metaboxes — three WYSIWYG fields (`dimensions`/`care`/`additional_information`) and one relational field (`parts`, injected into WooCommerce's Product Data panel) — plus the "Parts" front-end rendering system and its legacy-ACF-data migration risk. |
| `11-recommendation-and-implementation-plan.md` | **The conclusion of this research.** Final recommendation (build fresh, read the same data, don't port Woodmart/Elementor/Jet\* code), a feature-by-feature implementation plan mapped to this repo's own conventions (ACF, JSX blocks, Sass), and suggested sequencing. Start here if you just want "what do we do next." |
| `12-shop-archive-filters.md` | The shop/category "filter by color" sidebar widget — **confirmed to be Woodmart's own `WOODMART_Widget_Layered_Nav`** (an extended WooCommerce native attribute filter), reusing the exact same term-meta swatch data as the card/single-product swatches. Corrects an earlier `jet-smart-filters` guess (files `09`/`10`), which was checked live and ruled out. |
| `13-wishlist.md` | Woodmart's native wishlist feature (two custom DB tables) — confirmed live data is 3 empty wishlist shells, 0 products, so effectively zero migration burden. |
| `14-jet-content-types-and-showrooms.md` | **The authoritative full inventory** of every JetEngine custom post type, taxonomy, relation, options page, and Listing Grid template on the live site (pulled directly from JetEngine's own `wp_jet_post_types`/`wp_jet_taxonomies` tables) — supersedes file `10`'s partial picture. Includes full detail on the `showrooms` CPT and its bespoke, hand-written "nearest pickup showroom" selector in the child theme. |
| `15-gallery-page.md` | The `/gallery/` page: `gallery-tabs` CPT + `gallery-category` taxonomy + JetEngine Listing Grid (infinite scroll/load-more) + **the first confirmed real usage of `jet-smart-filters`** anywhere on the site + a "shoppable photo" popup with related/featured product relations. |
| `16-quick-view.md` | Confirms Quick View needs a full rebuild (it's Woodmart-native, not a separate plugin) and documents the one behavioral contract worth replicating: Quick View reuses the *same* single-product swatch/gallery component via a `wdQuickViewOpen` event, rather than a second parallel template. |
| `17-load-more-and-event-delegation.md` | The architectural rule that makes Woodmart's Load More/infinite-scroll/AJAX-filtering "just work" with swatches, carousels, and quick-shop forms with zero rebind code: document-level event delegation + idempotent, event-triggered lazy init. Mandatory reading before implementing any grid/pagination feature in the new theme. |
| `18-carousel-library.md` | Confirms Swiper is the *only* carousel library used anywhere in Woodmart (including the single-product gallery), via one shared, markup/data-attribute-driven initializer. Recommends keeping Swiper + building one shared carousel component for the new theme. |
| `19-master-rebuild-registration-checklist.md` | **The consolidated "what do we need to re-register" checklist** — every CPT/taxonomy/relation/options-page/DB-table/Smart-Filter/popup discovered across all files above, organized by keep-and-migrate vs. confirmed-safe-to-drop, plus a short list of open follow-ups. |
| `20-reviews-testimonials.md` | Explains the custom `review` CPT (14 posts): confirmed to be hand-curated marketing testimonials (all 5-star, no product relationship), not WooCommerce's native per-product review system (confirmed unused — zero `comment_type='review'` rows exist). |

## One-paragraph summary

Woodmart stores swatch *appearance* (color hex / image / "text only") as
**term meta** (`color`, `image`, `not_dropdown`) on each product attribute
taxonomy term (e.g. a `pa_color` term like "Red"), plus **hint text** in
`pa_term_hint` term meta. Swatch *styling* (shape, size, visual style,
whether to show on the grid, whether clicking changes the product image) is
stored as **`wp_options` rows keyed per-attribute** (`woodmart_pa_colour_swatch_style`,
etc. — the option *value* is a fixed string like `1`/`2`/`3`, chosen from a
picker on the **Products → Attributes → Edit/Add attribute** screen itself,
distinct from the per-term "Configure terms" screen used for `color`/`image`),
not as term meta.
On the shop grid, `woodmart_swatches_list()` renders either a lightweight
swatch-only list (swaps the product-card `<img src>` on click, see
`swatchesOnGrid.js`) or, if "Quick shop on variation click" is enabled, a full
variation `<form>` with swatches embedded in the card (`quickShopVariationForm.js`).
On the single product page, `woocommerce/single-product/add-to-cart/variable.php`
renders one `<div class="wd-swatches-product">` per attribute row, and
`swatchesVariations.js` wires swatch clicks to WooCommerce's native
`found_variation`/`show_variation` events plus a theme-specific "additional
variation images" mini-gallery system (`variation-gallery-new.php` /
`variation-gallery.php`) that swaps the *entire* main image gallery, not just
one image, when a variation is selected.

## QA addendum (post-research clarifications)

A follow-up QA pass answered four specific questions that aren't obvious
from reading the code cold; the answers are folded into the relevant files
above (not repeated here), but as a map of *where*:

- **"How do users set swatch colors, given Theme Options is going away?"**
  → file `02` §1/§2 (the "survives theme removal?" callouts) and file `06`'s
  intro. Short answer: `color`/`image`/`not_dropdown` term meta and the
  per-attribute `wp_options` rows are **not** part of Woodmart's bundled
  Theme Options blob and are completely safe; only the *global* Theme
  Settings → Shop toggles (grid attribute, limits, gallery storage method,
  etc.) live in that single serialized option and go inert when the theme
  is removed.
- **"Does the theme register anything that needs porting?"** → file `02`
  §6 and file `08` §A (the `woodmart_woo_lv` CPT is registered by the
  `woodmart-core` **plugin**, not the theme — a scope gap worth flagging).
  Otherwise: no `register_meta`/`register_taxonomy` calls exist for any
  swatch data, so there's nothing else to "port" — just re-read the same
  keys.
- **"Confirm we only need to code for one specific style/shape/size
  combination."** → file `05`'s new callout and file `09` §3, confirming
  the captured classes decode to `swatch_style=3`, `swatch_dis_style=3`,
  `swatch_size=m`, `swatch_shape=round`, and that `wd-all-shown` is a
  client-side-only runtime class (never server-rendered), not a style
  variant to implement.
- **"Why does the gallery seem to filter images by color?"** → file `04`'s
  existing "Gallery replacement" section (already covered this in detail);
  short answer: it's not a filter, it's a full gallery *swap* per variation,
  configured via a per-variation "Additional variation images" field on
  **Products → [product] → Variations → [variation]**.

## Second QA addendum — DB-verified facts (DDEV `wp-cli` pass)

A follow-up pass used `ddev wp` (WP-CLI + direct DB queries) to confirm
facts that were previously only inferable from static code, and to
investigate two admin metabox fields (`care`, `Additional Information`)
raised by the user. Highlights (full detail inline in the relevant files):

- Every "verify on the live site" item flagged in file `09` §0 is now
  **confirmed**, not just flagged — see the updated tables in files `02`,
  `06`, `08`, `09`. Notably: `grid_swatches_attribute = pa_colour` (not
  `pa_color`), grid swatch limit is **on** at 3, single-product limit is
  **off**, `variation_gallery_storage_method = new`, Linked Variations has
  **zero** posts (fully unused), Booster's PVS module has **zero**
  attributes ever configured (fully dead), and `_wc_additional_variation_images`
  has **zero** rows anywhere (confirmed dead code).
- Term meta `color` values are stored as **`rgb(r,g,b)` strings**, not hex
  (`#rrggbb`) — file `02` §1 corrected.
- **"Care"/"Dimensions"/"Additional Information" are JetEngine fields, not
  Woodmart's** — plain post meta (`care`, `dimensions`,
  `additional_information`) via a JetEngine custom meta box. New file `10`.
- **The "Parts" mystery from file `07` is resolved**: it's a JetEngine
  relational field (`parts` post meta, array of related product IDs) plus
  an Elementor/JetEngine Listing Grid template that renders each related
  product with its own embedded, independent add-to-cart form — explaining
  exactly why the child theme needed gallery-isolation protection. New
  file `10`.
- **Scope confirmed larger than "just Woodmart"**: Elementor and all
  `Jet*` plugins are also being retired, so the JetEngine-dependent items
  above are now in-scope for the rebuild, not just adjacent trivia.
- **New high-priority open question (now resolved, see Third addendum
  below)**: the Jet Woo Product Gallery plugin's "Slider" widget is
  explicitly enabled in `wp_options` — the *actual* live single-product
  gallery markup may not be Woodmart's own `product-image.php` template at
  all, which would mean file `04`'s gallery-swap JS is targeting the wrong
  DOM structure on the live site. See file `10` §3.

## Third QA addendum — verified against the real live/reference site

**⚠️ Environment correction, read this first.** Everything above (including
both prior "DB-verified" passes) was checked against the `chairforce-2026`
DDEV project — this repo's own workspace. It turns out that is a **separate,
partially-stripped-down copy**: several plugins (Elementor, Elementor Pro,
all `Jet*` plugins, `woodmart-core`, `woocommerce-jetpack`/Booster) are
already deactivated there, presumably as prep for this rebuild. There is a
**second, separate DDEV project, `chairforce`** (`~/Projects/wp/chairforce`,
served at `chairforce.test` — no `-2026`), which is the actual **live/full
reference site** with Woodmart + Elementor + Elementor Pro + every `Jet*`
plugin + `woodmart-core` + Booster all genuinely active. The user pointed us
at `http://chairforce.test/product/regis-bar-stool/` specifically to check
this real site. This addendum re-verifies everything against **that**
project's DB and rendered HTML. Where it disagrees with earlier passes, this
addendum wins.

**What changed vs. earlier passes:**

- **Booster for WooCommerce (`woocommerce-jetpack`) is actually active** on
  the live site (contradicts the earlier "inactive" note in file `08`). It
  just doesn't matter for swatches: its Product Variation Swatches module is
  explicitly toggled off (`wcj_product_variation_swatches_enabled` = `no`),
  so the "dead code, never configured" conclusion in file `08` §B still
  holds — active plugin, inactive *module*.
- **Every Woodmart product attribute has its own `woodmart_pa_{attr}_swatch_*`
  option rows** (14 attributes found: assembly, backrest, base-type, brand,
  colour, features, folding, indoor, indoor-outdoor, last-chance-to-buy,
  material, seat, size, stackable) — Woodmart auto-creates these the moment
  an attribute's edit screen is ever visited, regardless of whether it's
  actually used as a swatch. **Only `pa_colour` deviates from Woodmart's
  factory defaults** (style `3`/dis-style `3`/shape `round`/size `m`, vs.
  every other attribute sitting at the default `style 1`/`shape round`/
  `size default`). WooCommerce's own `attribute_type` column is `select` for
  *all* attributes including colour — Woodmart does **not** use WC's native
  attribute-type field to decide swatch vs. dropdown; it's driven purely by
  whether the attribute's terms carry `color`/`image` term meta (confirmed:
  `pa_colour` terms do, `pa_size` terms don't). This confirms file `02`'s
  and `09`'s "only hardcode for `pa_colour`" recommendation is correct even
  under closer scrutiny.
- **New per-product override discovered**: `_woodmart_swatches_attribute`
  post meta on the *product* lets an individual product override which
  attribute taxonomy is used for its swatches, instead of the global
  `grid_swatches_attribute` theme setting. It exists on essentially every
  product but is populated (non-empty) on only **1 product site-wide** — so
  it's real, registered behavior worth a fallback check in the rebuild, but
  not something to design around as a primary mechanism. See file `02`.
- **Gallery-rendering question is resolved**: on the live
  `regis-bar-stool` page, the main gallery markup is unambiguously
  Woodmart's own — `<div class="woocommerce-product-gallery ... wd-has-thumb
  thumbs-position-bottom ...">` wrapping a `.woocommerce-product-gallery__wrapper.wd-carousel.wd-grid`
  — exactly what file `04` documents from static code. Jet Woo Product
  Gallery's only footprint on this page is a single global, empty
  `<div class="pswp jet-woo-product-gallery-pswp">` PhotoSwipe container
  appended in `wp_footer` (a lightbox/zoom shell, populated dynamically on
  click) plus its own per-product meta fields for optional video/360°
  gallery items (`_jet_woo_product_video_type`,
  `_jet_woo_product_youtube_video_url`, `_jet_woo_product_vimeo_video_url`,
  `_jet_woo_product_self_hosted_video`, `_product_360_image_gallery` — all
  empty on this product, so not exercised here, but worth a quick scan for
  other products that do use gallery video/360 before finalizing gallery
  rebuild scope). **File `04`'s documented gallery-swap JS is targeting the
  correct, real DOM structure** — no correction needed there.
- **JetEngine's "Dimensions"/"Care"/"Additional Information"/"Parts" fields
  now have an authoritative, DB-verified definition** (pulled directly from
  the `jet_engine_meta_boxes` option, not inferred) — see the rewritten file
  `10`. Short version: two JetEngine meta boxes, three plain WYSIWYG fields
  (`dimensions`, `care`, `additional_information`) on post type `product`,
  plus one relational field (`parts`, type `posts`, multiple) injected into
  WooCommerce's own Product Data panel as a "related" tab.
- **Legacy ACF data discovered and confirmed orphaned**: `dimensions` and
  `care_tab` were originally **Advanced Custom Fields** fields (their
  field-key-shadow meta rows, `_dimensions` → `field_5fd81d83dcbdf` and
  `_care_tab` → `field_5f84f62021154`, are pure ACF plumbing) from before
  JetEngine took over. ACF itself is **not installed** on the live site at
  all today, so these shadow keys are 100% inert. However this is a
  **real data-completeness risk for migration**: `care_tab` still holds
  content on 409 products while the *currently rendered* field `care` only
  holds content on 542 — the two sets don't fully overlap, so some products
  likely show a blank "Care" tab today because their content is stranded
  under the old `care_tab` key. **Recommend a pre-migration audit**: for
  every product, if `care`/`dimensions` is empty but `care_tab`/`_dimensions`'s
  sibling has content, that's a real (currently-broken) content gap to
  fix/migrate, not something to silently drop. See file `10`.
- **`jet-woo-builder`'s per-product template override (`_jet_woo_template`
  meta) is registered but unused** — zero non-empty values found across
  every product on the live site. The whole catalog relies on a single
  global Elementor Theme Builder "Single Product" template (condition-
  matched to all products), not per-product template swaps. Nothing to
  replicate here beyond "one single-product layout for everything."
- **One orphaned, unexplained meta key**: `woo_variation_gallery_images`
  has non-empty data on 549 products (a single numeric value per row, e.g.
  attachment/post ID `1060740`), but **no file anywhere in the current
  `wp-content/plugins/` or `wp-content/themes/` references this string** —
  it's leftover data from a plugin that's since been fully removed from the
  filesystem. Since nothing reads it today, it's out of scope for
  replicating *current* behavior, but flagged here in case the data itself
  (not the mechanism) turns out to matter later.
- **The shop/category "filter by color" widget is resolved, and it is NOT
  `jet-smart-filters`.** Live inspection of `chairforce.test/shop/` found
  zero `jet-smart-filters` markup; the filter UI (`wd-swatches-filter`
  classes) is generated by Woodmart's own `WOODMART_Widget_Layered_Nav`
  widget — an extended version of WooCommerce's native "Filter Products by
  Attribute" widget, reusing the *same* term-meta swatch data as the
  card/single-product swatches. See the new file `12` for the full
  mechanism and rebuild recommendation. `jet-smart-filters` is still active
  and its markers do appear in 29 unrelated postmeta rows, but it's
  confirmed not responsible for this feature.
