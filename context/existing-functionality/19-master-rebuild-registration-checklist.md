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

Every row below is tagged with **which build phase** actually *renders/
uses* it on the frontend, per the sequencing decided in chat (see legend
immediately below).

**Superseded decision — registration timing (locked):** an earlier
version of this checklist recommended registering each item
just-in-time, only when its consuming phase was reached. That's now
**overridden**: every CPT, taxonomy, ACF field group, meta box, and
options page in this checklist — **except items requiring an actual
one-time data migration** (§3's `gallery-tabs`↔`product` relations) —
gets its **schema registered now**, as one batch, during the Phase 3
backend/registration pass, regardless of which phase later renders it on
the frontend. Rationale (from chat): leaving something unregistered
means its data just sits invisibly orphaned in the DB — nobody can see
it, track it, or decide what to do with it, and it can't be handed to
the team even for out-of-sequence work. Registering it now makes it show
up in wp-admin immediately, which becomes the forcing function for a
concrete PM/client conversation ("here's the old `year-carousel` data,
what do you want to do with it?") instead of a silent unknown. Actual
**data migration** (moving real historical rows/relations into the new
structure) is a different, separable cost from **schema registration**
(CPT/taxonomy/field-group definitions) — the former can still be
scheduled independently per-feature without blocking anything, since the
new schema can be tested/used with a handful of real or spot-migrated
rows first.

**Final refinement (locked, post-research) — the two-bucket rule:** the
paragraph above was written before a follow-up DB research pass (§1/§2/§8)
resolved most of the previously-speculative items. That pass showed
registration isn't the *only* way to get visibility — a cheap `wp-cli`
post/term count can resolve "is this real or dead" without touching
schema at all. That refines, but doesn't reverse, the policy above into a
simple two-bucket rule for everything in this checklist:

1. **Confirmed dead/empty** (zero posts/terms, or terms tagging zero
   posts, or never-published drafts) — **do not register.** No value in
   giving a non-technical PM/client a wp-admin screen for something that
   provably has nothing in it; document it as dropped and move on.
2. **Confirmed real, or still genuinely unresolved (PM/TBD)** — **register
   now, for visibility**, same as before. Rationale (unchanged, and this
   is the key reason a research pass doesn't replace registration for
   these): `wp-cli`/DB access isn't always available to whoever needs to
   make the keep/drop/rebuild call (PM, client, another dev) — a real
   wp-admin screen is. Putting the actual content in front of a human in
   wp-admin is what reliably triggers the concrete "what do you want done
   with this?" conversation; a one-off count in a research doc doesn't.

In short: research first (cheap, resolves the "is it real" question and
prunes dead weight before spending registration effort), then register
everything that survives that prune — whether it's already fully
confirmed-needed or still sitting in "PM decision." Nothing stays
unregistered just because nobody's gotten around to deciding on it yet;
only genuinely-proven-dead items skip registration entirely.

One practical carve-out: for Woodmart's own internal page-builder CPTs
(`woodmart_layout`, `woodmart_size_guide`, `woodmart_slide`,
`woodmart_sidebar`, `cms_block` — see §1) a full matching CPT + meta-box
rebuild may be pointless effort, since these aren't really "content,"
they're Elementor/Woodmart page-builder internals with no obvious
equivalent in an FSE theme. **This carve-out has now been fully resolved
by a follow-up DB post-count pass (see §1 and §8) — no CPT reconstruction
needed for any of these five**: three are confirmed empty/dead
(`woodmart_size_guide`, `woodmart_sidebar`, and `woodmart_slide`, the
latter never even published), `woodmart_layout`'s 5 rows are just
Elementor Theme-Builder layout assignments already superseded by this
theme's own FSE templates, and `cms_block`'s 13 rows are either already
covered by Phase 1 header/mega-menu work (11 of them) or are a
one-time content-copy task, not a registration task (the remaining 2 —
see file `10` §4/§5).

## Build-phase legend (frontend rendering/consumption — unchanged)

| Phase | Who | What |
|---|---|---|
| **1** | You | Header + mega menu (site-wide nav shell) |
| **2** | Team | Static/content patterns (Home hero, trust badges, category tiles, newsletter, footer — no backend logic) |
| **3** | You | Registration/backend pass (this checklist) **+** swatches + shop/archive filters + single-product gallery + Quick View (the core commerce/product UI) |
| **4** | Team | Home page assembly (best-sellers grid, reviews carousel, showrooms section — consumes what #3 built) |
| **5** | Collective, end | Blog listing + My Account |
| **6** | TBD, after #4 | Standalone `/gallery/` shoppable-photo marketing page |
| **Drop** | — | Confirmed dead/empty (bucket 1 of the two-bucket rule above) — **do not register, do not rebuild** |
| **PM decision** | — | Confirmed real, or still unresolved (bucket 2) — **schema registered now regardless, for visibility**; keep/drop/rebuild-fully decided with PM/client once they can see the actual data in wp-admin |

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
| `year-carousel` | JetEngine CCT | **8, confirmed** (2013/14/16/18/19/20/22/23), fields `year`+`_description` | New CPT (or repeater) — small, cheap, real content | Schema: **now (Phase 3)**; frontend render: **PM decision** (likely an About-page timeline) | File `14` §A/K |
| `notification-bar-mes` | JetEngine CCT | **3, confirmed** (real rotating promo lines) | **Decision (locked): do NOT register as a CPT.** 3 lines of text don't justify a post type — keep Phase 1's single `announcement_text` ACF field. Convert to an ACF repeater later, only if the client asks for rotation in UAT (current Figma shows no rotation UI/chevrons) | **Drop as CPT** — superseded by the already-shipped Phase 1 header field | File `14` §A |
| `review` | JetEngine CCT | 14 (confirmed via wp-admin) | New CPT + fields (`text`, `stars`) | Schema: **now (Phase 3)**; frontend render: **4** (Home testimonials carousel) | Files `14` §A, `20` |
| `woodmart_woo_lv` (Linked Variations) | `woodmart-core` plugin | 0 (unused) | **Drop — do not rebuild** | **Drop** | File `08`, `09` §1a |
| `woodmart_woo_fbt` (Frequently Bought Together) | `woodmart-core` plugin | **0, confirmed** | **Drop — confirmed unused, nothing to audit or show PM** | **Drop** | — |
| `woodmart_size_guide` / `woodmart_sidebar` | Woodmart theme itself | **0, confirmed** (both) | **Drop — confirmed unused** | **Drop** | — |
| `woodmart_slide` | Woodmart theme itself | **2, confirmed, both still `draft`** — "Home Page Slider", "Product" | **Drop — never published, abandoned** | **Drop** | — |
| `woodmart_layout` | Woodmart theme itself | **5, confirmed, all `publish`** — "Cart Layout", "Empty Cart Layout", "Product Archive Page", "Product Archive page for all", "Single Product Page" | **No CPT needed — these are just Elementor Theme-Builder layout assignments, fully superseded by this theme's own FSE templates** (`templates/archive.html`, `templates/single.html`, cart page) — nothing to port | **N/A, already covered** by existing FSE scaffolding | — |
| `cms_block` | Woodmart theme itself | **13, confirmed, all `publish`** — see detail | **No CPT needed.** 8 are Phase 1 mega-menu category panels + 3 are Phase 1 header/mobile-menu pieces (**already rebuilt**); the remaining 2 — "Product Info dynamic content" (ID `1063733`) and "Single product description" (ID `1063673`) — hold the static single-product-page marketing copy referenced in file `10` §4/§5. Just copy that text into the new Phase 3 template once, no registration | **Content-copy task only**, folds into **1** (11 of 13) and **3** (2 of 13) | File `10` §4/§5 |

## 2. Custom Taxonomies to re-register

| Taxonomy slug | Attached to | New theme owner | Phase | Detail |
|---|---|---|---|---|
| `showroom-locations` | `showrooms` | New taxonomy — **confirmed live** via the Contact page's Select filter (file `14` §I) | Schema: **now (Phase 3)**; frontend: **4** (Showrooms section/page); Contact page's own filter use is **PM decision**, not yet scheduled | File `14` §C/I |
| `gallery-category` | `gallery-tabs` | New taxonomy — **confirmed live** via the Gallery Category checkbox filter (file `14` §I) | Schema: **now (Phase 3)**; frontend: **6** | File `14` §C/I, `15` |
| `venues` | `product` | New taxonomy — **re-confirmed via DB post-counts as heavily used, not dormant**: 29 real terms, usage counts 2–315 per term (`Cafe`: 315, `Bars & Pubs`: 133, `Restaurants`: 91). Not a Smart Filter, but very likely the "Shop by Space" mega-menu destination — **upgraded to confirmed-important** | Schema: **now (Phase 3)**; frontend: **likely 4 or its own pass — needs a real taxonomy archive template**, not just admin visibility. Confirm where "Shop by Space" mega-menu links point | File `14` §C/I/K |
| `sales-by-location` | `product` | New taxonomy — **confirmed live** via the "Product sale by location" checkbox filter (file `14` §I) | Schema: **now (Phase 3)**; frontend: **3**, tentative — exact archive page not yet pinned down (see §8) | File `14` §C/I |
| `feature` | `product` | New taxonomy (likely customer-visible icon badges) | Schema: **now (Phase 3)**; frontend: **3**, tentative (product card badges) | File `14` §C/K |
| `review-option` | (unattached) | **Confirmed dead via DB query**: 2 terms (`Lotion`, `White Coffee`), both tagging **zero posts**. Not among the 5 confirmed live Smart Filters either | **Drop — do not rebuild** | File `14` §C/I/K |
| `pa_colour` (+ other `pa_*` attribute taxonomies) | `product` | **Already a native WooCommerce attribute taxonomy — nothing to "re-register", WooCommerce itself owns this, unaffected by retiring Elementor/Jet/Woodmart** | **3** (already in active use, zero registration work) | Files `02`, `03`, `04` |

## 3. Relationships to re-register

| Relation | Mechanism today | New theme owner | Phase | Detail |
|---|---|---|---|---|
| Product ↔ "Parts" (spare parts) | Plain serialized array in post meta key `parts` | ACF Relationship field, same meta key (zero migration — this is a straight read/write, **register the field now** along with everything else) | **3** (single-product tabs, same gallery-protection logic as the swatch work) | File `10` |
| `gallery-tabs` ↔ `product` (many-to-many, "other products in photo") | JetEngine Relations (`wp_jet_rel_default` table, relation ID 5) | ACF Relationship field or join table — **the one genuine exception to "register everything now"**: the *field* can be registered now, but the *existing relation rows* need an actual one-time data migration out of JetEngine's own relation tables, which is real work, not schema — schedule this independently, it doesn't block anything else | **6** | File `14` §D, `15` |
| `gallery-tabs` ↔ `product` (one "featured product") | JetEngine Relations (relation ID 9) | Same as above | **6** | File `14` §D, `15` |
| Woodmart "Linked Variations" (cross-product swatch linking) | `woodmart_woo_lv` CPT | **Drop — confirmed unused (0 posts)** | **Drop** | File `08` |

## 4. Options Pages (single-instance settings) to re-register

| Slug | Field(s) | New theme owner | Phase | Detail |
|---|---|---|---|---|
| `hero-banner-home-page` | 2× banner title/subtitle/button-text/button-link/image | ACF Options Page | **2** (Home hero pattern) | File `14` §E |
| `delivery-information-for-product-page` | 1 WYSIWYG field | ACF Options Page (per `10-acf-integration.mdc` convention) — **migrate the current value**, it's real editable content | **3** (single-product page tab content) | File `14` §E |
| `catalogue-links` | **Confirmed, exact fields (DB-verified)**: `catalogue_link_for_menu` (switcher, off), `catalogue_link_for_home_page` / `catalogue_link_for_footer` / `catalogue_link_for_` (all text URLs — 3rd field's own name is a truncated slug bug, rename cleanly). All 3 URL fields currently hold the **same real, live PDF** (`Chairforce-Catalogue_14thJan_Clearance2026.pdf`) | ACF Options Page — **register these exact fields, migrate the current live PDF URL value** — not speculative, this is the Home page's "Download Catalog" CTA seen in Figma | Schema: **now (Phase 3)**; frontend: **2/4** (Home hero) **and 6** (footer/blog) | File `14` §E |
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
  the JetEngine `care`/`dimensions` keys). File `10` §1a documents that
  some products' real content is stranded under the dead `care_tab` key
  (409 products) that never made it into the current `care` field (542
  products) — **decision (locked): do not proactively backfill this.**
  Work only with whatever is currently in `care`, exactly as the live
  site does today. This is a pre-existing gap on the *current* site, not
  something the rebuild is responsible for fixing — flag it to the
  client and only act on it if/when they ask.
- Booster for WooCommerce's Product Variation Swatches module data (file
  `08` — confirmed inactive/never configured).

## 8. Open follow-ups surfaced by this checklist (not yet resolved — flag for a future pass)

**Resolved by the follow-up DB post-count research pass (all items below
this line are now closed, kept here only as a record of what was checked):**

- ~~`woodmart_woo_fbt` usage/data~~ — **resolved: 0 posts, confirmed dead,
  drop, no PM/client conversation needed.**
- ~~Woodmart's own layout-builder CPTs~~ — **resolved individually**:
  `woodmart_size_guide`/`woodmart_sidebar` (0 posts, drop),
  `woodmart_slide` (2 posts, still draft, drop), `woodmart_layout` (5
  published posts, but all superseded by this theme's own FSE templates,
  nothing to port), `cms_block` (13 published posts — 11 already covered
  by Phase 1 header/mega-menu work, remaining 2 are the single-product
  "Product Info"/"Delivery" static copy referenced in file `10`).
- ~~`venues` taxonomy live usage~~ — **resolved: heavily used (29 terms,
  2–315 products each), upgraded to confirmed-important, likely powers
  "Shop by Space".**
- ~~`review-option` taxonomy live usage~~ — **resolved: 2 terms, both
  tagging 0 posts, confirmed dead, drop.**
- ~~`catalogue-links` options page exact fields~~ — **resolved: 4 fields,
  DB-verified, real live PDF URL confirmed in 3 of them — see §4.**
- ~~`notification-bar-mes` CPT~~ — **resolved by explicit decision: do not
  register as a CPT; keep Phase 1's single `announcement_text` field,
  revisit only if the client asks for rotation in UAT.**

**Still open:**

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
- Confirm exactly where the mega-menu's "Shop by Space" links point, to
  verify the `venues` taxonomy hypothesis above (file `14` §C).
