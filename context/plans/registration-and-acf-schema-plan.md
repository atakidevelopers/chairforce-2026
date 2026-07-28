# Registration + ACF Schema Pass — Implementation Plan

## Status: ✅ Complete (chunks 1–4), verified 28 Jul 2026

All four chunks are implemented, committed, and passed the universal
verification rule. See `context/PROGRESS.md` for the project-wide phase
tracker; the summary here is scoped to this plan only.

| Chunk | Status | Commit(s) | Notes |
|---|---|---|---|
| 1. CPT + taxonomy schema | ✅ Done | `54a203a` | Row/term counts confirmed matching file `14` |
| 2. ACF field groups (chunk 1 entities) | ✅ Done | `520dcbd` | |
| 3. ACF Options Pages | ✅ Done | `855af91` | Legacy blob-storage bridge (`class-legacy-options-storage.php`) |
| 4. Product-level field groups | ✅ Done (revised) | `0f60d12`, `99d7ab2` | Dimensions/Care/Additional Info/Parts and variation gallery all confirmed. **First pass used ACF on `product_variation`, which has no wp-admin UI (`show_ui: false`) — fixed in `99d7ab2` by switching Parts + variation gallery to native WooCommerce admin hooks** (see `lib/class-woocommerce-admin.php` and `.cursor/skills/chairforce-woocommerce/`) |
| *(excluded)* Relationship data migration | ⏳ Not started | — | `gallery-tabs`↔`product` rows, `wp_jet_rel_default` relation IDs 5 & 9 — still a separate, unscheduled effort |

## Goal

Execute the Phase 3 "registration/backend pass" from
`context/existing-functionality/19-master-rebuild-registration-checklist.md`:
re-create every **confirmed-needed** (bucket 2 of the two-bucket rule) CPT,
taxonomy, options page, and product-level field group from the retiring
JetEngine/Woodmart/Elementor stack as native WordPress + ACF schema —
reading the **exact same existing meta/term-meta/option keys** already in
the live database.

**Source of truth for field names/types/keys:** `context/existing-
functionality/14-jet-content-types-and-showrooms.md` (§A/§C/§E) and
`10-jetengine-fields-and-parts-system.md`. The agent doing this work should
be handed those tables directly as a literal spec, not asked to re-derive
field names from scratch.

## Important framing correction (read before starting)

Earlier planning talk (and a first draft of this chunking) described chunks
1–3 below as "new schema, no data risk" versus chunk 4 as "the risky one
because it touches existing data." **That distinction was wrong.** Every
single entity in this plan — `showrooms`, `gallery-tabs`, `year-carousel`,
`review`, `venues`, `sales-by-location`, `feature`, the three options pages —
already holds real, live rows/terms/meta in the current database (see file
`14` for exact counts, e.g. `venues` has 29 terms tagging up to 315 products
each). **Nothing being registered here is new/empty content.** We are only
building a new *admin UI* on top of data that already exists.

What actually differs between chunk 4 and everything else isn't "has real
data vs. doesn't" — it's **how consequential a rendering glitch would be**:

- Chunks 1–3: content types content editors touch rarely (a showroom's
  address is edited maybe once a year; nobody edits `year-carousel` day to
  day). If a field type is briefly wrong, it's a quiet, low-stakes fix.
- Chunk 4: the `product` post type, edited by content editors **every single
  day** as part of normal business operations. The same class of mistake
  here is immediately visible to the team currently running the live store.

So the verification rule below applies **uniformly to every chunk** — chunk
4 just deserves extra caution and a staging-first test, not a fundamentally
different process.

## Universal verification rule (every chunk, not just chunk 4)

For each entity registered, before marking its chunk done:

1. **Match the existing storage format exactly** — pull the field type/key
   straight from file `14`/`10`'s documented `meta_fields` (e.g.
   `gallery-tabs`'s `gallery_images` is stored as **URLs, not attachment
   IDs** — the ACF field's `value_format` must be `url`, not `id`, or
   existing values won't render correctly).
2. **Open a real existing record with the new admin UI** and confirm every
   field displays/edits correctly — not blank, not garbled, not silently
   coerced into a different shape. Pick a record known to have real values
   in every field (e.g. showroom `1448`, a `venues` term with a large
   product count, not the first row you happen to click).
3. **Do this against a DB copy/staging environment first**, not directly
   against production. ACF can rewrite a meta value into its own expected
   shape the first time the edit screen is saved — usually recoverable via
   revisions, but there's no reason to risk it on live data when a local
   copy costs nothing.

## Scope

### In scope (bucket 2 — confirmed-needed, register now)

- CPTs: `showrooms`, `gallery-tabs`, `year-carousel`, `review`
- Taxonomies: `showroom-locations`, `gallery-category`, `venues`,
  `sales-by-location`, `feature`
- Options pages: `hero-banner-home-page`,
  `delivery-information-for-product-page`, `catalogue-links`
- Product-level field groups (existing `product` CPT): Dimensions, Care,
  Additional Information, Parts (relationship field), variation image
  gallery

### Out of scope (this pass)

- **Relationship data migration**: `gallery-tabs`↔`product` rows currently
  in `wp_jet_rel_default` (relation IDs 5 & 9) — real one-time data
  migration, not schema, scheduled separately (file `19` §3).
- Frontend rendering/consumption of any of this data — that's the rest of
  Phase 3 (swatches/filters/gallery/Quick View) plus Phases 4–6.
- Bucket 1 (confirmed dead) items — `woodmart_woo_fbt`,
  `woodmart_size_guide`, `woodmart_sidebar`, `woodmart_slide`,
  `review-option` — not registered at all, per the locked two-bucket rule.
