# 09 — Rebuild Considerations & Checklist

Practical guidance for reimplementing this functionality in the new
Chairforce theme (JSX blocks / ACF blocks architecture, per this repo's own
conventions) **without migrating or altering existing product data**.

## 0. Live-site unknowns — all resolved via `ddev wp` DB access

> **⚠️ Environment note, read first.** There are **two separate DDEV
> projects** relevant to this research: `chairforce-2026` (this rebuild's
> own workspace — several plugins, e.g. Elementor and all `Jet*` plugins,
> `woodmart-core`, and `woocommerce-jetpack`/Booster, are already
> deactivated there in prep for the rebuild) and **`chairforce`**
> (`~/Projects/wp/chairforce`, served at `chairforce.test` — the actual
> **live/full reference site**, with every one of those plugins genuinely
> active). Early passes of this research queried `chairforce-2026` only;
> a later pass re-verified everything against `chairforce` directly
> (including rendered HTML from `http://chairforce.test/product/regis-bar-stool/`).
> The two databases hold near-identical product/postmeta/option *data*
> (this rebuild's copy appears to be a full DB clone with only plugins
> toggled off afterward), so data-level facts below were already accurate
> either way — but plugin-*active-state* facts (item 3 below in
> particular) were corrected after checking the right project. If you need
> to re-verify anything yourself, always run `ddev wp` from inside
> `~/Projects/wp/chairforce` for "what's really live" questions.

This research started out entirely from reading theme/plugin source files,
which left several facts unconfirmable from static code alone. A follow-up
pass used `ddev wp` (WP-CLI + direct SQL queries against the actual
database) to resolve every one of them:

1. **`variation_gallery_storage_method`** — **`new`**, confirmed both by
   the option value and by data: 1,023 non-empty
   `wd_additional_variation_images_data` rows vs. 0
   `woodmart_variation_gallery_data` rows. See file `02` §4.
2. **`_wc_additional_variation_images`** meta key (file `07` §4) —
   **confirmed dead, zero rows anywhere in `wp_postmeta`.** Ignore this
   code path entirely.
3. **Booster's "Product Variation Swatches" module** (file `08` §B) —
   **confirmed dead, for a specific reason.** The `woocommerce-jetpack`
   (Booster) plugin is actually **active** on the real live site (the
   "inactive" finding in an earlier pass was from checking the wrong DDEV
   project — see the environment note above), but the PVS module's own
   enable toggle (`wcj_product_variation_swatches_enabled`) is explicitly
   `no`, and zero `wcj_attribute_type_%` options were ever set for any
   attribute either way. No conflict risk, nothing to preserve.
4. **"Parts" custom-Parts-section markup** (file `07` §6) — **resolved,
   fully documented in new file `10`.** It's a JetEngine relational field
   (`parts` post meta, 196 products populated) rendered via a JetEngine
   Listing Grid + Elementor template, with each related "part" product
   getting its own embedded, independent add-to-cart form — which is
   exactly why gallery isolation was needed.
5. **Woodmart "Linked Variations"** (file `08` §A) — **confirmed fully
   unused**: `linked_variations` toggle is on, but zero `woodmart_woo_lv`
   posts exist anywhere. Drop this feature entirely from the rebuild.
6. **Every other setting in file `06`** — now has a confirmed live value
   inline in that file's tables (grid attribute = `pa_colour`, grid limit
   on at 3, single-product limit off, use-variation-images off, labels-on
   tooltip replaced with text, scroll-to-top off, quick-shop mode =
   `select_options` only, per-attribute style/dis-style/shape/size =
   `3`/`3`/`round`/`m`).

### New unknowns surfaced by this same pass — status

1. **Scope is larger than originally framed.** The site owner confirmed
   Elementor, Elementor Pro, and all `Jet*` plugins (JetEngine,
   JetWooBuilder, JetTabs, JetSmartFilters, JetPopup,
   JetWooProductGallery) are *also* being retired alongside Woodmart — not
   just the swatch system. This pulls the JetEngine `care`/`dimensions`/
   `additional_information` fields and the entire "Parts" system (file
   `10`) into scope as things that need full rebuilding, not just
   incidental trivia.
2. **✅ RESOLVED — the live single-product gallery is Woodmart's own
   markup.** Checked directly against the rendered HTML of
   `http://chairforce.test/product/regis-bar-stool/`: the visible gallery
   is `.woocommerce-product-gallery.wd-carousel` (Woodmart/WooCommerce
   native), exactly what file `04`'s gallery-swap JS targets. Jet Woo
   Product Gallery's "Slider" toggle being on does not mean its widget
   replaces the gallery here — the plugin's only footprint on this page is
   a global PhotoSwipe lightbox shell appended in `wp_footer` (enhancing
   clicks on the existing gallery, not replacing it) plus optional
   video/360°-image gallery-item fields (unused on this specific product).
   **No changes needed to file `04`'s approach.** Full detail in file `10`
   §3.
