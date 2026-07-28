# 14 — JetEngine Custom Content Types, Taxonomies, Relations & Showrooms

Resolves QA questions: *"There are 'Jet listings' on the current site, we
shall need to take inventory of these..."* and *"The Showrooms: how are
these built, what do we need to preserve?"*

This supersedes/extends file `10`'s partial picture — that file only
covered the two `jet_engine_meta_boxes` (Dimensions/Care/Additional Info +
Parts). **This file is the authoritative, complete inventory**, pulled
directly from JetEngine's own dedicated database tables
(`wp_jet_post_types`, `wp_jet_taxonomies`) rather than guessed from
front-end markup.

## Where JetEngine actually stores custom post type / taxonomy definitions

Not in `wp_options` (as file `10` assumed for the meta boxes, which *are*
options-based) — JetEngine's **Custom Content Types module** uses its own
dedicated tables:

```
wp_jet_post_types    (id, slug, status, labels, args, meta_fields)
wp_jet_taxonomies    (id, slug, object_type, status, labels, args, meta_fields)
```

`status` distinguishes what kind of "content type" each row is:
`publish` = a real custom post type, `page` = a JetEngine "Options Page"
(a single virtual settings screen, not a repeatable post type),
`relation` = a JetEngine Relationship definition (uses further tables:
`wp_jet_rel_default`, `wp_jet_rel_default_meta`), `query` = a saved Query
Builder query consumed by one or more Listing Grid widgets.

## A. Real custom post types (6)

| Slug | Live post count | Fields (`meta_fields`) | Purpose |
|---|---|---|---|
| `showrooms` | 7 | `warehouse` (text), `time` (text), `phone` (text), `email` (text), `address` (text), `_description` (textarea), `map` (media), `showroom_gallery` (gallery), `location` (select: AU states + NZ), `id` (text, a slug-like short code e.g. `sydney`) | Physical store locations — see §B below for full detail |
| `gallery-tabs` | 34 | `image_item` (media, cover image), `gallery_images` (gallery, stored as URLs not IDs — `value_format: url`), `_title` (text, optional), `_description` (text, optional) | Powers the `/gallery/` page — see new file `15` |
| `category-silder-list` | 6 | `category_link` (text) | Homepage "shop by category" slider items (Chairs/Stools/Tables/Table Tops/Table Bases/Picnic Tables) — title + thumbnail (native post fields) + editor content + a manual category link |
| `year-carousel` | 8 (confirmed: 2013, 2014, 2016, 2018, 2019, 2020, 2022, 2023) | `year` (text), `_description` (text) | A "company history" year-by-year timeline/carousel (likely on an About page). Real, small, cheap to register. |
| `notification-bar-mes` | 3 (confirmed titles: "Next day dispatch when ordered by 4 pm!", "Visit Your Local Branch! Click to see more.", "Check out our LASTEST Catalogue! See now!") | none beyond title | Rotating top-of-site announcement bar messages. **Decision (locked, per chat): do NOT register this as a CPT.** Registering a whole post type to hold 3 lines of text + links isn't worth it. The new theme's Phase 1 header already ships a single `announcement_text` ACF field (one static line) — keep that as-is for launch. If the client specifically asks in UAT for multiple rotating messages, convert `announcement_text` to an ACF repeater at that point (not before) — the current Figma header design shows no chevron/arrow controls implying rotation, so there's no design signal this is needed yet. |
| `review` | **14** (confirmed via wp-admin list, `chairforce.test/wp-admin/edit.php?post_type=review`) | `text` (textarea, required — the review body), `stars` (number 1–5, default 5, required — the rating) | Customer testimonials — see new file `20` for a full write-up of what this is and why it's a separate CPT rather than WooCommerce's native product reviews |

## B. Showrooms — full detail

7 published showrooms, one per state/region: NSW, QLD, SA, TAS, VIC, WA,
AUK (Auckland/NZ). Sample (`ID 1448`, NSW):

```
warehouse:        "Sydney Showroom / Warehouse"
time:             "Open 9am to 5pm - Monday to Friday<br>"
phone:            "(02) 9648 0799"
email:            sydney@chairforce.test
address:          "Warehouse 1, 161 Manchester Road, Auburn"
_description:     "(corner of Manchester & Chisholm Rd)\nEntry through the glass doors..."
map:              (empty on this row — field is flagged "check for delete in the future" in its own admin label, i.e. already considered dead by the site's own content editors)
showroom_gallery:  comma-separated attachment IDs (5 images)
location:          "New South Wales"  (redundant with the `state` value below — same info, two fields)
state:              "New South Wales"  (plain post meta, NOT declared in the CCT's own meta_fields list — added ad hoc, likely by an older version of this setup or a different tool)
id:                 "sydney"  (a short machine-readable slug, used by the pickup-selector feature below)
_thumbnail_id:      standard featured image
```

