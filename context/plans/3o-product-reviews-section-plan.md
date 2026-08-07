# 3o — Product Reviews Section (Figma) — Implementation Plan

## Status: ✅ Done (Aug 2026)

Plan locked from product-page review (6 Aug 2026).

| Chunk | Scope | Status |
|---|---|---|
| 1 | Theme options (reviews display mode + tab toggles) + visibility helpers | ✅ |
| 2 | `chairforce/product-reviews-summary` block (average + histogram) | ✅ |
| 3 | `chairforce/product-reviews` block (list + form + native pagination) | ✅ |
| 4 | Tab integration (unset WC reviews tab in section mode; gate custom tabs) | ✅ |
| 5 | Sass (Figma layout) + `single-product.html` wiring + browser QA | ✅ |

## Goal

Replace the default **WooCommerce Reviews tab UI** with a **Figma-aligned
standalone reviews section** on single product pages, while keeping **native WC
review data and submission** (WP comments + rating meta — not the `review` CPT).

Deliver:

1. **`chairforce/product-reviews-summary`** — standalone dynamic block: average
   rating, star display, 5→1 histogram, total count (insert anywhere).
2. **`chairforce/product-reviews`** — dynamic block wrapper with **`id="reviews"`**:
   review list, native comment pagination, review form with show/hide rules.
3. **Theme options** — global controls for reviews display mode and per-tab
   visibility; custom tabs gated behind toggles **and** existing content checks.
4. **Mutual exclusivity** — Reviews in **tab** mode (WC default tab) **or**
   **section** mode (custom blocks in template) — not both.

**Design reference:**

- Figma: `context/figma/components/Single Product review Section.png`
- Current baseline (WC tab): screenshot in chat 6 Aug 2026 — simple list inside
  Product Details tabs.

**Source docs (read before implementing):**

- `woocommerce/templates/single-product-reviews.php` — list, form, pagination,
  `#reviews` anchor (override reference, do not fork submission logic blindly).
- `lib/class-woocommerce-single-product.php` — existing custom tab registration.
- `acf-json/group_theme_options.json` — WooCommerce tab (wishlist/filters pattern).
- `src-jsx-blocks/product-features/` — dynamic block + `usesContext: postId`
  pattern.
- `context/PROGRESS.md` — Phase 3g tabs shipped; this plan extends single-product UX.

---

## Locked decisions

### Data source

| Source | Use |
|---|---|
| **WC product reviews** (WP `comment` rows, type `review`, `rating` comment meta) | ✅ **Yes** — fetch, display, submit |
| **`review` CPT** (testimonials) | ❌ **No** — different content type (Phase 4a) |

Use WooCommerce APIs where available:

- `$product->get_review_count()`, `$product->get_average_rating()`
- `get_comments()` / review query with `post_id`, `status => approve`, `type => review`
- `wc_review_is_from_verified_owner( $comment )` for verified badge (Figma)
- Review form: reuse WC `comment_form` filters / field structure from
  `single-product-reviews.php` (do not hand-roll POST handling)

### Display mode (global — Theme Options)

| Field | Type | Default | Purpose |
|---|---|---|---|
| `product_reviews_enabled` | `true_false` | `1` | **Master switch.** Site-wide reviews feature. When off: no reviews tab, no reviews section, no form. |
| `product_reviews_display` | `button_group` | `section` | **`tab`** = WC Reviews tab only (legacy/minimal). **`section`** = Figma standalone section; **unset** `reviews` from `woocommerce_product_tabs`. |

**PHP helpers** (pattern matches wishlist):

```php
function chairforce_is_product_reviews_enabled(): bool;
function chairforce_is_product_reviews_section_mode(): bool;
function chairforce_is_product_reviews_tab_mode(): bool;
function chairforce_is_product_reviews_visible_for_product( int $product_id ): bool;
```

### Visibility rule (global **and** per-product)

Reviews UI (tab **or** section, including **empty state with form**) renders only when **both** are true:

1. **Global:** `product_reviews_enabled` + `woocommerce_enable_reviews` option is `yes`.
2. **Per-product:** product allows reviews — `$product->get_reviews_allowed()` /
   `comment_status === 'open'` (WooCommerce “Allow customer reviews?”).

**Important:** Unlike content tabs (Dimensions, Care, etc.), the reviews **section
must still render when there are zero reviews** — show the form so the column is
not empty. List/histogram/summary areas adapt to empty state (see below).

**Catalog note:** All published products currently have `comment_status = closed`
(DB Aug 2026). Reviews will not appear until per-product reviews are enabled
(bulk update or product editor). Theme code cannot bypass this without breaking
WC’s purchase-verification rules.

### Custom product tab toggles (Theme Options)

Add master toggles on **Chairforce → Theme Options → WooCommerce** (same tab as
wishlist). Each toggle is **AND**ed with existing “has content” checks in
`WooCommerce_Single_Product::register_product_tabs()`.

