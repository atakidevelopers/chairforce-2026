# 19 — Master Checklist: What Must Be Re-Registered When Elementor/Jet\*/Woodmart Are Deactivated

Resolves QA question: *"Have we made sure that we are dumping all the
plugins like Elementor, Jet plugins, Woodmart theme — have we made the
list of what we shall need to register once we deactivate these
permanently?"*

This is a single, practical checklist consolidating every registration
(custom post type, taxonomy, meta key, options page, DB table) discovered
across files `01`–`18`, organized by **what breaks the moment each plugin
is deactivated**, so nothing gets silently orphaned. Full detail/rationale
for each row lives in the referenced file — this file is deliberately just
the checklist.

Every row below is now also tagged with **which build phase** it belongs
to, per the sequencing decided in chat (see legend immediately below).
This is intentionally *not* a single "step 0" — each registration happens
just-in-time, alongside the feature that actually needs it.

## Build-phase legend

| Phase | Who | What |
|---|---|---|
| **1** | You | Header + mega menu (site-wide nav shell) |
| **2** | Team | Static/content patterns (Home hero, trust badges, category tiles, newsletter, footer — no backend logic) |
| **3** | You | Swatches + shop/archive filters + single-product gallery + Quick View (the core commerce/product UI) |
| **4** | Team | Home page assembly (best-sellers grid, reviews carousel, showrooms section — consumes what #3 built) |
| **5** | Collective, end | Blog listing + My Account |
| **6** | TBD, after #4 | Standalone `/gallery/` shoppable-photo marketing page |
| **Drop** | — | Confirmed unused — do not rebuild |
| **TBD** | — | Usage/fields not yet confirmed — resolve before assigning a phase (see §8) |

## 0. Plugins actually confirmed being retired (from live `chairforce` site)

```
elementor, elementor-pro, jet-engine, jet-popup, jet-woo-product-gallery,
jet-smart-filters, jet-tabs, jet-woo-builder, woocommerce-jetpack (Booster),
woodmart-core, woodmart (theme), woodmart-child (theme)
```

Everything else in the active-plugin list (`09` §0) — WooCommerce, Rank
Math, Wordfence, UpdraftPlus, Redirection, YITH Request-a-Quote, PayPal/
Verifone payment gateways, PixelYourSite, feed plugins, etc. — is staying
and is **out of scope** for this checklist (nothing to re-register).

## 1. Custom Post Types to re-register

| CPT slug | Currently registered by | Post count | New theme owner | Phase | Detail |
|---|---|---|---|---|---|
| `showrooms` | JetEngine CCT (`wp_jet_post_types` table) | 7 | New CPT + ACF/metabox | **4** (needed for Home's showroom section) | File `14` §A/§B |
| `gallery-tabs` | JetEngine CCT | 34 | New CPT + ACF/metabox | **6** | File `14` §A, `15` |
| `category-silder-list` | JetEngine CCT | 6 | New CPT (or just an ACF repeater on a homepage options page — small enough to not need a full CPT) | **2/4** (Home category slider — team builds the field, assembles on the page) | File `14` §A |
| `year-carousel` | JetEngine CCT | 8+ | New CPT (or repeater) — confirm still wanted first | **TBD → 4** once confirmed | File `14` §A/K |
| `notification-bar-mes` | JetEngine CCT | 3 | New CPT (or repeater) | **1** — this is the site-wide top announcement bar ("Free shipping on orders over $999…") visible in every header mockup | File `14` §A |
| `review` | JetEngine CCT | 14 (confirmed via wp-admin) | New CPT + fields (`text`, `stars`) | **4** (Home testimonials carousel) | Files `14` §A, `20` |
| `woodmart_woo_lv` (Linked Variations) | `woodmart-core` plugin | 0 (unused) | **Drop — do not rebuild** | **Drop** | File `08`, `09` §1a |
| `woodmart_woo_fbt` (Frequently Bought Together) | `woodmart-core` plugin | not audited this pass — confirm usage before dropping | TBD | **TBD** | New finding, no dedicated file yet — **follow-up needed** |
| `woodmart_layout` / `woodmart_size_guide` / `woodmart_slide` / `woodmart_sidebar` / `cms_block` | Woodmart theme itself | not audited this pass | TBD | **TBD** (likely N/A — superseded by FSE templates/patterns) | Layout-builder/content-block CPTs — likely superseded entirely by the new theme's own FSE templates/patterns (per `11-template-system.mdc`) rather than needing like-for-like rebuilding; **confirm nothing customer-facing depends on specific posts in these before ignoring them** |

## 2. Custom Taxonomies to re-register

| Taxonomy slug | Attached to | New theme owner | Phase | Detail |
|---|---|---|---|---|
| `showroom-locations` | `showrooms` | New taxonomy — **confirmed live** via the Contact page's Select filter (file `14` §I) | **4** (Showrooms section/page); Contact page's own filter use is **TBD**, not yet scheduled | File `14` §C/I |
| `gallery-category` | `gallery-tabs` | New taxonomy — **confirmed live** via the Gallery Category checkbox filter (file `14` §I) | **6** | File `14` §C/I, `15` |
| `venues` | `product` | New taxonomy — **not** among the 5 confirmed live Smart Filters; confirm actual front-end usage before rebuilding | **TBD** | File `14` §C/I/K |
| `sales-by-location` | `product` | New taxonomy — **confirmed live** via the "Product sale by location" checkbox filter (file `14` §I) | **3**, tentative — exact archive page not yet pinned down (see §8) | File `14` §C/I |
| `feature` | `product` | New taxonomy (likely customer-visible icon badges) | **3**, tentative (product card badges) | File `14` §C/K |
| `review-option` | (unattached) | Confirm purpose/usage before rebuilding — **not** among the 5 confirmed live Smart Filters either | **TBD** | File `14` §C/I/K |
| `pa_colour` (+ other `pa_*` attribute taxonomies) | `product` | **Already a native WooCommerce attribute taxonomy — nothing to "re-register", WooCommerce itself owns this, unaffected by retiring Elementor/Jet/Woodmart** | **3** (already in active use, zero registration work) | Files `02`, `03`, `04` |

## 3. Relationships to re-register

| Relation | Mechanism today | New theme owner | Phase | Detail |
|---|---|---|---|---|
| Product ↔ "Parts" (spare parts) | Plain serialized array in post meta key `parts` | ACF Relationship field, same meta key (zero migration) | **3** (single-product tabs, same gallery-protection logic as the swatch work) | File `10` |
| `gallery-tabs` ↔ `product` (many-to-many, "other products in photo") | JetEngine Relations (`wp_jet_rel_default` table, relation ID 5) | ACF Relationship field or join table — **migrate the existing rows once**, this is real data | **6** | File `14` §D, `15` |
| `gallery-tabs` ↔ `product` (one "featured product") | JetEngine Relations (relation ID 9) | Same as above | **6** | File `14` §D, `15` |
| Woodmart "Linked Variations" (cross-product swatch linking) | `woodmart_woo_lv` CPT | **Drop — confirmed unused (0 posts)** | **Drop** | File `08` |

## 4. Options Pages (single-instance settings) to re-register

| Slug | Field(s) | New theme owner | Phase | Detail |
|---|---|---|---|---|
| `hero-banner-home-page` | 2× banner title/subtitle/button-text/button-link/image | ACF Options Page | **2** (Home hero pattern) | File `14` §E |
| `delivery-information-for-product-page` | 1 WYSIWYG field | ACF Options Page (per `10-acf-integration.mdc` convention) — **migrate the current value**, it's real editable content | **3** (single-product page tab content) | File `14` §E |
| `catalogue-links` | not fully captured this pass | ACF Options Page — **follow-up needed** to pin down exact fields | **TBD → likely 2/4** (matches the Home page's "Download Catalog" CTA) | File `14` §E |
| Woodmart Theme Settings → Shop → Variable products (swatch behavior) | `xts-woodmart-options` serialized `wp_options` row | Hardcoded Sass/config (per file `09` §3 recommendation — not user-configurable in the new theme) | **3** | Files `02`, `06`, `09` |

## 4a. Jet Smart Filters to re-register (5 total, confirmed via wp-admin)

Full detail in file `14` §I. Quick reference:

| Filter | New theme owner | Phase |
|---|---|---|
| Gallery Category Filter | New checkbox filter component reading `gallery-category` — file `15` | **6** |
| Select Filter - Showrooms Location - Contact Page | New select/dropdown component reading `showroom-locations` — file `14` §B | **4**, Contact-page usage itself is **TBD** (not yet on the roadmap) |
| Product sale by location filter | New checkbox filter component reading `sales-by-location` — page location TBD, see §8 below | **TBD → likely 3** |
| Post Tag Filter / Post Category Filter | Blog archive filters — **out of scope for this research**, unrelated to swatches/gallery/product features | **5** (blog) |

## 4b. Jet Popups to re-register (2 total, confirmed via wp-admin)

Full detail in file `14` §J.

| Popup | New theme owner | Phase |
|---|---|---|
| "Gallery – Jet Popup Template" | **Rebuild** — a shared modal/dialog component (same pattern as Quick View, file `16`) showing the clicked gallery item's content — file `15` | **6** |
| "Checkout notice popup" | **Don't rebuild until confirmed still wanted** — zero live trigger wiring found anywhere in the DB (empty `_conditions`, no references to its post ID from any other post) | **Drop** (pending confirmation) |

## 5. Custom database tables — decide keep/migrate/drop per table

| Table(s) | Feature | Verdict | Phase |
|---|---|---|---|
| `wp_jet_post_types`, `wp_jet_taxonomies`, `wp_jet_cache`, `wp_jet_rel_default(_meta)`, `wp_jet_search_suggestions(_sessions)`, `wp_jet_smart_filters_indexer` | JetEngine internals | **Drop entirely once migration of §1–§4 above is done** — these are JetEngine's own working tables, meaningless without the plugin | Cleanup, after phases **4** and **6** have read what they need |
| `wp_woodmart_wishlists`, `wp_woodmart_wishlist_products` | Wishlist | **Drop — confirmed near-empty (3 rows, 0 products), no real data to preserve** | **Drop**, decision lives with **5** (Account) | File `13` |
| `wp_wc_layered_nav_counts_*` transients | Shop filter term counts | Not a table (transient rows in `wp_options`) — **drop, was already disabled site-wide by the child theme** | **3** | File `07` §5, `12` |

## 6. Term meta / post meta keys to keep reading (zero-migration items — just confirm the new theme's code uses these exact key names)

Full detail already in file `02`; summarized here for the checklist:

- `pa_colour` term meta: `color`, `image`, `not_dropdown` (also read by the
  shop/gallery filter widgets — file `12`, and any other swatch-styled
  taxonomy filter — file `14` §C's `feature`/`venues` could theoretically
  reuse the same convention if desired, though they don't today). **Phase 3.**
- Product/variation meta: `dimensions`, `care`, `additional_information`,
  `parts`, `wd_additional_variation_images_data`. **Phase 3.**
- Showroom post meta: `warehouse`, `time`, `phone`, `email`, `address`,
  `_description`, `map`, `showroom_gallery`, `location`, `state`, `id`.
  **Phase 4.**
- Gallery post meta: `image_item`, `gallery_images`. **Phase 6.**

## 7. Explicitly confirmed dead — do not re-register, do not migrate

- `_wc_additional_variation_images` (file `07` §4 — zero rows).
- `_jet_woo_template` (file `10` §1b — zero non-empty rows).
- `woo_variation_gallery_images` (file `02`/README — orphaned, 549 rows
  but read by nothing; leave the data alone, just don't build anything
  that expects it).
- `care_tab`/`_care_tab`/`_dimensions` (legacy ACF fields, superseded by
  the JetEngine `care`/`dimensions` keys — **except** see file `10` §1a's
  data-gap warning: some products' real content is stranded under
  `care_tab` and must be backfilled into `care` before/during migration,
  not simply discarded — do this as part of **Phase 3**, before the
  Dimensions/Care ACF fields go live).
- Booster for WooCommerce's Product Variation Swatches module data (file
  `08` — confirmed inactive/never configured).

## 8. Open follow-ups surfaced by this checklist (not yet resolved — flag for a future pass)

- `woodmart_woo_fbt` (Frequently Bought Together) usage/data — not audited.
- Woodmart's own layout-builder CPTs (`woodmart_layout`, `woodmart_size_guide`,
  `woodmart_slide`, `woodmart_sidebar`, `cms_block`) — not audited; likely
  superseded by FSE but worth a quick confirm.
- `venues`, `review-option` taxonomies — live usage still unconfirmed
  (unlike `sales-by-location`, now confirmed live via a Smart Filter —
  file `14` §I), don't build until confirmed needed.
- `catalogue-links` options page — exact fields not captured this pass.
- The `/gallery/` page's exact pagination mode (infinite-scroll vs.
  load-more button) — confirm via live network inspection (file `15`).
- Which page(s) render the showroom "pickup selector" (file `14` §B) —
  confirm before rebuilding that specific interactive feature.
- Exactly which archive page(s) show the "Product sale by location" filter
  (file `14` §I) — confirmed to exist, exact page not yet pinned down.
- "Checkout notice popup" (file `14` §J) — confirmed built but with zero
  live trigger wiring found anywhere in the DB; confirm with the site
  owner whether it's still wanted before deciding to rebuild it.
- The Contact page's showroom Select filter (§4a above) and the "Download
  Catalog" `catalogue-links` options page (§4 above) aren't yet assigned a
  firm phase — both are small, but need a page owner decided (folded into
  Home/#4, or their own later pass) before scheduling.