Also has taxonomy `showroom-locations` attached (see §C) and
`rank_math_primary_showroom-locations` (Rank Math SEO primary-term meta —
ignorable for rebuild).

### Front-end usage — 3 separate contexts, all via Listing Grid templates

1. **Showroom Archive Page** (`/showrooms/` — the CPT's own `has_archive`
   page) → Listing Grid template `"Showroom - Listing (Showroom Archive
   Page)"`.
2. **Contact Page** → Listing Grid template `"Showroom - Listing (Contact
   Page) - Updated"` — presumably a shorter/different card layout embedded
   on the Contact page rather than a link to the archive.
3. **"Showroom Locations" list** → Listing Grid template `"Showroom -
   Locations Listing - Updated"` — likely a simple state-name picker/index
   (using the `showroom-locations` taxonomy) that links into the other two.

### A 4th, genuinely custom (non-Jet) usage: "nearest pickup showroom" selector

`wp-content/themes/woodmart-child/functions.php` (not JetEngine, not
Elementor — hand-written PHP/JS in the child theme) implements a
**store-pickup selector**, most likely on the cart/checkout page:

- `get_posts(['post_type' => 'showrooms', ...])` fetches every published
  showroom.
- Computes a "nearest showroom first" ordering (likely using the
  customer's postcode against the `postcode_calculation` plugin, which is
  active on this site — the exact distance logic wasn't traced line-by-line
  in this pass, but the plugin's presence plus this feature's existence
  make the connection clear).
- Renders a `<select id="pickup-showroom-select">` populated from
  `warehouse`/`address`/`time`/`phone`/`email` meta, with the nearest
  showroom pre-selected/listed first.
- An AJAX endpoint, `wp_ajax_update_pickup_showroom_detail` /
  `wp_ajax_nopriv_update_pickup_showroom_detail`, re-renders the
  name/address/time/phone/email detail panel when a different showroom is
  picked from the dropdown — reading the same 4 meta keys directly via
  `get_post_meta($_POST['id'], ...)`.

**This is a genuine custom feature to functionally replicate** (unlike
most of the rest of this file, which is "just render the CPT data") — it's
real interactive checkout/cart logic, not simply a content listing.
Confirm with the site owner exactly which page(s) show this pickup
selector before rebuilding, since it wasn't fully traced to its calling
template in this pass (search the child theme file around line 421 for the
full context, or `grep` the live theme's checkout/cart templates for
`pickup-showroom-select`).

### Rebuild recommendation for Showrooms

- **Zero migration for the data**: `showrooms` post type + its plain post
  meta (`warehouse`/`time`/`phone`/`email`/`address`/`_description`/
  `showroom_gallery`/`location`/`state`/`id`) is regular
  `wp_posts`/`wp_postmeta` — register a matching CPT + ACF field group (or
  hand-rolled metabox) in the new theme reading the **same** meta keys, no
  data changes needed. Only 7 rows exist; a manual content review while
  building the new admin UI is cheap and worthwhile (e.g. resolve the
  `location`/`state` duplication, decide whether to keep the dead `map`
  field).
- **Rebuild the 3 front-end listings** (archive/contact-page/locations)
  as normal theme templates/blocks querying the CPT directly — no
  JetEngine Listing Grid dependency needed.
- **Rebuild the pickup-selector as first-class theme/checkout code** — this
  is the one piece of real business logic in the whole showrooms feature;
  everything else is presentational.

## C. Custom taxonomies (6)