| Field | Default | Tab key |
|---|---|---|
| `product_tab_overview_enabled` | `1` | `description` (Overview) |
| `product_tab_delivery_enabled` | `1` | `cf_delivery_information` |
| `product_tab_dimensions_enabled` | `1` | `cf_dimensions` |
| `product_tab_care_enabled` | `1` | `cf_care` |
| `product_tab_parts_enabled` | `1` | `cf_parts` |
| `product_tab_additional_info_enabled` | `1` | `cf_additional_information` |
| `product_tab_product_info_enabled` | `1` | `cf_product_info` |

Overview toggle hides the description tab entirely when off (features block inside
Overview goes with it — document for editors).

When `product_reviews_display = tab` and reviews visible for product: WC
`reviews` tab remains (priority 80, already set in theme).

When `product_reviews_display = section`: **`unset( $tabs['reviews'] )`** in
`register_product_tabs()` (after WC default registration).

---

## Blocks

### 1. `chairforce/product-reviews-summary` (standalone)

**Purpose:** Average + histogram card (Figma left column). Insert anywhere —
typically left column beside the list block.

| Piece | Detail |
|---|---|
| Type | Dynamic JSX block (`save.js` → `null`, `render.php`) |
| Context | `usesContext: [ "postId" ]` |
| Inserter | Hidden via `Editor_Curation` (template-placed; same as `product-features`) |
| Data | Loop approved review comments; read `rating` meta; compute average + counts per star (5→1) |
| Empty state | Show `0` / empty bars / “No reviews yet” copy — still render card shell when section visible |
| Markup | BEM: `.cf-product-reviews-summary`, `.cf-product-reviews-summary__average`, `__bars`, `__bar`, `__count` |
| CTA | Optional “Write a Review” button that scrolls/focuses `#review_form` in sibling section block (data attribute or `#reviews` anchor) — coordinate with section block |

**No pagination or form in this block** — summary only.

### 2. `chairforce/product-reviews` (section)

**Purpose:** Review list + form + pagination (Figma right column). Wrapper carries
**`id="reviews"`** for deep links and WC compatibility.

| Piece | Detail |
|---|---|
| Type | Dynamic JSX block |
| Context | `usesContext: [ "postId" ]` |
| Block attributes | `reviewsPerPage` (number, default **3**) |
| List | Custom markup per Figma (avatar/initials, name, date, stars, body); use WC verified helper where applicable |
| Pagination | **Native WP comment pagination** — do not build Load More. Use `paginate_comments_links()` / WC template pattern. Filter `comments_per_page` (or equivalent) from block attribute `reviewsPerPage`. Requires Discussion “break comments into pages” **or** explicit paged `get_comments` + paginate helper — verify in chunk 3. |
| Form | See **Form behaviour** below |
| Sass | `src/sass/woocommerce/_product-reviews.scss` |

**Template wiring** (`templates/single-product.html`) — section mode only:

```html
<!-- wp:columns {"align":"wide"} -->
  <!-- wp:column -->
    <!-- wp:chairforce/product-reviews-summary /-->
  <!-- /wp:column -->
  <!-- wp:column -->
    <!-- wp:chairforce/product-reviews /-->
  <!-- /wp:column -->
<!-- /wp:columns -->
```

Place **below** `woocommerce/product-details`. Wrap in a group with constrained
layout if Figma full-width background is needed (chunk 5).

---

## Form behaviour (locked)

| State | List | Form | Primary action |
|---|---|---|---|
| **Has ≥1 review** | Visible + pagination | **Hidden by default** | **“Add Review”** button toggles form visible (JS); button hides when form open |
| **Zero reviews** | Hidden / empty message | **Visible by default** | No toggle needed — form is the CTA |

Implementation notes:

- Reuse WC review form fields (rating select, comment textarea, author fields for
  guests) via `comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', … ) )`.
- Form lives inside `#reviews` / `#review_form_wrapper` (match WC IDs for scripts).
- Minimal JS module: `src/js/product-reviews-form-toggle.js` (import from `public.js`
  only on single product if needed).
- Logged-out + must-purchase rules: keep WC defaults (`woocommerce_review_rating_verification_required`, etc.) — show WC’s “Only logged in customers…” message when form cannot render.

---

## Figma vs v1 scope

| Figma element | v1 |
|---|---|
| Average + histogram | ✅ Summary block |
| Review list cards | ✅ Section block (custom markup) |
| Verified purchase badge | ✅ via `wc_review_is_from_verified_owner()` |
| Sort (“Most Recent”) | ⏳ **Defer** — default WP comment order (newest first via query) unless trivial |
| **Load More** button | ❌ **Not built** — native pagination only |
| Helpful / Report links | ❌ **Out of scope** — not in WC core |
| Review title line | ❌ **Out of scope** — WC reviews have no title field unless plugin adds meta |

