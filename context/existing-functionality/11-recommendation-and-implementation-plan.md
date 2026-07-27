# 11 — Recommendation & Implementation Plan

This is the conclusion of the research in files `01`–`10`: a concrete
recommendation for how to rebuild this functionality in the new Chairforce
theme, plus a feature-by-feature implementation plan and suggested
sequencing.

**Scope note**: a later QA round expanded the research well beyond
swatches/gallery into wishlist, showrooms, the full JetEngine content-type
inventory, the `/gallery/` page, Quick View, Load More/pagination
architecture, and the carousel library — see files `13`–`19`. The table
below is updated to include those findings; file `19` is the single
consolidated "what needs re-registering" checklist if that's specifically
what you're looking for.

## Verdict: build fresh in the new theme's own architecture, reading the exact same underlying meta/option keys — don't port any Woodmart/Elementor/Jet\* code

This is "Option 2" from the original two-option discussion (salvage
Woodmart code vs. build fresh + read the same data), but the research
changes *why* it's the right call: almost none of this data has a
`register_meta()` / custom-post-type dependency on the plugins being
retired. It's nearly all plain, unregistered custom fields (`wp_termmeta`,
`wp_postmeta`). That means this isn't really a "migration" project for
most of the data — it's a **"rebuild the admin UI + rendering layer on top
of data that already survives untouched"** project. Only one item needs an
actual one-time data fix (§ below), and one item needs a genuine
drop-and-don't-rebuild decision (Linked Variations). Salvaging
Woodmart/JetEngine/Elementor code would cost more (fighting their
assumptions, dragging in dependencies you're trying to remove) than it
would save.

## Why this is lower-risk than it might look

The single biggest fear in a "retire ~10 plugins at once" project is
usually *data trapped behind a CPT or `register_meta` you can no longer
register*. We checked (file `09` §1a), and that's essentially not the case
here — the only thing with a real plugin-registration dependency was
Linked Variations (via `woodmart-core`'s CPT), and it's confirmed unused
(zero posts). Everything else is just "same meta key, new PHP reading/
writing it." Frame this to stakeholders as: **rebuild the UI, keep the
data** — not a migration project.

## Feature-by-feature plan

| Feature | Verdict | New theme implementation |
|---|---|---|
| Swatch color/image on `pa_colour` terms | **Zero migration** — plain term meta (`color`, `image`, `not_dropdown`) | New lightweight admin field on the term edit screen (custom metabox or ACF, per `10-acf-integration.mdc`) writing to the **same** term meta keys |
| Swatch visual style (shape/size/style) | **Don't preserve as config** — was theme-wide anyway, only one combo (`style 3`/`dis-style 3`/`round`/`m`) ever used | Hardcode as Sass per `12-assets-styling.mdc` — no admin field needed |
| Which attribute shows swatches | **Hardcode** — only `pa_colour` ever actually renders as a swatch | Hardcode `pa_colour` as the swatch attribute; skip the per-product override (`_woodmart_swatches_attribute`, used on 1 product site-wide) unless that specific product matters |
| Variation "extra gallery images" | **Zero migration** — `wd_additional_variation_images_data` (comma-list of attachment IDs on the variation post), no plugin dependency | Add a media-gallery field to the variation panel writing to this **same** key; read it the same way on the frontend |
| Dimensions / Care / Additional Information | **Zero migration for the schema**, **one real content-gap fix needed** | New ACF field group (WYSIWYG fields named exactly `dimensions`, `care`, `additional_information`) on `product` — same meta keys, zero code changes to existing values. **Before cutover**, run the `care`/`care_tab` backfill audit (file `10` §1a) — ~409 products have content stranded under the dead `care_tab` key that the live site currently shows blank for |
| Parts (related spare-parts) | **Zero migration** — `parts` post meta, serialized array of product IDs | ACF Relationship field named `parts`, restricted to `product`, multiple. Rebuild the front-end card (image + title + price + link + **its own independent add-to-cart**) as a properly scoped component — since you control the code, you don't need the child theme's `MutationObserver` hack (file `07` §6); just namespace event handlers to each part's own DOM subtree so they can never touch the main gallery |
| Single-product tabs (Dimensions/Care/Parts/Additional Info + Delivery/Product Info) | **Rebuild as one native section** | New JSX accordion/tabs component reading the ACF fields above; "Delivery Information"/"Product Info" are static site-wide copy (not per-product), so put them in an ACF Options page or hardcode in the template — no Elementor/Nested-Tabs dependency |
| Main product gallery + swatch-driven swap | **Confirmed straightforward** — it's WooCommerce's native gallery today, not a Jet Woo Product Gallery replacement (file `10` §3) | Build a normal WooCommerce gallery, wire swatch clicks to `found_variation`/`show_variation` (never a parallel data path) |
| Linked Variations, Booster PVS, `_wc_additional_variation_images`, `_jet_woo_template`, `woo_variation_gallery_images` | **Confirmed dead — drop entirely** | Build nothing; leave the orphaned data alone (don't delete it either, just ignore it) |
| Shop/category "filter by color" widget | **Resolved — it's Woodmart's own widget, not `jet-smart-filters`** (see file `12`); reuses the exact same term-meta swatch data | Small widget/block emitting `?filter_pa_colour=...` links from the same `pa_colour` term list + swatch renderer already built for cards; let WooCommerce's native `WC_Query` layered-nav handle the actual filtering — no new plugin dependency |
| Quick View swatches | **Zero new work** — Woodmart already reuses its single-product swatch form/JS verbatim inside Quick View | Build the single-product swatch component once; mount it inside both the single-product page and the Quick View modal |
| Quick View itself (the popup) | **Needs a full rebuild** — Woodmart-native, no plugin dependency, but also nothing to port | Any modal/dialog approach + an endpoint returning the *same* single-product component as above; replicate the "re-init via one event" contract, not any specific code — file `16` |
| Wishlist | **Zero real migration** — 2 custom DB tables, live data is 3 empty shells / 0 products | Build fresh with whatever schema fits best (Woodmart's own 2-table shape is a reasonable, proven default) — no legacy data dictates the design here — file `13` |
| Showrooms (store locator + pickup selector) | **Zero migration for the CPT data**; the "nearest pickup showroom" selector on cart/checkout is **real logic to rebuild**, not just content | New CPT + ACF fields on the same meta keys; rebuild the postcode-based nearest-showroom selector as first-class checkout code — file `14` §B |
| Other JetEngine content types (`gallery-tabs`, `category-silder-list`, `year-carousel`, `notification-bar-mes`, `review`, `feature`/`venues`/`sales-by-location` taxonomies, options pages) | **Mostly zero-migration content**, priority varies — see file `14` §K's high/medium/low breakdown | New CPTs/taxonomies/ACF Options pages per file `14`'s per-item table; confirm live usage of the "low priority" ones before spending effort |
| `/gallery/` page (infinite scroll + `gallery-category` filter + shoppable-photo popup) | **First confirmed real `jet-smart-filters` usage on the site** — a distinct page/feature, not covered by the shop-filter work above | New CPT/taxonomy + shared grid/pagination pattern (file `17`) + shared carousel/lightbox component (file `18`) + migrate the two JetEngine relations (related/featured product) once — file `15` |
| Grid pagination ("Load More" / infinite scroll / AJAX filter re-query) | **Architectural rule, not a one-off feature** — applies to shop grid, gallery page, any future paginated grid | Document-level event delegation for click/hover handlers + idempotent, event-triggered lazy init for anything needing real per-element setup (carousels, variation forms) — file `17`, mandatory reading before building any grid |
| Carousel/slider implementation (product gallery, testimonials, category slider, etc.) | **One library today (Swiper), one shared initializer for every use** — not five separate carousel implementations | Keep Swiper; build one shared `<Carousel>` component (or equivalent) used by every block/section that needs "grid → carousel" — file `18` |

## Suggested sequencing

1. **Data audit first** (cheap, no code): run the `care`/`care_tab`
   backfill query (file `10` §1a); spot-check whether any products
   actually use the Jet Woo Product Gallery video/360° fields (file `10`
   §1b) before deciding if that's in scope.
2. **Admin UI**: ACF field groups for term swatches, product fields
   (`dimensions`/`care`/`additional_information`/`parts`), variation
   gallery images.
3. **Frontend rendering**: grid-card swatches → single-product
   swatches+gallery → Parts section/tabs — in that order, since
   single-product logic builds on the same "swatch skins a real
   `<select>`" pattern as the grid (file `09` §2).
4. **Styling**: hardcode the one confirmed swatch style as Sass (file `09`
   §3).
5. **Shop/category filter widget** (file `12`): can be built any time after
   step 2 (term swatch admin UI) is in place, since it depends on nothing
   else — it's a thin read-only rendering of the same term data plus
   native WooCommerce query-string filtering.
6. **Foundational, do early rather than late**: the Load More/event-
   delegation rule (file `17`) and the shared carousel component (file
   `18`) are used by nearly everything above (grid swatches, quick shop,
   quick view, gallery page, testimonials, category slider) — build these
   two shared pieces of infrastructure before the features that depend on
   them, not as an afterthought per-feature.
7. **Lower priority / can trail the core swatch+gallery work**: wishlist
   (file `13`), showrooms (file `14` §B — except the pickup-selector logic,
   which should be scheduled with checkout work specifically), the
   `/gallery/` page (file `15`), and the remaining JetEngine content types
   (file `14` §A/C/E) — none of these block or are blocked by the
   swatch/gallery rebuild, so sequence them based on business priority
   rather than technical dependency.
8. **Master checklist** (file `19`): use this as the final sign-off list
   before actually deactivating Elementor/Jet\*/Woodmart — walk every row
   and confirm its "New theme owner" column is actually built (or
   deliberately dropped) before flipping plugins off.

## Testing note

Because `chairforce-2026` (this workspace) and `chairforce` (the live
reference site) share near-identical product/postmeta data (see the
environment note in file `09` §0 and file `README.md`'s Third QA
addendum), development and before/after comparisons can safely happen
against `chairforce-2026` without touching the live site — just remember
plugin-*active-state* facts differ between the two, so re-verify
anything plugin-activation-dependent against `chairforce` if in doubt.