3. **`jet-woo-builder` and `jet-tabs` — checked, confirmed low-priority/
   unused for this feature set.** `jet-woo-builder`'s per-product template
   override field has zero non-empty values across every product (the
   catalog uses one global Elementor Theme Builder template, not per-
   product overrides). The tabbed Dimensions/Care/Parts/etc. UI turns out
   to be built with Elementor Pro's own Nested Tabs widget, not `jet-tabs`.
   **The shop-archive color filter is resolved and it is NOT
   `jet-smart-filters`** — it's Woodmart's own `WOODMART_Widget_Layered_Nav`
   widget (an extended WooCommerce native "Filter Products by Attribute"
   widget), confirmed live by inspecting the rendered shop page (zero
   `jet-smart-filters` markup found there) and reading the widget's PHP
   source. It reuses the *exact same* term meta (`color`/`image`/
   `not_dropdown`) and per-attribute style settings as the swatches
   themselves. See the new file `12` for full detail — this turns out to be
   one of the lowest-effort items in the whole rebuild, not an open
   question. `jet-smart-filters` itself is still active and its markers do
   appear in some unrelated `_elementor_data` blobs, but it is confirmed
   **not** the source of shop/category attribute filtering.

## 1. Data layer — read, don't migrate

The new theme's PHP should read data from **exactly** these existing
locations (full detail in file `02`):

| Data | Source |
|---|---|
| Swatch color/image/text-only | Term meta `color`, `image`, `not_dropdown` on `pa_*` taxonomy terms |
| Term hint tooltip | Term meta `pa_term_hint` |
| Per-attribute style/shape/size/disabled-style/etc. | `wp_options` rows `woodmart_pa_{attribute}_{field}` (getter pattern: `get_option('woodmart_' . $full_taxonomy_name . '_' . $field)`) |
| Which attribute shows on the grid | Theme Setting `grid_swatches_attribute`, overridable per-product via post meta `_woodmart_swatches_attribute` |
| Additional/mini-gallery images per variation | `wd_additional_variation_images_data` (variation post meta, new method) **or** `woodmart_variation_gallery_data` (parent product post meta, old method) — pick based on the live setting (§0.1 above) |
| Linked-variations groups | ~~`woodmart_woo_lv` CPT + its 3 post-meta keys~~ — **confirmed unused, drop entirely, see §0** |
| JetEngine product fields (Care/Dimensions/Additional Info) | Plain post meta `care` / `dimensions` / `additional_information` — see file `10` §1 |
| "Parts" related-products list | Post meta `parts` (serialized array of product IDs) — see file `10` §1 |

Recommendation: write small, well-named PHP helper functions in the new
theme's `/lib/` or `/includes/` (per this repo's own architecture rules)
that **directly replicate** the read-side behavior of
`woodmart_has_swatch()`, `woodmart_has_swatches()`,
`woodmart_get_option_variations()`, and `woodmart_wc_get_attribute_term()`
— these are small, pure, well-isolated functions and there's no reason to
reinvent their logic; a near-literal port (renamed to the new theme's own
prefix/namespace) minimizes risk of subtly changing swatch-eligibility
rules that content editors have already configured against.

### 1a. Registration dependencies — what actually breaks when Woodmart is removed

QA verified (via grep across the theme, child theme, and the
`woodmart-core` plugin) that there is **no** `register_meta()`,
`register_taxonomy()`, or `register_post_type()` call anywhere for
swatch-related data, with one exception:

| Data | Registration dependency | Survives theme removal? |
|---|---|---|
| `pa_*` attribute taxonomies | WooCommerce core, from the `wp_woocommerce_attribute_taxonomies` table | Yes — zero theme dependency |
| Term meta (`color`/`image`/`not_dropdown`/`pa_term_hint`) | None — plain unregistered custom fields | Yes — plain `wp_termmeta` rows |
| Per-attribute style/shape/size `wp_options` rows | None — plain `update_option()`/`get_option()` calls | Yes, but become **unread** (no code left to call `woodmart_wc_get_attribute_term()`) |
| Theme Settings → Shop bundle (grid attribute, limits, gallery storage method, etc.) | Woodmart's own `Options` framework, one serialized `wp_options` row (`xts-woodmart-options`) | Row persists but is **fully inert** — `woodmart_get_opt()` is theme code, nothing populates it anymore |
| `woodmart_woo_lv` CPT (Linked Variations) | **`register_post_type()` in the `woodmart-core` plugin**, not the theme | Only if `woodmart-core` stays active; if it's also removed, existing posts become an orphaned/unregistered post type |

Practical upshot: everything in files `02` §1/§2/§5 (term meta, per-attribute
options, product-level overrides) is safe to keep reading as-is with zero
migration. Everything in file `06` (global Theme Settings) has no life
outside Woodmart's own code and should just be re-decided/hardcoded fresh
(see §3 below for the one confirmed case — swatch style/shape/size). The
Linked Variations CPT is the one item that depends on a *plugin* decision
independent of the theme swap — confirm whether `woodmart-core` is staying
before deciding if/how to port it.