| Slug | Attached to | Fields | Purpose |
|---|---|---|---|
| `showroom-locations` | `showrooms` | none | State/region grouping for showrooms (see §B). **Confirmed actively used** — it's the data source for the "Select Filter - Showrooms Location - Contact Page" Smart Filter (§I below), i.e. the Contact page has a real state-picker filter driven by this taxonomy, not just the "Locations Listing" template guessed at previously. |
| `gallery-category` | `gallery-tabs` | none | Category filter for the `/gallery/` page — see file `15` and §I below (confirmed live via "Gallery Category Filter") |
| `venues` | `product` | `venue_image` (gallery) | Tags a product with venue(s)/space-type(s) it suits. **Correction (DB-confirmed via post-count query): this is heavily used, not dormant.** 29 real terms, each tagging real products — usage counts range from 2 up to **315** (`Cafe`: 315, `Bars & Pubs`: 133, `Restaurants`: 91, `Community Centers`: 60, etc.). The term list (Hospitality, Commercial, Education, Healthcare, Residential, Cafe, Hotels, Office, Laboratory...) is essentially identical to the mega menu's "Shop by Space" panel (Phase 1). Very likely this taxonomy is exactly what "Shop by Space" links to. **Not** among the 5 confirmed live Smart Filters, so it's not used as a *filter widget* — but it's almost certainly used as *archive/landing pages* (one per venue term). Upgraded from "confirm before rebuilding" to **confirmed important — needs a real taxonomy archive template**, not just wp-admin visibility. Double-check where "Shop by Space" mega-menu links currently point. |
| `sales-by-location` | `product` | none | A regional-availability/sales classification on products. **Confirmed actively used** — it's the data source for the "Product sale by location filter" Smart Filter (§I below), a real checkbox filter, most likely on the shop/category archive (in addition to, or alongside, the color swatch filter in file `12`). **Upgrade this from file 09/earlier's "confirm before assuming" to "confirmed needed, but confirm exactly which archive page(s) show it."** |
| `feature` | `product` | `thumbnail` (media) | Icon-badge taxonomy for products (e.g. "Stackable", "UV Resistant", "Made in Australia") — each term has its own icon image, rendered via Listing Grid template `"Product Feature Listing - Single Product Page"`. **Likely customer-visible and worth preserving** — check the single-product page for an icon-badge row to confirm. |
| `review-option` | (none — not attached to any post type in its own definition) | none | **Confirmed dead via DB post-count query**: only 2 terms exist (`Lotion`, `White Coffee`), and both tag **zero posts** (`count: 0`). Not among the 5 confirmed live Smart Filters either. **Drop — do not rebuild, nothing to preserve.** |

## D. Relations (JetEngine's relational-field feature, distinct from the `parts` plain-meta approach)

| ID | Name | Parent → Child | Purpose |
|---|---|---|---|
| 5 | "Related Other Products in the Gallery" | `gallery-tabs` ↔ `product` (many-to-many) | Links a gallery photo to the product(s) shown in it — powers "shop this look" / "products in this photo" on the gallery popup |
| 9 | "Related Featured Product in the Gallery" | `gallery-tabs` ↔ `product` (one featured product per gallery item, per its labels) | The single "hero" product for a gallery item, distinct from the many-to-many list above |

Unlike "Parts" (file `10`, plain serialized post meta), these two actually
use JetEngine's proper relational-table storage
(`wp_jet_rel_default`/`wp_jet_rel_default_meta`) — a genuinely different
(and more normalized) data shape. **If the gallery page is being rebuilt**,
these relation tables are the actual source of "which products appear in
this gallery photo" — query `wp_jet_rel_default` directly (filtered by
relation ID 5 or 9) rather than assuming a post-meta array like `parts`.

## E. JetEngine "Options Pages" (single-instance virtual settings screens)

| Slug | Field(s) | Purpose |
|---|---|---|
| `catalogue-links` | **Confirmed live and active** (DB-verified): 4 fields — `catalogue_link_for_menu` (switcher, currently `false`), `catalogue_link_for_home_page` (text URL), `catalogue_link_for_footer` (text URL), `catalogue_link_for_` (text URL, labeled "...for Blog Page" — the field `name` itself is a truncated/buggy slug, worth renaming cleanly in the rebuild). The home/footer/blog fields all currently hold the **same real, live PDF URL** (`.../uploads/2026/01/Chairforce-Catalogue_14thJan_Clearance2026.pdf`, uploaded this January) — this is exactly the "Download Catalog" CTA button seen in the Home page Figma mockup. **Not speculative — register with confidence, migrate the current URL value.** The menu-link field is a documented non-dynamic manual workaround (its own description field says to edit the nav menu item directly instead) — safe to drop that specific sub-field, the new theme's menu items can just hold their own URL directly. | Downloadable PDF catalogue links, surfaced on Home/footer/blog and (per its own admin note) manually on one nav menu item |
| `hero-banner-home-page` | Two full sets of `banner_title_N`/`banner_sub_title_N`/`banner_button_text_N`/`banner_button_link_N`/`banner_image_N` (N=1,2) | Homepage hero banner content — **editable marketing copy, not developer-hardcoded** |
| `delivery-information-for-product-page` | `add_delivery_information_for_product_page` (wysiwyg) | The "Delivery Information" tab content on the single product page. **Correction to file `10`**, which assumed this was static template copy not worth migrating — it is actually **stored, editable content** (one global WYSIWYG field), just not tied to a specific product. Treat like the theme's global "options" pattern (`10-acf-integration.mdc`'s options page convention) — add an ACF Options Page field for this exact content and migrate the current value over once. |

