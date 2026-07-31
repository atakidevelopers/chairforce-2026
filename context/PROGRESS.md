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
- Site Editor header placeholders — `chairforce/site-header` +
  `chairforce/editor-placeholder` JSX blocks (`c7c9205`, `e482633`); PHP header
  renders on frontend, locked notice in editor. Hidden from inserter via
  `Editor_Curation::curate_chairforce_blocks()`.

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

**Status: ✅ Done (29 Jul 2026)** — event-delegation convention only (`50ab984`).
Plan: `context/plans/3b-3d-event-pattern-and-grid-swatches-plan.md` chunk 1.

Per file `11` §6 ("do early rather than late") — nearly every feature in
3c–3g depends on these two shared pieces:

- **Load More / infinite-scroll / AJAX-filter event architecture** —
  document-level event delegation + idempotent, event-triggered lazy init.
  Mandatory reading before building any grid/pagination feature: file `17`.
  **Convention shipped:** `src/js/shared/delegated-events.js` +
  `.cursor/rules/18-event-delegation.mdc` (`chairforce:content-updated`
  custom event included for future carousel/lazy-init consumers).
- **Shared carousel component** — Swiper is the confirmed only library in
  use (product gallery, testimonials, category slider, etc.); build one
  shared component, not five ad-hoc initializers: file `18`.

