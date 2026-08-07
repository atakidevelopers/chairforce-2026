# 3l Chunk 3 — Product Category Banner — Implementation Plan

## Status: 📋 Plan locked (Aug 2026)

Parent plan: `context/plans/3l-product-category-archive-chrome-plan.md` (Chunks 1–2 ✅).

| Chunk | Scope | Status |
|---|---|---|
| 1 | `chairforce_banner` CPT (Gutenberg) | ⏳ Not started |
| 2 | Banner Configurations ACF options sub-page | ⏳ Not started |
| 3 | Resolution helpers + `chairforce/product-cat-banner` block | ⏳ Not started |
| 4 | `archive-product.html` wiring + verification | ⏳ Not started |

**Chunk 4 (thumbs grid)** in the parent 3l plan is **dropped** — category swiper blocks
cover that use case.

**Design reference:**

- Archive chrome context: `context/figma/components/Product Category Title Area.png`
  (+ Mobile) — banner sits **above** the existing title / description / child-swiper
  header band unless Figma dictates otherwise during build.
- Banner **content** is editor-authored in the Banner CPT (blocks, not a fixed Figma
  component).

**Related shipped work:**

- FAQ Configurations pattern: `context/plans/3p-product-faqs-section-plan.md`,
  `acf-json/group_faq_configurations.json`, `includes/product-faqs-functions.php`,
  `lib/class-acf.php` (options sub-page + hierarchical category picker + duplicate-row
  validation).
- Category archive template: `templates/archive-product.html` (title, description,
  `chairforce/product-cat-child-swiper`).

---

## Goal

On **`product_cat` taxonomy archives**, optionally render a **reusable Gutenberg banner**
above (or around) the existing archive chrome.

Deliver:

1. **`chairforce_banner` CPT** — editors build banner content with core/theme blocks.
2. **Banner Configurations** ACF options sub-page — central mapping of
   `product_cat` → banner, with optional **inherit to child categories**.
3. **`chairforce/product-cat-banner`** dynamic block — resolves banner for the queried
   term and outputs the banner post’s block content; **no output** when unmapped.
4. **Template placement** in `archive-product.html` (Site Editor–editable).

Not every category gets a banner. The config repeater is the **inventory** of which
categories are mapped — easier to audit than per-term meta scattered across the category
tree.

---

## Locked decisions

### Architecture (two layers)

| Layer | Responsibility |
|---|---|
| **Banner CPT** | Creative content only — Gutenberg `post_content`, optional featured image. No taxonomy logic. |
| **Banner Configurations** | Assignment only — which `product_cat` uses which banner post. |
| **Archive block** | Resolve banner ID for queried term → render CPT content. |

Same separation as **`chairforce_faq`** + FAQ Configurations (3p).

**Rejected for v1:** ACF field on `product_cat` term meta — harder to track mappings
and reuse banners across categories from one screen.

### Banner CPT

| Property | Value |
|---|---|
| Post type slug | `chairforce_banner` |
| Admin label | Banners |
| Supports | `title`, `editor`, `revisions` (thumbnail optional — banner body is block-driven) |
| `show_in_rest` | `true` (block editor) |
| `public` | `true` |
| `publicly_queryable` | `false` (no public single URLs required — rendered only via block) |
| `has_archive` | `false` |
| `exclude_from_search` | `true` |
| Menu icon | `dashicons-cover-image` (or similar) |

Register in `includes/register-cpt.php` (same file as `chairforce_faq`).

### Banner Configurations (options sub-page)

Register via `acf_add_options_sub_page()` in `lib/class-acf.php`:

| Property | Value |
|---|---|
| `page_title` | Banner Configurations |
| `menu_title` | Banner Configurations |
| `menu_slug` | `chairforce-banner-configurations` |
| `parent_slug` | `edit.php?post_type=chairforce_banner` |
| `capability` | `manage_options` |

### Config repeater row

One row per **product category** (unique term ID — validate on save, same pattern as
FAQ Configurations).

| Sub-field | Name | Type | Notes |
|---|---|---|---|
| Product category | `product_category` | `taxonomy` | `product_cat`, `field_type`: `select`, return: term ID, required |
| Banner | `banner` | `post_object` | Same field type/settings as FAQ Configurations repeater (`faqs` on `field_faq_category_posts`): `post_type`: `chairforce_banner`, `return_format`: `id`, `ui`: 1, `allow_null`: 0, **`multiple`: 0** (single banner per row; FAQs use `multiple`: 1) |
| Display on child categories | `display_on_child_categories` | `true_false` | Default `0`. When enabled, descendant category archives inherit this row’s banner **unless** the descendant has its own row. |