## F. Listing Grid templates (the front-end rendering half — "jet-engine" post type, 12 total)

These are the actual Elementor+JetEngine templates that loop over the CPTs
above and render markup. Each is a real, separate design to account for if
rebuilding that page/section:

| Template | Renders | Where used |
|---|---|---|
| "Homepage - Review Card" | `review` | Homepage testimonials section |
| "Reviews - Review Card" | `review` | Presumably a dedicated reviews/testimonials page |
| "Reviews - Image Card" | `review` | Same page, image-led variant |
| "Category Slider Item" | `category-silder-list` | Homepage category slider |
| "Gallery Listing" | `gallery-tabs` | Main `/gallery/` grid — file `15` |
| "Gallery - Pop-up Content" | `gallery-tabs` (single item) | The lightbox/modal opened when a gallery photo is clicked — file `15` |
| "Showroom - Listing (Showroom Archive Page)" | `showrooms` | `/showrooms/` archive |
| "Showroom - Listing (Contact Page) - Updated" | `showrooms` | Contact page |
| "Showroom - Locations Listing - Updated" | `showroom-locations` terms | Location picker/index |
| "Product Feature Listing - Single Product Page" | `feature` taxonomy terms on the current product | Single product page icon-badge row |
| "Spare Parts Listing - On the Single Product Page" | `parts` related products | Already documented in file `10` |
| "Spare Parts Listing - add to cart canvas only" | `parts` related products | A second variant of the same "Parts" listing — likely rendered inside an off-canvas/side-cart drawer rather than the main tab panel. Confirm exactly where this is used before assuming the main-page version (file `10`) is the only one to replicate. |

## G. Saved Query Builder queries (13, feeding the Listing Grids above)

Mostly self-explanatory from their own names; flagging the notable ones:

- "Get the List of Main Related Products to the Gallery" / "...Other
  Related Product to the Gallery" / "Query for Previous and Next Gallery"
  — support the gallery popup's "related products" and prev/next
  navigation (file `15`).
- "Get the list of Features related to the current Product" — feeds the
  `feature` taxonomy icon row above.
- "Get the list of Spare Part related to the Current Product" / "Check if
  product has part" / "Test Parts listing" — three separate queries all
  built around the `parts` meta key, confirming (independently of file
  `10`'s findings) that `parts` is the real, actively-used mechanism.
- "Category chair shop page woocommerce product query" — a
  category-scoped product query; worth a quick check on whether this
  indicates a bespoke (non-default-WooCommerce-archive) shop-by-category
  page exists somewhere, separate from the standard `pa_colour`-swatch
  product archive covered in files `03`/`12`.

## I. Jet Smart Filters — full inventory (5 total, confirmed via wp-admin)

The plugin's own Smart Filters list (`wp-admin/admin.php?page=jet-engine-query`
→ Smart Filters) shows exactly 5 filters exist site-wide, confirming and
completing the earlier partial finding in files `09`/`12`/`15`:

| Filter | Type | Data source | Where it's used |
|---|---|---|---|
| Gallery Category Filter | Checkboxes List | Taxonomies (`gallery-category`) | `/gallery/` page — file `15` |
| Post Tag Filter | Checkboxes List | JetEngine Query Builder | Blog archive (not otherwise researched — out of scope, no swatch/gallery/product overlap) |
| Post Category Filter | Checkboxes List | Taxonomies (`category`) | Blog archive (same, out of scope) |
| Select Filter - Showrooms Location - Contact Page | Select (dropdown) | Taxonomies (`showroom-locations`) | Contact page — a real state-picker filter, not just a static list. Confirms §C's `showroom-locations` row above |
| Product sale by location filter | Checkboxes List | Taxonomies (`sales-by-location`) | Shop/category archive (exact page not yet pinned down) — confirms §C's `sales-by-location` row above |