---

## Architecture

```
Theme Options (global)
├── product_reviews_enabled
├── product_reviews_display: tab | section
└── product_tab_*_enabled (custom tabs)

Per product
└── comment_status open + get_reviews_allowed()

woocommerce_product_tabs filter
├── Gate custom tabs (option AND content)
├── tab mode: keep reviews tab (priority 80)
└── section mode: unset reviews tab

single-product.html (section mode)
├── product-details (tabs without reviews)
└── columns
    ├── product-reviews-summary
    └── product-reviews (#reviews)

includes/product-reviews-functions.php
├── chairforce_get_product_review_comments( $product_id, $args )
├── chairforce_get_product_review_rating_stats( $product_id ) → avg + histogram
├── chairforce_render_product_review_form( $product_id, $args )
└── chairforce_should_show_product_reviews( $product_id )
```

---

## Implementation chunks

### Chunk 1 — Theme options + visibility helpers

**Files:**

- `acf-json/group_theme_options.json` — new fields on WooCommerce tab
- `includes/product-reviews-functions.php` — stats/query/helpers
- `includes/init.php` — require helpers
- `includes/helper-functions.php` — thin wrappers for options (or keep in reviews include)

**Verify:**

- `ddev wp eval` — toggling options changes `chairforce_should_show_product_reviews()`
- Tab filter respects new tab toggles

### Chunk 2 — Summary block

**Files:**

- `src-jsx-blocks/product-reviews-summary/` — block.json, index, edit, save, render.php
- `src/sass/woocommerce/_product-reviews-summary.scss`

**Verify:**

- Product with 2 reviews shows correct average + bar counts
- Product with 0 reviews shows empty summary without fatal

### Chunk 3 — Reviews section block

**Files:**

- `src-jsx-blocks/product-reviews/` — block.json (+ `reviewsPerPage` attribute), render.php
- `src/js/product-reviews-form-toggle.js`
- `src/sass/woocommerce/_product-reviews.scss`
- Extend `lib/class-woocommerce-single-product.php` — unset reviews tab in section mode

**Verify:**

- `#reviews` present in DOM
- Pagination appears when > `reviewsPerPage` comments
- Form hidden + “Add Review” when reviews exist; form visible when none
- Submitting a review still works (WC flow)

### Chunk 4 — Tab gating refactor

**Files:**

- `lib/class-woocommerce-single-product.php` — apply all `product_tab_*_enabled` checks

**Verify:**

- Disabling “Dimensions” in options removes tab even when product has dimensions meta

### Chunk 5 — Template + Figma polish + docs

**Files:**

- `templates/single-product.html` — columns layout (section mode; conditional via template or always place blocks with render.php no-op when tab mode)
- `lib/class-editor-curation.php` — hide new blocks from inserter
- `context/PROGRESS.md` — add 3o row + link this plan
- `npm run build:blocks && npm run build:assets`

**Verify:**

- `/product/new-dario-kitchen-stool/` matches Figma layout at wide breakpoint
- Tab mode still works when switched in Theme Options
- Mobile: columns stack (summary above list)

---

## Editor / SSR notes

- **Dynamic blocks only** — editor shows placeholder cards (same pattern as
  `product-features`).
- **`render.php` no-op** when `! chairforce_should_show_product_reviews( $product_id )`
  **except** consider whether template blocks should always occupy space — prefer
  **no wrapper output** when globally/per-product disabled.
- **Tab mode:** summary + section blocks output nothing; WC tab handles reviews.

---

## Open questions (resolve in chunk 1 if needed)

1. **Bulk-enable product reviews** — one-time `comment_status = open` migration for
   all products? (Separate ops task, not blocking theme work.)
2. **Overview tab toggle** — when off, move `product-features` elsewhere or accept
   features hidden? (Default: features only show inside Overview tab.)
3. **Discussion settings** — confirm `page_comments` on in WP Discussion settings
   for native pagination; document in plan verification step.

---

## Test plan (browser)

- [ ] Global reviews off → no tab, no section, no form
- [ ] Section mode + product reviews allowed + 0 reviews → summary empty, form visible, no “Add Review” toggle
- [ ] Section mode + ≥1 review → list + pagination (per page 3), form hidden, “Add Review” reveals form
- [ ] Tab mode → WC Reviews tab visible, section blocks empty
- [ ] Per-product reviews disabled → no section/tab for that product
- [ ] Verified owner badge when applicable
- [ ] Submit review as eligible logged-in customer
- [ ] `#reviews` URL fragment scrolls to section

---

## Related

| Doc | Purpose |
|---|---|
| `context/figma/components/Single Product review Section.png` | Layout target |
| `context/plans/3j-wishlist-plan.md` | Theme options + helper pattern |
| `.cursor/rules/00-main-rules.mdc` | DDEV / `ddev wp` for verification |