**Repeater field:** `banner_category_rows` on options page `chairforce-banner-configurations`.

**UI copy (row):**

- Checkbox label: **Display banner on child categories**
- Instructions: *When checked, this banner also appears on archives of direct and nested child categories that do not have their own banner row.*

Reuse FAQ Configurations admin UX:

- Hierarchical indented labels on category select (`acf/fields/taxonomy/result` filter).
- Include all terms (`hide_empty => false`).
- Duplicate category validation (`acf/validate_value` on `product_category` field).

### Resolution algorithm (archive)

**Input:** queried `product_cat` term ID on a taxonomy archive.

**Preload:** read `banner_category_rows` from options; build map
`term_id => [ 'banner_id' => int, 'display_on_child_categories' => bool ]`.

**Steps:**

1. **Exact match** — if the queried term has a row with a valid `banner_id`, return
   that ID (checkbox state on **this** row does not matter for the term itself; it only
   affects descendants).
2. **Ancestor inherit** — walk **parent → grandparent → …** toward root. For each
   ancestor that has a row with a valid `banner_id` **and**
   `display_on_child_categories === true`, return that banner ID (**closest ancestor
   wins**).
3. **No match** — return `0` (block outputs nothing).

**Pseudocode:**

```php
function chairforce_resolve_product_cat_banner_id( int $term_id ): int {
    $rows_by_term = chairforce_get_banner_configuration_rows_by_term();

    if ( isset( $rows_by_term[ $term_id ]['banner_id'] ) ) {
        return (int) $rows_by_term[ $term_id ]['banner_id'];
    }

    $ancestor_id = $term_id;
    while ( $ancestor_id = (int) wp_get_term_taxonomy_parent_id( $ancestor_id, 'product_cat' ) ) {
        if ( empty( $rows_by_term[ $ancestor_id ] ) ) {
            continue;
        }
        $row = $rows_by_term[ $ancestor_id ];
        if ( ! empty( $row['display_on_child_categories'] ) && ! empty( $row['banner_id'] ) ) {
            return (int) $row['banner_id'];
        }
    }

    return 0;
}
```

**Examples:**

| Config | Archive viewed | Result |
|---|---|---|
| Chairs → Banner A, inherit **on** | `/product-category/chairs/` | Banner A |
| Chairs → Banner A, inherit **on** | `/product-category/chairs/cafe-chairs/` (no row) | Banner A |
| Chairs → Banner A, inherit **on**; Cafe Chairs → Banner B | `/product-category/chairs/cafe-chairs/` | Banner B (exact match wins) |
| Chairs → Banner A, inherit **off** | `/product-category/chairs/cafe-chairs/` (no row) | Nothing |

### Block output scope

**`chairforce/product-cat-banner`** outputs:

- Wrapper from `get_block_wrapper_attributes()` (block-scoped class e.g.
  `cf-product-cat-banner`).
- Inner: rendered banner CPT content via `do_blocks()` on parsed block content
  (same approach as other dynamic theme blocks — no hand-rolled hero markup).

It does **not**:

- Output a fallback placeholder on the frontend when unmapped.
- Duplicate `query-title` / `term-description` (those stay in the template unless
  editors later remove them in favour of banner-only chrome).

### Block editor behaviour

| Context | Behaviour |
|---|---|
| **Frontend** `product_cat` archive | Resolve + render banner or output nothing. |
| **Site Editor / block editor on template** | `ServerSideRender` when logged in; placeholder when not on a resolvable archive context (same philosophy as `product-cat-child-swiper`). |
| **Inserter** | Available for template placement (`archive-product.html`); not intended for arbitrary page embed without future extension. |

No block attributes required for v1 (fully context-driven). Optional later:
override banner ID for manual pages.

### Template placement

Add **`chairforce/product-cat-banner`** to `templates/archive-product.html` **above**
the existing white header group (title + child swiper):

```html
<!-- wp:chairforce/product-cat-banner {"align":"full"} /-->

<!-- existing header group: query-title, term-description, product-cat-child-swiper -->
```

Editors can reorder, wrap, or remove in the Site Editor. Block handles empty state
(no wrapper noise when unmapped).

---

## ACF schema

### Field group: `group_banner_configurations`

**Location:** `options_page == chairforce-banner-configurations`