- `notification-bar-mes` — explicitly not registered as a CPT (decision
  locked in file `14` §A); Phase 1's single `announcement_text` field
  already covers this.
- Woodmart theme settings (`xts-woodmart-options`) — hardcoded per file `09`
  §3, not user-configurable in the new theme, nothing to register.

## Chunk breakdown

| Chunk | Contents | Existing data it exposes | Verification focus |
|---|---|---|---|
| **1. CPT + taxonomy schema** (structure only, no ACF fields yet) | `register_post_type()` for `showrooms`/`gallery-tabs`/`year-carousel`/`review`; `register_taxonomy()` for `showroom-locations`/`gallery-category`/`venues`/`sales-by-location`/`feature` | The existing `wp_posts` rows (7/34/8/14) and `wp_terms` rows (29 `venues` terms, etc.) are **already tagged with these exact slugs** in the DB — the moment each is registered, those rows/terms should appear immediately in `wp-admin` list tables, with zero extra work. This is a great free sanity check between chunks 1 and 2: if the right *count* of rows doesn't show up immediately, something about the slug/`show_in_menu`/`public` args is wrong, before any field-mapping risk even enters the picture. | Row/term counts in wp-admin match file `14`'s documented counts exactly |
| **2. ACF field groups for chunk 1's entities** | One field group per entity, fields copied verbatim from file `14` §A/§C: `showrooms` (`warehouse`/`time`/`phone`/`email`/`address`/`_description`/`map`/`showroom_gallery`/`location`/`id` + ad-hoc `state`), `gallery-tabs` (`image_item`/`gallery_images` **as URL**/`_title`/`_description`), `year-carousel` (`year`/`_description`), `review` (`text`/`stars`), `venues` (`venue_image` gallery on the **term**), `feature` (`thumbnail` media on the **term**), `sales-by-location` (no fields — just the term list) | Same rows as chunk 1, now with their actual field values visible/editable | Per the universal rule above — open at least one real, fully-populated record per entity |
| **3. ACF Options Pages** | `hero-banner-home-page` (2× `banner_title_N`/`banner_sub_title_N`/`banner_button_text_N`/`banner_button_link_N`/`banner_image_N`, N=1,2), `delivery-information-for-product-page` (1 WYSIWYG field), `catalogue-links` (`catalogue_link_for_menu` switcher + 3 URL fields — rename the buggy `catalogue_link_for_` blog-field slug cleanly while wiring this up) | Real, currently-live values (the hero banner copy, the Delivery Information WYSIWYG, and — most concretely — the actual live PDF URL: `Chairforce-Catalogue_14thJan_Clearance2026.pdf`) | Confirm the migrated value matches the live site exactly, character for character, since these are directly customer-facing marketing copy/links |
| **4. Product-level field groups** (existing, actively-edited `product` CPT) | Dimensions/Care/Additional Information ACF group (meta keys `dimensions`/`care`/`additional_information`, plain wysiwyg post meta) + `parts` Relationship field (meta key `parts`, serialized array of product IDs) — all on the **same existing meta keys** JetEngine used, per file `10` §1/§5. Variation image gallery: **already resolved, not an open decision** — the live site exclusively uses the "new" method, meta key `wd_additional_variation_images_data` on the **variation post itself** (`post_type = product_variation`), a comma-separated string of attachment IDs (1,023 non-empty rows; the legacy parent-product-meta method has zero rows and can be ignored) — see file `02` §4a/§09 item 1. Just build an ACF/metabox UI reading that exact key in that exact shape | Every published product's existing Dimensions/Care/Additional Info/Parts values, and every variation's existing additional-images list | **Highest caution of the four** — not because the data is "more real" than the others, but because `product` is edited daily by the team running the live store. Test against a **staging DB copy**, spot-check **several** real products and variations (not just one), and confirm saving the edit screen doesn't rewrite or strip any sibling meta key it isn't supposed to touch |
| *(excluded)* | `gallery-tabs`↔`product` relationship rows (`wp_jet_rel_default`, relation IDs 5 & 9) | Real relational data, but this is a migration task, not a registration task | Separate effort, separate timeline — doesn't block any chunk above |

**Ordering:** 1 → 2 must be sequential (a field group's location rules need
the CPT/taxonomy slug registered first). 3 can run any time, independently.
4 should be done last and reviewed on its own, specifically so a mistake
there is never buried inside a larger diff covering lower-stakes entities.

## Suggested agent briefing

1. Hand the agent the literal spec tables from file `14`/`10` — not a
   pointer to "go read the research docs," copy the actual field
   name/type/key rows into the task description.
2. One commit per chunk (1/2/3/4), in the order above — cheap to do, makes
   chunk 4 trivially isolatable/revertable if something looks off.
3. After each chunk: a manual wp-admin pass confirming real existing
   content renders correctly, per the universal verification rule — not
   just "did it save without a PHP error."
4. For chunk 4 specifically: run it against a DB copy first, and explicitly
   instruct the agent not to rename or restructure any existing meta key,
   even if the JetEngine-era key names aren't what it would naturally
   choose (e.g. the ad hoc, undeclared `state` key on `showrooms`).

## Related files

- `context/existing-functionality/14-jet-content-types-and-showrooms.md` —
  field-level source of truth for chunks 1–3
- `context/existing-functionality/10-jetengine-fields-and-parts-system.md` —
  field-level source of truth for chunk 4
- `context/existing-functionality/19-master-rebuild-registration-checklist.md`
  — the two-bucket rule and full registration checklist this plan executes
- `context/existing-functionality/02-data-model-and-storage.md` §4a — the
  confirmed variation image gallery meta key/format needed for chunk 4