**Scoping note (from the plan above):** checked the codebase before
planning 3d — no page currently uses Load More/infinite-scroll (existing
templates use numbered `query-pagination`), and Swiper isn't installed or
referenced anywhere yet. The plan above narrows 3b to just the event-
delegation **convention** (needed by 3d's click handler now); the shared
Swiper component is deferred to whichever phase first needs it. **Update
(31 Jul 2026):** Load More UI shipped as **3i** (`d7d7acc`).

#### 3c. Term swatch admin UI (`pa_colour` term meta)

**Status: ✅ Done**

Woodmart's own attribute-term-edit-screen UI let editors set `color`/
`image` term meta (`not_dropdown`/`pa_term_hint` confirmed universally
empty — no UI needed, see file `02`'s Fourth QA addendum) — that UI
disappeared the moment Woodmart was removed. Replaced with an ACF field
group (`group_pa_colour_swatch_fields`, `acf-json/`), not a custom
metabox — decided after confirming ACF's alpha color picker auto-detects
and preserves the existing `rgb(r,g,b)` format on save (no format
migration needed for `color`).

- Data contract: file `02` §1/§2 (term meta keys, `rgb(r,g,b)` string
  format, not hex; `image` stored as a legacy `['url' => ..., 'id' => ...]`
  array).
- `image` field: ACF Image field (`return_format: id`) cannot read the
  legacy array shape on its own — confirmed via live test (shows "No image
  selected" despite valid underlying data). Rather than a permanent runtime
  `acf/load_value`/`acf/update_value` bridge (explicitly rejected — one-off
  bridges compound into long-term tech debt), this was resolved with a
  **one-time data normalisation** instead: a temporary, removable plugin —
  `wp-content/plugins/chairforce-woodmart-data-normalise/` — bundling
  [lambry/batchpress](https://github.com/lambry/batchpress) as a composer
  dependency (`composer/installers`-placed at `vendor/batchpress/` inside
  the plugin itself, so the whole tool installs/removes as one unit; see
  `skeleton-plugin-master` for the general composer-plugin convention this
  follows).
  - `wp-content/plugins/` is otherwise entirely gitignored in this repo
    (only the `chairforce` theme was previously excepted). Added a matching
    exception so only this one plugin is tracked — `!wp-content/plugins/` /
    `wp-content/plugins/*` / `!wp-content/plugins/chairforce-woodmart-data-normalise/`
    (same 3-line un-ignore/re-ignore/re-except pattern already used for the
    theme). Verified with `git check-ignore` against this plugin's own files
    (all tracked) and several other plugin folders (all still ignored).
  - **Still to do on staging/production**: this plugin needs installing +
    running once on each environment before/at cutover, then deactivating +
    deleting there too (dev is done).

  **The same legacy `image` shape turned out to exist on 12 *other*
  attribute taxonomies too** (found while auditing — not just `pa_colour`).
  A full term-meta inventory across `pa_material`, `pa_features`, `pa_seat`,
  `pa_size`, `pa_arms`, `pa_assembly`, `pa_backrest`, `pa_base-type`,
  `pa_folding`, `pa_height`, `pa_indoor-outdoor`, `pa_stackable` turned up:

  | Meta key | Finding | Action taken |
  |---|---|---|
  | `image` | Same legacy `['url','id']` shape as `pa_colour`; only `pa_features` "Weather Resistant" (#407) has a real value, rest are empty placeholders | **Migrated + built ACF field** (below) |
  | `color`, `not_dropdown`, `pa_term_hint` | 0 real values across all 12 taxonomies | Skipped — same "confirmed empty" rule as `pa_colour`'s equivalents |
  | `order` | Real, populated ordering ints (14–88 terms/taxonomy) | **Not a swatch field** — WooCommerce/Woodmart's admin-configured term display order. Different concern entirely; **new open item**, needs its own decision on how term ordering is preserved in the new theme (not started) |
  | `_wc_facebook_enhanced_catalog_attributes___optional_selector`, `_wc_facebook_google_product_category` | Empty; owned by the (currently inactive) Facebook for WooCommerce plugin | Skipped — that plugin's own data, irrelevant unless it's reactivated |
  | `xts-term-{ID}-status` | Value is literally the string `"invalid"` everywhere | Skipped — Woodmart/XTemos internal cache-validity marker, dies with Woodmart, not real data |
  | `cfvsw_image` | 2 real values on `pa_material` ("Timber", "Metal" → real texture photo URLs) from a **third, now fully-uninstalled** variation-swatches plugin (`cfvsw_*` options confirm `enable_swatches: true` etc., but no matching plugin folder exists anymore) | **Not migrated — open question for the client**, same "don't proactively backfill" rule as the `care_tab → care` case. If they want material texture icons back, these 2 URLs are sitting in `wp_options`/`wp_termmeta` ready to hand-place into the new `image` field below. |

  **Final shape, after two rounds of iteration** (first built `pa_colour`
  alone with a runtime bridge → replaced the bridge with a normalisation
  job → discovered the 12 other taxonomies needed the same treatment → built
  a second job + a second field group for those → then merged everything
  down to one of each, since keeping them separate was pure duplication):
  - **One BatchPress job**, `Normalise_Attribute_Swatch_Images`
    (`jobs/class-normalise-attribute-swatch-images.php`), with a
    `TAXONOMIES` const listing all 13 taxonomies (`pa_colour` + the 12
    above). Finds affected terms **dynamically** (`SELECT ... WHERE
    tt.taxonomy IN (...) AND tm.meta_key = 'image' AND tm.meta_value LIKE
    'a:%'` — no hardcoded term IDs) and converts each to a plain attachment
    ID, or clears empty legacy placeholders. Idempotent/safe to re-run.
  - **One shared ACF field group**, `group_pa_colour_swatch_fields`
    (`acf-json/` — kept its original key/field-keys unchanged on purpose,
    see below), with both a `color` (ACF Color Picker, alpha-enabled) and
    an `image` field, and 13 OR'd `taxonomy ==` location rule groups (ACF:
    multiple location groups = OR) rather than one group per taxonomy.
    `color` shows on all 13 (including the 12 with zero current real
    values) rather than special-casing it out — client is fine with this,
    simpler than maintaining an asymmetric field set.
  - **Field/group keys deliberately left unrenamed** even though the group
    now covers far more than "pa_colour" — renaming ACF field **keys**
    (`field_pa_colour_swatch_color`/`field_pa_colour_swatch_image`) risks
    orphaning ACF's own reference-meta bookkeeping for any already-saved
    data, which conflicts with this plugin's non-negotiable rule (normalise
    format only, never touch/risk data). Only the group's `title` and
    filename-irrelevant cosmetic details changed — zero risk, since term
    meta storage is keyed by field **name** (`color`/`image`), not field
    key, and those never changed either.
  - Run once via **Tools → BatchPress** in wp-admin (not WP-CLI/eval) —
    the whole point of using BatchPress is a visible, auditable, PM/client-
    demoable run with a log, not a script that leaves no trace. Verified
    end-to-end in the dev DB across both the original run and the later
    merge (re-run is a safe no-op — 0 items left, everything already
    normalised): 81 `pa_colour` terms (13 real images, 68 placeholders) +
    28 terms across the other 12 taxonomies (1 real image — `pa_features`
    "Weather Resistant" → attachment #1054849 — 27 placeholders). **Zero**
    remaining legacy-shaped `image` rows anywhere across all 13 taxonomies.
  - Verified `get_field()` and the term-edit admin screen both work fully
    natively for `pa_colour`, `pa_material`, `pa_features`, and
    `pa_stackable` terms, with **zero filters/bridge code** — the data was
    normalised before the field group was (re-)created.

  **Woodmart's admin "Preview" column — rebuilt.** The live Woodmart
  attribute term-list screens (`edit-tags.php?taxonomy=pa_*`) show a
  "Preview" column with a rendered swatch/icon per term — a Woodmart theme
  customisation
  (`inc/integrations/woocommerce/modules/swatches.php`,
  `manage_edit-pa_{attr}_columns` / `manage_pa_{attr}_custom_column`), not a
  WooCommerce core feature, that disappears entirely once Woodmart is
  removed. Rebuilt as `Chairforce\Attribute_Swatch_Preview_Column`
  (`lib/class-attribute-swatch-preview-column.php`) — same hook pattern,
  looped dynamically over `wc_get_attribute_taxonomies()` (not a hardcoded
  list, matching Woodmart's original approach exactly), reading the
  now-normalised `color`/`image` term meta directly (no legacy array
  unwrapping needed, unlike Woodmart's version). Auditing this surfaced
  **2 more attribute taxonomies neither the checklist nor 3c's inventory
  above had caught**: `pa_mounting` and `pa_shape` (15 total attribute
  taxonomies on the site, not 13). Both carry only `order` term meta —
  zero `color`/`image`/`not_dropdown`/`pa_term_hint` rows, not even empty
  legacy placeholders — so there's nothing to migrate and no ACF fields
  needed for them; the Preview column simply renders blank for their terms
  (correct, matches what the original site would have shown too). Verified
  in-browser on `pa_colour` (renders color swatches), `pa_material` (blank,
  no crash), and `pa_mounting` (blank, no crash). Swatch styling ported from
  Woodmart's `.wd-attr-peview` Sass rules, injected via
  `wp_add_inline_style()` scoped to attribute term-list screens only.
- Note for future term-field work: ACF's `get_field($name, $post_id)` by
  bare field **name** silently fails to resolve/format-filter values for
  any taxonomy term that has never been saved through ACF before (no
  `_{$name}` reference meta yet exists in `wp_termmeta`) — confirmed this
  also affects the pre-existing `venues`/`feature` term groups from 3a.
  The admin edit screen itself is unaffected (WordPress resolves fields via
  location rules, not the name-reference lookup). Any future frontend code
  reading term fields should use the field **key** as the `get_field()`
  selector, or read `get_term_meta()` directly.

#### 3d. Product card / grid swatches

**Status: ✅ Done (29 Jul 2026)** — `52847e0`, `6e6c1b0`, `83393c0`.
Plan: `context/plans/3b-3d-event-pattern-and-grid-swatches-plan.md` chunks
2–4. Verified on `/product-category/chairs/cafe-chairs/` (variable
`pa_colour` products — color swatches, hover image swap-and-stay, +N
collapse/expand, delegated handlers).

- Full spec: file `03` (Mode A / `select_options` only — image swap, no
  quick-shop form on card).
- Styling: file `05`, hardcoded to the one confirmed combo (`style 3`/
  `dis-style 3`/`round`/`m`) per file `09` §3 — no admin config needed.
- **Shipped:** dynamic JSX block `chairforce/product-swatches`
  (`src-jsx-blocks/product-swatches/`, PHP helpers in
  `lib/class-product-swatches.php`), theme-owned
  `templates/archive-product.html` override (swatches under product image),
  hover-triggered image swap on desktop (`mouseenter`, no revert on
  `mouseleave`), click fallback on touch, limit-swatches +N collapse
  (hardcoded count `3`).

#### 3e. Single-product swatches + gallery swap

**Status: ✅ Done (29 Jul 2026)** — `adf0788`, `bfa2d53`, `57601c5`, `7532023`.
Plan: `context/plans/3e-single-product-swatches-and-gallery-plan.md` chunks
1–4. Verified on `/product/breeze-chair/` (swatches above hidden select,
Black swatch → full variation gallery rebuild, Clear → default gallery) and
`/product/dario-kitchen-stool/` (no gallery meta — WC default image swap only).

- Full spec: file `04` (variation form, swatch → `found_variation`/
  `show_variation` wiring, main gallery + "additional variation images"
  mini-gallery swap).
- Admin-side data this depends on is already registered (3a) —
  `wd_additional_variation_images_data` on `product_variation`, native WC
  hooks in `lib/class-woocommerce-admin.php`.
- Frontend scaffold waiting for this work: `lib/class-woocommerce-single-product.php`.
- **Key findings from planning this phase** (full detail in the plan doc):
  - No template override needed — swatches inject via the
    `woocommerce_dropdown_variation_attribute_options_html` core filter.
  - This vendored WooCommerce (10.9.x) ships its own **native, first-party
    "Variation Gallery" feature**, but it's an experimental **canary**
    toggle (off by default, `Settings → Advanced → Features`), stores data
    under a different meta key (`_product_image_gallery`), and its own
    legacy-migration targets a different, already-confirmed-dead key
    (`_wc_additional_variation_images`, not our real data). **Decision:
    don't adopt it yet** — keep reading `wd_additional_variation_images_data`
    directly via our own filter; revisit as a future BatchPress migration
    if/when that core feature graduates out of canary.
  - The Swiper shared-carousel open item (below) is **narrowed, not
    resolved**: this phase's actual gallery (WooCommerce's classic
    `.woocommerce-product-gallery` markup) gets a complete carousel/zoom/
    lightbox story for free via three `add_theme_support()` flags
    (`wc-product-gallery-slider`/`-zoom`/`-lightbox`) — no Swiper needed
    for *this* feature. Swiper remains deferred to its first real future
    consumer (testimonials, category sliders, etc.).

#### 3f. Shop/category product archive filters (Figma bar + AJAX)

**Status: 🔄 In progress (31 Jul 2026)** — implementation landed locally; browser QA
ongoing.

- Investigation: `context/notes/product-filters-findings.md`.
- Implementation plan: `context/plans/3f-product-filters-plan.md` (plan still
  references old `mode=replace`; live code uses **archive shell partial reload**
  — see `12A-woodmart-ajax-shop-filtering.md`).
- Mechanism (Woodmart PJAX-style shell reload + Load More append REST — **not**
  `jet-smart-filters`): files `12`, `12A`.
- **Shipped (uncommitted):** filter bar/panel/chips partials;
  `includes/archive-shell-functions.php` (`?_cf_archive=shell` fragment);
  `src/js/product-filters.js` (shell swap + `pushState`); Load More REST
  append-only (no `mode`); `lib/class-woocommerce-archive.php` shell wrapper.
- **Fixed (31 Jul 2026):** post-filter single-column grid — shell now renders
  product collection via `do_blocks()` on `parts/product-collection.html` so
  `wc-block-product-template__responsive` classes match SSR.
- **Browser QA (31 Jul 2026):** filter apply (Arms), faceted counts, chip +
  Clear all, 3-column grid after shell swap, Load More append after filter — ✅.
  Still to verify: sort change, `popstate`, price filter, multi-filter stack,
  Quick View/wishlist on post-swap cards.
- **Locked:** dynamic filter buttons; card-grid panel (all filters); AJAX +
  `pushState` (no reload); native WC `Filterer` only (no WPGB); panel
  orientation theme options **`vertical` | `horizontal`** (default desktop
  **vertical**; mobile **vertical**); Load More stays append-only REST (filter
  refresh = shell reload, not REST replace).
- Also covers filter UX on archives where `venues` / `sales-by-location` are
  the main query (taxonomy archives), not as sidebar filter widgets — see
  findings doc §1.

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

**Status: ✅ Done (29 Jul 2026)** — `61630e0`, `0685c9c`, `aff0297`, `c21dfaf`.
Plan: `context/plans/3h-quick-view-plan.md` chunks 1–4. Verified on
`/product-category/chairs/cafe-chairs/` (drawer — eye trigger, Breeze Armchair
popup with swatch→gallery swap inside popup; Escape → different product reopen;
modal skin after ACF switch). REST verified for Breeze Chair (#1000290).

- Full spec: file `16` (AJAX reuse of single-product markup — rebuilt as REST +
  vanilla JS shell, not Woodmart/Magnific).
- **Shipped:** `GET /wp-json/chairforce/v1/quick-view/{id}` (WooCommerce
  `before_single_product_summary` + wrapped `single_product_summary` only —
  no tabs/related); ACF `quick_view_display` (`Modal`|`Drawer`, default
  `drawer`); `chairforce/quick-view-button` block in `cf-card-media` wrapper;
  `src/js/quick-view.js` lazy shell + `primeVariationForms()` on inject; Sass
  `--modal`/`--drawer` skins + eye icon (`0xe0ba`).

#### Product grid card parity (3d/3h follow-up)

**Status: ✅ Done (31 Jul 2026)** — `2a455c9`.

- Tracker: `context/implementation/product-grid.md`.
- Notes/decision history: `context/notes/product-grid-cards-and-load-more.md`.
- **Shipped:** canonical card in `parts/product-card.html` (swatches + quick
  view blocks inside `cf-card-media` / `cf-wrapp-swatches` wrappers), referenced
  from `parts/product-collection.html`, `parts/product-related.html`, and
  `parts/product-upsells.html`. Removed `WooCommerce_Archive::inject_quick_view_button()`
  and the short-lived locked ACF `chairforce/product-card` experiment (`785febf`,
  reverted in `2a455c9`). Block Hooks approach (spiked 30 Jul 2026) superseded by
  the shared template-part strategy — same outcome, simpler maintenance.
- **Templates:** `archive-product.html` → `product-collection` part;
  `templates/single-product.html` → upsell + related parts (legacy WC hook grids
  removed).

#### 3i. Page-1 Load More (WooCommerce Product Collection archives)

**Status: ✅ Done (31 Jul 2026)** — `d7d7acc`, `fe21084`, `3e2546a`. Plan:
`context/plans/3i-load-more-plan.md`. **Minor quirks may remain** — polish
pass optional (see Known open issues below).

- Full spec: file `17` (delegated-events architecture); investigation:
  `context/notes/load-more-findings.md`.
- **Shipped:** REST `GET /wp-json/chairforce/v1/load-more`; `Load_More` class
  extending `core/query-pagination` with `loadMore` attribute (page 1 only —
  page 2+ keeps crawlable numbered pagination); `src/js/shared/load-more.js`;
  appended cards rendered from the same `parts/product-card.html` source as the
  initial grid (no markup drift). `query-loop-load-more` plugin superseded.
- **Follow-up fixes:** WC catalog ordering for `?orderby=price` /
  `price-desc` (`fe21084`); grid swatch JS moved to global `public.js` so
  Load More batches get hover image swap when page 1 omits swatches
  (`3e2546a`).

#### 3j. Wishlist (logged-in only, custom table)

**Status: ✅ Done (31 Jul 2026)** — `6e7b485`, `3e2546a`. Plan:
`context/plans/3j-wishlist-plan.md` chunks 1–4.

- Investigation: file `13` (Woodmart native — 3 empty shells, 0 products; no
  migration). **Locked:** logged-in users only; single list; custom table
  `{prefix}chairforce_wishlist_items`; no header badge; no guest cookies.
- **Theme options (WooCommerce tab):** `wishlist_enabled` (master),
  `wishlist_loop_enabled` (heart on product cards).
- **Shipped:** `Chairforce\Wishlist` + REST toggle/list/status;
  `chairforce/wishlist-button` on product cards + single product; guest click
  → My Account login with `redirect` back (`chairforce_get_wishlist_login_url()`);
  `cf-card-actions` wrapper for heart + quick-view stack.
- **Deferred to Phase 5 (My Account):** account endpoint UI + wishlist list
  page (plan chunk 5) — endpoint registration can ship with account work.

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

- **My Account wishlist page** (3j plan chunk 5 — list UI + endpoint template)
  ships here, not as part of 3j closure.
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

- ~~**`templates/archive.html` renders no grid items on any taxonomy
  archive WooCommerce doesn't specifically own**~~ — ✅ Resolved.
  `templates/archive.html` and `templates/index.html` both referenced
  `chairforce/query-post-card-blokki`, a block **never registered in this
  theme** (`src-acf-blocks/` only has `acf-field-display`; the block exists
  only in reference themes `bjm-briks`/`lasersight`/`shineon`) —
  WordPress silently renders empty output for unregistered blocks, which is
  why `/venue/commercial/` showed pagination but no cards.
  `product_cat`/`product_tag`/`pa_*` attribute/`product_brand` archives
  were unaffected because WooCommerce's own bundled block templates
  (`ProductCategoryTemplate` etc. in
  `wp-content/plugins/woocommerce/src/Blocks/Templates/`) take over before
  the theme's generic `archive.html` is ever reached. Both templates were
  rebuilt using native core blocks (`post-featured-image`, `post-terms`,
  `post-title`, `post-author`, `post-date`) in place of the missing block —
  covers the plain blog archive (**Phase 5**) and any other generic
  taxonomy archive. Still worth a follow-up check once **3f**'s
  `sales-by-location`/`feature` taxonomy archives are built, to confirm
  they render through this same path (or get their own dedicated template).

- **Load More — minor quirks pending** — core feature shipped (`d7d7acc`) and
  marked complete in docs; a short polish pass remains before calling it fully
  verified in browser. Track fixes in the next session, then close this item.
- ~~**`chairforce/quick-view-button` block is dead code**~~ — ✅ Resolved
  (`2a455c9`). Block is live again via explicit placement in
  `parts/product-card.html`; runtime `render_block` injection and the duplicate
  `chairforce_get_quick_view_button_html()` helper removed.

## Open decisions blocking future phases

Carried from checklist file `19` §8 — resolve before/during the phase noted:

- Which page renders the showroom "pickup selector" (file `14` §B) —
  blocks part of **Phase 4**.
- ~~Exact archive page for the "Product sale by location" filter (file `14`
  §I) — blocks **3f**.~~ — ✅ Resolved (31 Jul 2026): existing
  `/sales-by-location/{term-slug}/` archives; WC Product Collection template
  alignment deferred.
- ~~Where the mega-menu's "Shop by Space" link should point (likely `venues`
  taxonomy archive) — blocks **3f**/**Phase 4**.~~ — ✅ Resolved (31 Jul
  2026): top-level **`venues`** archives (e.g. `/venue/hospitality/`); menu
  builder script populates from live terms.
- "Checkout notice popup" — confirm still wanted, zero live trigger found
  (file `14` §J) — blocks nothing yet, just needs a yes/no.
- `/gallery/` page's exact pagination mode (infinite-scroll vs. load-more) —
  blocks **Phase 6**.
- Contact page's showroom Select filter and the "Download Catalog"
  `catalogue-links` CTA (checklist §4/§4a) — need a page owner (Phase 2/4 vs.
  their own pass) before scheduling.
- **Attribute term display order** (`order` term meta, populated on
  `pa_material`/`pa_features`/`pa_seat`/`pa_size`/`pa_arms`/`pa_assembly`/
  `pa_backrest`/`pa_base-type`/`pa_folding`/`pa_height`/`pa_indoor-outdoor`/
  `pa_stackable` — found during 3c's other-attribute-taxonomy inventory) —
  not a swatch field, but real, actively-used data controlling the
  admin-configured order these terms appear in on the frontend (filters,
  variation dropdowns, etc.). Needs its own decision on how term ordering
  is preserved/rebuilt in the new theme — not started, not blocking 3c.
- **`pa_material` "Timber"/"Metal" texture icons** (`cfvsw_image` meta,
  found during 3c's inventory) — 2 real image URLs left over from a third,
  now fully-uninstalled variation-swatches plugin. Not migrated into the
  new `image` field (3c) — ask the client whether they still want these
  material-texture icons; if yes, it's a 2-term manual backfill, not a
  batch job.
- ~~**Load More UI, deferred out of 3b**~~ — ✅ Done as **3i** (`d7d7acc`).
  Page-1 Load More on inherited Product Collection archives; minor quirks
  pending polish (see Known open issues). **Shared Swiper carousel** remains
  deferred — not needed for single-product gallery (see 3e); first real consumer
  still TBD (testimonials, category sliders, etc.).
- ~~**Card composition mechanism (swatches + quick view) — Block Hooks rework**~~
  — ✅ Done via shared **`parts/product-card.html` template part** (`2a455c9`),
  not Block Hooks. Block Hooks spike passed (30 Jul 2026) but implementation
  chose the template-part approach instead — see
  `context/notes/product-grid-cards-and-load-more.md` §6 decision log.
- ~~**"Limit swatches" (+N collapse) vs. always-expanded** and **whether
  the grid-hover gallery-preview feature is wanted independent of
  swatches**~~ — ✅ Resolved (client decision, both in the 3d plan's
  chunk 3): **+N collapse, yes** — hardcoded limit count, not an
  admin-configurable setting (code change on request if the client wants
  a different number later); grid-hover gallery-preview feature — **not
  needed**. Also resolved: the swatch **interaction trigger is hover, not
  click** (matches the live child-theme customization, file `07` §1) —
  swap on `mouseenter`/desktop, no auto-revert on `mouseleave`, click
  fallback for touch devices.
- ~~**Attribute term-list "Preview" column"**~~ — ✅ Done. Rebuilt as
  `Chairforce\Attribute_Swatch_Preview_Column`
  (`lib/class-attribute-swatch-preview-column.php`), registered in
  `class-init.php`. Loops over `wc_get_attribute_taxonomies()` dynamically
  (matching Woodmart's original approach exactly) rather than a hardcoded
  list — while auditing this, found the site actually has **15** attribute
  taxonomies, not the 13 already covered by the `pa_colour` ACF group/
  BatchPress job: `pa_mounting` and `pa_shape` were missed by the earlier
  inventory. Checked both — they carry only `order` term meta, no
  `color`/`image`/`not_dropdown` at all (not even empty legacy
  placeholders), so there's nothing to migrate and no ACF fields needed for
  them yet; the Preview column just renders blank for their terms, which is
  correct/harmless. Styling ported from Woodmart's `.wd-attr-peview` Sass
  (`inc/admin/assets/sass/pages/wordpress/_post-type-list.scss`) as a
  same-size circular swatch, injected via `wp_add_inline_style()` scoped to
  `edit-tags.php?taxonomy=pa_*` screens only. Verified in-browser on
  `pa_colour` (color swatches), `pa_material` (blank/no-crash), and
  `pa_mounting` (blank/no-crash, confirms the 2 newly-found taxonomies don't
  break anything).

## Related docs

| Doc | Purpose |
|---|---|
| `context/existing-functionality/README.md` | Index of all research docs `01`–`20`, plus the three QA addenda with DB-verified facts |
| `context/existing-functionality/11-recommendation-and-implementation-plan.md` | The research's own conclusion + feature-by-feature plan + sequencing — this file's main source |
| `context/existing-functionality/19-master-rebuild-registration-checklist.md` | Full registration checklist + two-bucket rule (source of the phase legend) |
| `context/plans/registration-and-acf-schema-plan.md` | Execution plan + status for Phase 3a specifically |
| `context/plans/3b-3d-event-pattern-and-grid-swatches-plan.md` | Execution plan + status for Phase 3b (event-delegation convention, narrowed scope) and 3d (product card/grid swatches) |
| `context/notes/product-grid-cards-and-load-more.md` | Card-composition parity problem + decision history (Block Hooks spike → template-part solution) |
| `context/implementation/product-grid.md` | Short tracker for shared product card, quick view, swatches, and Load More wiring |
| `context/plans/3e-single-product-swatches-and-gallery-plan.md` | Execution plan for Phase 3e (single-product swatches + gallery swap) — includes the WooCommerce-native-canary-feature and Swiper-narrowing findings |
| `context/plans/3h-quick-view-plan.md` | Execution plan for Phase 3h (Quick View rebuild — modal/drawer shell, REST endpoint, reuses 3e swatch/gallery component) |
| `context/notes/product-filters-findings.md` | Phase 3f investigation + locked UX/engine decisions |
| `context/plans/3f-product-filters-plan.md` | Execution plan for Phase 3f (archive filters) |
| `context/plans/3i-load-more-plan.md` | Execution plan for Phase 3i (page-1 Load More on Product Collection archives) |
| `context/plans/3j-wishlist-plan.md` | Execution plan for Phase 3j (logged-in wishlist — custom table, loop + single; account endpoint deferred) |
| `context/notes/load-more-findings.md` | Load More investigation, rejected plugin, architecture notes |
| `context/plans/header-mega-menu-plan.md`, `header-mega-menu-cleanup-notes.md` | Phase 1 implementation |
| `context/plans/lucide-icon-system-plan.md`, `icon-block-plugin-integration-plan.md` | Icon system infrastructure (done) |
| `context/decisions/jetengine-and-jet-smart-filters-vs-native-rebuild.md` | Locked decision: no Jet\* plugins as live dependencies, native rebuild throughout |
| `.cursor/skills/chairforce-woocommerce/` | WC-specific implementation conventions for Phase 3d–3h work |
| `context/kb/theme-color-mapping.md`, `typography-token-mapping.md` | Design-token reference used across all frontend phases |
| `context/figma/` | Source screens/components referenced throughout |