| Field key | Name | Type |
|---|---|---|
| `field_banner_category_rows` | `banner_category_rows` | `repeater` |
| ↳ `field_banner_category_term` | `product_category` | `taxonomy` |
| ↳ `field_banner_post` | `banner` | `post_object` | Mirror `field_faq_category_posts` (`type`, `ui`, `return_format`, `post_type` filter); `multiple`: 0 |
| ↳ `field_banner_display_on_children` | `display_on_child_categories` | `true_false` |

**File:** `acf-json/group_banner_configurations.json`

**Repeater layout:** block layout — category (25%) | banner (50%) | checkbox (25%),
mirroring FAQ category row proportions.

---

## Implementation files

| Piece | Location |
|---|---|
| CPT registration | `includes/register-cpt.php` |
| Options sub-page + ACF filters | `lib/class-acf.php` |
| ACF JSON | `acf-json/group_banner_configurations.json` |
| Resolution + render helpers | `includes/product-cat-banner-functions.php` |
| Dynamic block (source) | `src-jsx-blocks/product-cat-banner/` |
| Block build output | `build-jsx-blocks/product-cat-banner/` |
| Template | `templates/archive-product.html` |
| Styles (if needed) | `src/sass/components/_product-cat-banner.scss` or block `style.scss` |

**Require** `product-cat-banner-functions.php` from `includes/init.php`.

**PHP API (canonical):**

```php
chairforce_get_banner_configuration_rows_by_term(): array
chairforce_resolve_product_cat_banner_id( int $term_id ): int
chairforce_get_queried_product_cat_banner_id(): int
chairforce_get_banner_post_markup( int $banner_id ): string
```

- `chairforce_get_banner_post_markup()` — load `chairforce_banner` post, bail if not
  `publish`, run content through `do_blocks()` (after `apply_filters( 'the_content', … )`
  only if needed for shortcodes/embeds; prefer raw block content + `do_blocks` like other
  theme patterns).

**Queried term helper:** reuse `chairforce_get_queried_product_cat_term()` from
`includes/product-cat-swiper-functions.php`.

---

## Implementation chunks (build order)

### Chunk 1 — CPT + admin

- Register `chairforce_banner` in `register-cpt.php`.
- Verify block editor works (title + content).
- Smoke-test: create a banner with heading + cover/button blocks.

### Chunk 2 — Banner Configurations ACF

- Add options sub-page under Banners menu.
- Export `group_banner_configurations.json`.
- Port FAQ Configurations ACF hooks (hierarchical select, duplicate term validation) for
  `field_banner_category_term`.
- Manual QA: map Chairs → banner, toggle inherit, save.

### Chunk 3 — Helpers + block

- `includes/product-cat-banner-functions.php` — row index, resolver, markup helper.
- `src-jsx-blocks/product-cat-banner/` — dynamic block, `render.php`, minimal `edit.js`
  (Inspector note + `ServerSideRender` or placeholder).
- `npm run build:blocks`.

### Chunk 4 — Template + verification

- Wire block into `archive-product.html`.
- Browser QA matrix (below).
- Update `context/plans/3l-product-category-archive-chrome-plan.md` + `PROGRESS.md` on
  completion.

---

## Verification (draft)

- [ ] Banner CPT: create/edit/publish in block editor
- [ ] Banner Configurations: map term → banner; duplicate term rejected on save
- [ ] `/product-category/chairs/` — exact match shows banner content
- [ ] Child archive with inherit **on**, no child row — shows parent banner
- [ ] Child archive with own row — shows child banner (overrides inherit)
- [ ] Child archive with inherit **off** on parent, no child row — no banner output
- [ ] Unmapped category — block renders nothing (no empty wrapper)
- [ ] Banner reuse — same banner post on two category rows renders identically
- [ ] Site Editor: template shows block; preview reasonable off-archive
- [ ] Draft/trashed banner — resolver treats as no banner (no broken output)

---

## Out of scope (v1)

- Banners on `venues`, `sales-by-location`, or non-`product_cat` archives (**3n**).
- Term meta assignment UI on WooCommerce category edit screen.
- Banner scheduling / A/B / geo targeting.
- Automatic banner from WC category thumbnail + description (editors use blocks).
- Public single URL or archive for `chairforce_banner`.
- Chunk 4 taxonomy thumbs grid (dropped).

---

## Open items (resolve during build)

1. **Banner wrapper Sass** — only if CPT block content needs archive-specific spacing;
   prefer letting editors control layout inside the banner post.
2. **Featured image** — include `thumbnail` support on CPT for optional cover blocks vs
   dedicated image field (default: editor blocks only).
3. **Editor off-archive preview** — static placeholder vs mock banner pick in inspector
   (default: placeholder + help text, match child-swiper pattern).
