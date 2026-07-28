# Chairforce Rebuild — Progress Tracker & Roadmap

Single top-level status doc for the whole rebuild (header/mega-menu →
registration → commerce UI → page assembly). Phase numbers match the
"Build-phase legend" in
`context/existing-functionality/19-master-rebuild-registration-checklist.md`.
Feature-level detail and sequencing rationale come from
`context/existing-functionality/11-recommendation-and-implementation-plan.md`
(the research's own conclusion/sequencing doc) — this file is the
status-tracking layer on top of that plan, not a replacement for it.

This file tracks **status**, with a **reference to the doc that has the
actual detail** for each item. Don't duplicate research content here.

## How to keep this updated

- Flip an item to ✅ when it's fully done and verified (not just
  code-complete — each phase's linked doc has its own verification rule).
- Add a one-line note + commit hash when closing an item.
- Don't delete rows for skipped/dropped work — mark them **Dropped** with a
  pointer to the decision doc, so the "why" isn't lost.
- New research/plan docs get added to the "Related docs" table at the
  bottom, and cross-referenced from whichever phase they inform.

## Foundational decision (governs every phase below)

**Build fresh in the new theme's own architecture (ACF + JSX blocks + Sass),
reading the exact same underlying `wp_termmeta`/`wp_postmeta`/`wp_options`
keys — do not port any Woodmart/Elementor/Jet\* code, and do not keep those
plugins active as dependencies.** Locked twice: once as the research's own
conclusion (file `11`), and once more explicitly after a "keep JetEngine
active?" second-guess was raised and rejected —
`context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md`.

## Phase roadmap

### Phase 1 — Header + mega menu (site-wide nav shell) — You

**Status: ✅ Done**

- Spec/decisions: `context/header-mega-menu/` (`01`–`05`, see its own
  `README.md`), sourced from `context/figma/components/` screenshots.
- Implementation plan: `context/plans/header-mega-menu-plan.md`,
  `context/plans/header-mega-menu-cleanup-notes.md`.
- Includes the single `announcement_text` ACF field that supersedes
  registering `notification-bar-mes` as a CPT (checklist file `19` §1 —
  locked decision, revisit only if client asks for rotation in UAT).

**Supporting infrastructure (done alongside Phase 1, not phase-numbered):**

- Lucide icon font for the Button block picker — `context/plans/lucide-icon-system-plan.md`,
  `lucide-icon-system-task-list.md`; rules: `.cursor/rules/16-icon-system.mdc`.
- Icon Block plugin integration (same curated icon set, inline SVG) —
  `context/plans/icon-block-plugin-integration-plan.md`.

### Phase 2 — Static/content patterns — Team

**Status: ⏳ Not started**

Home hero, trust badges, category tiles, newsletter, footer — no backend
logic, can run independently of Phase 3.

- Consumes the `hero-banner-home-page` and `catalogue-links` ACF Options
  Pages (already registered in Phase 3a below) — field detail in checklist
  file `19` §4, source research file `14` §E.
- No dedicated plan doc yet — Figma source: `context/figma/screens/`.

### Phase 3 — Registration + core commerce UI — You

This is the "big" phase; broken into sub-steps below in the order
recommended by file `11`'s "Suggested sequencing" section.

#### 3a. Registration/backend pass (CPTs, taxonomies, ACF field groups, options pages, product-level fields)

**Status: ✅ Done (28 Jul 2026)**

- Plan + per-chunk status/commits: `context/plans/registration-and-acf-schema-plan.md`.
- Full checklist this executes: `context/existing-functionality/19-master-rebuild-registration-checklist.md`.
- Field-level source of truth: file `14` §A/§C/§E (CPTs/taxonomies/options
  pages), file `10` §1/§5 (product Dimensions/Care/Additional
  Info/Parts/variation gallery).
- WooCommerce-specific implementation conventions locked in for future work:
  `.cursor/skills/chairforce-woocommerce/`.
- **Excluded from this pass, still unscheduled:** `gallery-tabs`↔`product`
  relationship **data** migration (`wp_jet_rel_default` relation IDs 5 & 9)
  — real one-time migration, not schema. Needed before/during Phase 6. See
  checklist §3, research file `14` §D.

#### 3b. Foundational rendering infrastructure — build before the features below depend on it

**Status: ⏳ Not started**

Per file `11` §6 ("do early rather than late") — nearly every feature in
3c–3g depends on these two shared pieces:

- **Load More / infinite-scroll / AJAX-filter event architecture** —
  document-level event delegation + idempotent, event-triggered lazy init.
  Mandatory reading before building any grid/pagination feature: file `17`.
- **Shared carousel component** — Swiper is the confirmed only library in
  use (product gallery, testimonials, category slider, etc.); build one
  shared component, not five ad-hoc initializers: file `18`.

#### 3c. Term swatch admin UI (`pa_colour` term meta)

**Status: ⏳ Not started**

Woodmart's own attribute-term-edit-screen UI currently lets editors set
`color`/`image`/`not_dropdown` term meta — that UI disappears the moment
Woodmart is removed, so it needs a like-for-like replacement (same keys,
new admin field), or editors lose the ability to manage swatch colors
entirely.

- Data contract: file `02` §1/§2 (term meta keys, `rgb(r,g,b)` string
  format, not hex).
- Recommended approach: file `11`'s feature table, row 1 ("New lightweight
  admin field on the term edit screen").

#### 3d. Product card / grid swatches

**Status: ⏳ Not started**

- Full spec: file `03` (rendering modes, click/hover → image-swap JS).
- Styling: file `05`, hardcoded to the one confirmed combo (`style 3`/
  `dis-style 3`/`round`/`m`) per file `09` §3 — no admin config needed.

#### 3e. Single-product swatches + gallery swap

**Status: ⏳ Not started**

- Full spec: file `04` (variation form, swatch → `found_variation`/
  `show_variation` wiring, main gallery + "additional variation images"
  mini-gallery swap).
- Admin-side data this depends on is already registered (3a) —
  `wd_additional_variation_images_data` on `product_variation`, native WC
  hooks in `lib/class-woocommerce-admin.php`.
- Frontend scaffold waiting for this work: `lib/class-woocommerce-single-product.php`.

#### 3f. Shop/category "filter by color" sidebar widget

**Status: ⏳ Not started**

- Confirmed mechanism (Woodmart's own `WOODMART_Widget_Layered_Nav`, an
  extended WC native attribute filter — **not** `jet-smart-filters`): file
  `12`.
- Can be built any time after 3c (term swatch admin UI) — no other
  dependency.
- Frontend scaffold waiting for this work: `lib/class-woocommerce-archive.php`.
- Also covers the `venues`/`sales-by-location` taxonomy filters registered
  in 3a — checklist file `19` §2/§4a; open question: exact archive page for
  "Product sale by location" (§8) and where "Shop by Space" should link
  (likely the `venues` archive).

#### 3g. Parts (related spare-parts) section + single-product tabs

**Status: ⏳ Not started**

- Data + legacy rendering system: file `10` §5 (relationship field, already
  registered in 3a) and §6 (why the child theme needed gallery-isolation
  protection — file `07` §6's `MutationObserver` hack, not needed in a
  from-scratch build if each Part's markup is properly scoped).
- Tabs bundle Dimensions/Care/Parts/Additional Info + static "Delivery
  Information"/"Product Info" copy (options pages already registered in
  3a) — file `11`'s feature table, "Single-product tabs" row.

#### 3h. Quick View rebuild

**Status: ⏳ Not started**

- Confirms full rebuild needed (Woodmart-native, no plugin dependency) but
  **zero new swatch/gallery logic** — reuses the same single-product
  component via a `wdQuickViewOpen` event contract: file `16`.
- Depends on 3e existing first (the component it mounts).

### Phase 4 — Home page assembly — Team

**Status: ⏳ Not started**

Best-sellers grid, reviews carousel, showrooms section — consumes what
Phase 3 built.

- Reviews: `review` CPT (registered in 3a) is hand-curated marketing
  testimonials, not WooCommerce's native per-product reviews (confirmed
  unused) — file `20`.