## 2. Rendering layer — two things to design fresh

Unlike the data layer, the **rendering** should be redesigned to fit the
new theme's actual architecture (JSX blocks preferred per this repo's
rules) rather than porting Woodmart's PHP-template-string-concatenation
approach verbatim. Two independent pieces to design:

### A. Grid/card swatches
- A JSX or ACF block (or an enhancement to however product-card rendering
  already works in the new theme/Blokki integration) that:
  1. Resolves the "grid attribute" (global setting + per-product override).
  2. Renders one swatch per term (color/image/text priority from file `03`).
  3. On click, swaps the card's main image `src`/`srcset` using each
     swatch's associated variation image data — no need to preserve
     Woodmart's exact `data-image-src` attribute names, but the *behavior*
     (toggle on/off, restore original image, don't select a real
     variation unless doing full quick-shop) should match current UX
     unless deliberately changing it.
  4. Decide up front whether to support both of Woodmart's two "quick shop"
     modes (file `03`) or consolidate to one — this is a product decision,
     not purely technical; flag it with the site owner if unclear.

### B. Single-product swatches + gallery
- A properly-scoped React/JS module (or vanilla JS, matching whatever the
  new theme's single-product template uses for its add-to-cart form) that:
  1. Renders one visual swatch per term, **bound to the real WooCommerce
     variation `<select>`** (don't reinvent variation-matching/stock/price
     logic — proxy into WooCommerce core's own `wc_variation_form()` /
     `found_variation`/`show_variation` events, exactly as Woodmart does in
     file `04`). This is the single most important architectural lesson
     from this research: **swatches are a UI skin over the real `<select>`,
     never a parallel data path**, except for the child theme's bespoke
     "Parts" system (file `07` §6) which deliberately breaks this rule and
     is harder to maintain as a result — don't repeat that pattern
     elsewhere unless there's a specific reason (e.g. genuinely
     independent sub-forms that must never touch the main gallery, which
     is exactly the case Parts solves for).
  2. On `found_variation`/reset: check the resolved variation's associated
     image count (main image + any "additional variation images"); if
     more than one, rebuild the whole gallery/thumbnail-rail; otherwise
     just swap the single visible image.
  3. Recomputes swatch enabled/disabled state from WooCommerce's own
     per-`<option>` enabled/invalid-combination info after every attribute
     change.

## 3. CSS — a much smaller task than in Woodmart

Woodmart's CSS supports 4 active styles × 3 disabled styles × 3 shapes × 6
sizes, all admin-configurable per attribute (file `05`). **Confirmed via QA:
this site only needs one combination hardcoded** — the classes captured
from the live product card (`wd-bg-style-3 wd-text-style-3 wd-dis-style-3
wd-size-m wd-shape-round`) and single product page
(`wd-swatches-product wd-swatches-single wd-bg-style-3 wd-text-style-3
wd-dis-style-3 wd-size-m wd-shape-round`) both decode to the same
attribute-level settings: active style **3** (plain border), disabled
style **3** (opacity + single diagonal line), size **m**, shape **round**.
Per this repo's Sass architecture rules, implement just this one visual
treatment as clean Sass mixins/variables in a `src/sass/swatches/` (or
similar) directory — **do not** build out the full admin-configurable
matrix; there is no requirement to make style/shape/size editable per
attribute in the new theme unless a future request asks for it. (Note:
`wd-all-shown`, also seen in the captured card classes, is not a style to
implement — it's a JS-runtime-only state class, see file `03`'s note on
`swatchesLimit.js`. The rebuild's default should be "show all swatches,
never collapse" unless swatch-limiting is explicitly wanted.)

## 4. Shop-archive "filter by color" widget — resolved, not a non-goal

- **Resolved** (previously listed here as an open non-goal, and previously
  misattributed to `jet-smart-filters` or WP Grid Builder). The
  `wd-swatches-filter` markup is generated by Woodmart's own
  `WOODMART_Widget_Layered_Nav` widget — a skinned version of WooCommerce's
  native "Filter Products by Attribute" widget, reusing the exact same
  term-meta swatch data (`color`/`image`/`not_dropdown`) documented in file
  `02`. Confirmed live: the rendered shop page has zero `jet-smart-filters`
  markup. Full detail, including the rebuild recommendation, is in the new
  file `12`. This is genuinely one of the *smaller* rebuild items, since
  the data and swatch-rendering logic are already shared with the
  card/single-product swatches — it only needs the WooCommerce native
  `filter_{attribute}` query-string wiring on top.
- The "grid image gallery" hover feature (`imagesGalleryInLoop.js`, file
  `03`) is **not** swatches — it's a separate hover-to-preview-other-photos
  feature that happens to share the same `<img>` element. Only needs
  replicating if the site currently relies on it independent of swatches.
- Quick View / Quick Shop popups reuse the exact same swatch
  markup/JS as the single product page (file `04`) — if the new theme
  keeps some form of quick-view, its swatches should reuse the single
  product page's own component/module rather than being built a third
  time.