**Only 2 of these 5 (Gallery Category, Product sale by location) overlap
with the swatch/gallery/product rebuild scope.** The two blog filters are
unrelated to this research entirely; the Contact-page showroom filter
overlaps with §B's showroom rebuild. None of these need `jet-smart-filters`
itself — each is a small "read taxonomy terms → checkbox/select UI →
re-query" component, same pattern as file `12`'s shop color filter.

## J. Jet Popups (2 total, confirmed via wp-admin)

`wp-admin/edit.php?post_type=jet-popup` shows exactly 2 popups:

| Popup | Trigger mechanism | Status |
|---|---|---|
| "Checkout notice popup" | **None found** — its own `_conditions` meta is an empty array (`a:0:{}`, matching the admin list's "Conditions aren't selected" / "Open event: Not Selected" / "On close event: Not Selected"), **and** a DB-wide search for its post ID anywhere in `wp_postmeta` (any other post's Elementor data, widget settings, etc.) returns **zero** references. **This popup appears to be built but never actually wired to open anywhere** — no automatic trigger condition, no manual "open this popup" button/link found referencing it. Flag as likely dead/abandoned before spending effort rebuilding it; if it turns out to matter, find out from the site owner what it's supposed to show and when (its name suggests a checkout-page notice, but nothing in the DB confirms this is live). |
| "Gallery – Jet Popup Template" | **Confirmed, real, and now fully understood.** The "Gallery Listing" template's per-item "View Products" button widget has `jet_attached_popup: "1066035"` (this popup's post ID) + `jet_engine_dynamic_popup: "yes"` — JetEngine's own native "attach a popup to this button, populated dynamically from the current loop item" integration with Elementor. This is the trigger for the gallery photo popup documented in file `15` — not custom JS, a built-in JetEngine+Elementor button setting. | **Live and load-bearing** — this is the actual mechanism behind file `15`'s "shoppable photo" popup. |

### Rebuild implication

- **Checkout notice popup**: don't rebuild until/unless confirmed still
  wanted — currently indistinguishable from dead weight based on DB
  evidence alone.
- **Gallery popup**: rebuild as a normal modal/dialog opened by a button
  click handler passing the current gallery item's ID/data — exactly the
  same shape as file `16`'s Quick View recommendation (one shared
  popup/dialog component + pass in whichever item triggered it), no
  JetEngine "attached popup" equivalent needed since you're not using
  JetEngine.

## K. Rebuild-priority guidance across all of the above

Given the number of items, priority should be driven by **customer-facing
impact**, not completeness-for-its-own-sake:

1. **High** — Showrooms (store locator + pickup selector: real e-commerce
   functionality), Gallery page (file `15`, a whole distinct page),
   `feature` taxonomy icons (if confirmed visible on single-product pages),
   Delivery Information content (real editable copy), **`venues` taxonomy**
   (re-confirmed via DB post-counts as heavily used — up to 315 products
   per term, likely the "Shop by Space" mega-menu destination — moved up
   from the earlier "low/confirm-before-building" bucket below).
2. **Medium** — Reviews/testimonials, homepage hero banner, category
   slider, `catalogue-links` (**re-confirmed live**: a real, currently-set
   PDF URL feeding Home/footer/blog), `sales-by-location` taxonomy — all
   standard "marketing content block" rebuilds with plain data models, no
   urgency beyond normal content-parity work.
3. **Low, but still register (schema is cheap)** — `year-carousel` (8 real
   rows, small/cheap). **Notification bar messages: explicitly NOT
   registering `notification-bar-mes` as a CPT** — decision made in chat:
   3 lines of text don't justify a whole post type; keep the Phase 1
   header's single `announcement_text` ACF field, only add a repeater if
   the client asks for rotation in UAT.
4. **Confirmed dead — drop, no further work** — `review-option` taxonomy
   (2 terms, both tagging zero posts).

**Separately (not JetEngine, out of this file's normal scope, but found
during the same DB pass):** Woodmart-core's own internal CPTs
(`woodmart_woo_fbt`, `woodmart_size_guide`, `woodmart_sidebar` — zero
posts, confirmed dead; `woodmart_slide` — 2 posts, still in draft, never
published; `woodmart_layout` — 5 published posts, but they're just
Elementor Theme-Builder layout assignments fully superseded by this
theme's own FSE templates, nothing to port; `cms_block` — 13 published
posts, 11 of which are Phase 1 header/mega-menu content already rebuilt,
the other 2 hold the single-product "Product Info"/"Delivery" static
copy referenced in file `10` §4) are all detailed in the master checklist,
file `19` §1.