- Showrooms: CPT/fields registered in 3a; the **postcode-based "nearest
  pickup showroom" selector is real logic to rebuild**, not just content —
  file `14` §B. Open question (checklist §8): which page renders the
  selector.
- Uses the shared carousel component from 3b.

### Phase 5 — Blog listing + My Account — Collective, end

**Status: ⏳ Not started**

- Wishlist rebuild folds in here: Woodmart's native 2-table wishlist,
  confirmed near-empty live data (3 shells, 0 products) — zero real
  migration burden, free to design fresh — file `13`.
- Blog archive filters (Post Tag/Category — checklist file `19` §4a) are
  out of scope for the swatch/gallery research, just standard blog
  filtering.

### Phase 6 — `/gallery/` shoppable-photo page — TBD, after Phase 4

**Status: ⏳ Not started**

- Full spec: file `15` — `gallery-tabs` CPT + `gallery-category` taxonomy
  (both registered in 3a) + JetEngine Listing Grid pagination (replaced by
  3b's Load More pattern) + **the first confirmed real `jet-smart-filters`
  usage on the site** + a shoppable-photo popup with related/featured
  product relations.
- **Blocked on** the excluded relationship data migration noted under 3a
  (`gallery-tabs`↔`product`, relation IDs 5 & 9) — this must happen before
  or during this phase, it's the one real migration task in the whole
  project.
- Open question (checklist §8): exact pagination mode (infinite-scroll vs.
  load-more button) — confirm via live network inspection.

## Confirmed-dead / dropped (no rebuild work, listed so it isn't re-litigated)

Full detail and evidence in checklist file `19` §1/§2/§5/§7. Headline
items: `woodmart_woo_lv` (Linked Variations), `woodmart_woo_fbt`,
`woodmart_size_guide`, `woodmart_sidebar`, `woodmart_slide`,
`review-option` taxonomy, Booster's Product Variation Swatches module,
`_wc_additional_variation_images`, `_jet_woo_template`,
`woo_variation_gallery_images` (orphaned data, left alone not deleted),
`care_tab`/`_dimensions` legacy ACF shadow keys (flag to client, don't
proactively backfill).

## Known open issues (not blocking current work)

- **`templates/archive.html` renders no grid items on any taxonomy archive
  WooCommerce doesn't specifically own** (confirmed via `/venue/commercial/`
  — pagination shows correctly, since the query/taxonomy match real
  products, but no cards render). Root cause: the template's `wp:post-
  template` references `chairforce/query-post-card-blokki`, a block that is
  **never registered in this theme** (`src-acf-blocks/` only has
  `acf-field-display`; the block exists only in reference themes
  `bjm-briks`/`lasersight`/`shineon`) — WordPress silently renders empty
  output for unregistered blocks. `product_cat`/`product_tag`/`pa_*`
  attribute/`product_brand` archives are unaffected because WooCommerce's
  own bundled block templates (`ProductCategoryTemplate` etc. in
  `wp-content/plugins/woocommerce/src/Blocks/Templates/`) take over before
  the theme's generic `archive.html` is ever reached. This will also affect
  `sales-by-location`/`feature` taxonomy archives (**3f**) and the plain
  blog archive (**Phase 5**) until a real post-card block is built for this
  theme (or those archives get their own FSE templates using native WC
  blocks). Not a regression from the Phase 3a registration pass — the
  taxonomy/query layer is confirmed working correctly.

## Open decisions blocking future phases

Carried from checklist file `19` §8 — resolve before/during the phase noted:

- Which page renders the showroom "pickup selector" (file `14` §B) —
  blocks part of **Phase 4**.
- Exact archive page for the "Product sale by location" filter (file `14`
  §I) — blocks **3f**.
- Where the mega-menu's "Shop by Space" link should point (likely `venues`
  taxonomy archive) — blocks **3f**/**Phase 4**.
- "Checkout notice popup" — confirm still wanted, zero live trigger found
  (file `14` §J) — blocks nothing yet, just needs a yes/no.
- `/gallery/` page's exact pagination mode (infinite-scroll vs. load-more) —
  blocks **Phase 6**.
- Contact page's showroom Select filter and the "Download Catalog"
  `catalogue-links` CTA (checklist §4/§4a) — need a page owner (Phase 2/4 vs.
  their own pass) before scheduling.

## Related docs

| Doc | Purpose |
|---|---|
| `context/existing-functionality/README.md` | Index of all research docs `01`–`20`, plus the three QA addenda with DB-verified facts |
| `context/existing-functionality/11-recommendation-and-implementation-plan.md` | The research's own conclusion + feature-by-feature plan + sequencing — this file's main source |
| `context/existing-functionality/19-master-rebuild-registration-checklist.md` | Full registration checklist + two-bucket rule (source of the phase legend) |
| `context/plans/registration-and-acf-schema-plan.md` | Execution plan + status for Phase 3a specifically |
| `context/plans/header-mega-menu-plan.md`, `header-mega-menu-cleanup-notes.md` | Phase 1 implementation |
| `context/plans/lucide-icon-system-plan.md`, `icon-block-plugin-integration-plan.md` | Icon system infrastructure (done) |
| `context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md` | Locked decision: no Jet\* plugins as live dependencies, native rebuild throughout |
| `.cursor/skills/chairforce-woocommerce/` | WC-specific implementation conventions for Phase 3d–3h work |
| `context/kb/theme-color-mapping.md`, `typography-token-mapping.md` | Design-token reference used across all frontend phases |
| `context/figma/` | Source screens/components referenced throughout |
