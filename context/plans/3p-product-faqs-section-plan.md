# 3p — Product FAQs Section — Implementation Plan

## Status: ⏳ Not started

Plan locked from product-page review (6 Aug 2026).

| Chunk | Scope | Status |
|---|---|---|
| 1 | ACF: FAQ Configurations options sub-page + product field | ⏳ |
| 2 | FAQ resolution helpers (`includes/product-faqs-functions.php`) | ⏳ |
| 3 | `chairforce/product-faqs` dynamic block (accordion + empty state only) | ⏳ |
| 4 | Sass (Figma accordion) + true-accordion JS + template wrapper | ⏳ |
| 5 | Browser QA + docs (`PROGRESS.md`) | ⏳ |

## Goal

Show **context-aware FAQs** on single product pages by merging three
configuration tiers into one accordion list, driven by the existing
`chairforce_faq` CPT (imported via BatchPress from Elementor page #1510).

Deliver:

1. **FAQ Configurations** ACF options sub-page under **FAQs** admin menu —
   Global FAQ selection + Product Category FAQ repeater.
2. **Product-specific FAQ field** on the product edit screen (same meta box as
   Dimensions / Care / Additional Information).
3. **`chairforce/product-faqs`** dynamic JSX block — renders **accordion items
   only** (+ “No FAQs Found” when empty). **No section heading, no outer
   marketing wrapper** — editors add those in the Site Editor.
4. **Template wrapper group** in `single-product.html` (spacing/layout only,
   same pattern as “Related Wrapper”) containing the block.

**Design reference:**

- Figma: `context/figma/components/Single Product FAQ Section.png`
- Accordion UX: true accordion (one panel open at a time); **first item open
  by default**.

**Related shipped work:**

- BatchPress import job: `Import_Elementor_Faqs` (page #1510 → `chairforce_faq`)
- Product Reviews section pattern: `context/plans/3o-product-reviews-section-plan.md`

---

## Locked decisions

### Three configuration tiers

| Tier | Storage | Admin UI |
|---|---|---|
| **1. Global** | ACF options | FAQ Configurations → “Global FAQs” |
| **2. Product category** | ACF options repeater | FAQ Configurations → “Product Category FAQs” |
| **3. Product-specific** | ACF on `product` | Product meta box → after “Additional Information” |

All FAQ pickers: **post object / relationship** fields targeting
`post_type = chairforce_faq`, return format **Post ID**, multiple selection
allowed, order preserved as selected in admin.

### Merge order (most specific first)

When resolving FAQs for a product, collect post IDs in this order:

1. **Product-specific** — field on the current product (selection order)
2. **Category** — for each `product_cat` term assigned to the product, walk
   **child → parent** (most specific term first, then ancestors). For each
   term in that walk, if a repeater row exists for that term ID, append its
   FAQ IDs **in the order selected in that row**.
3. **Global** — options page selection (order preserved)

**Multiple product categories:** For each assigned category, run the
child→parent walk independently; append category FAQs in term-ID walk order
(most specific terms across all branches before their parents).

### Deduplication

**Yes.** After merge, deduplicate by post ID while **keeping first
occurrence** (most-specific tier wins).

### Empty state

When zero FAQ IDs resolve: block still renders and outputs a single message:

> **No FAQs Found**

(No accordion markup.)

### Block output scope (strict)

The block outputs **only**:

- Accordion list (`<details>`/summary or equivalent accessible markup)
- Empty-state paragraph when no FAQs

It does **not** output:

- Section heading (“Frequently Asked Questions” — editor adds via `core/heading`)
- Background / full-bleed wrapper (editor or template group handles that)
- Category grouping headers inside the accordion (flat merged list)

### Accordion behaviour

| Rule | Value |
|---|---|
| Type | **True accordion** — opening one item closes others |
| Default | **First item open** on load |
| Content | Question = FAQ `post_title`; Answer = FAQ `post_content` (HTML) |
| Markup | Prefer **WordPress block-serialized inner markup** where practical (same philosophy as `product-reviews-functions.php`) |

### Template wrapper

Add a **`Product FAQs Wrapper`** group to `templates/single-product.html`
between **Product Reviews** and **Related Wrapper**, matching Related Wrapper
spacing:

```html
<!-- wp:group {"metadata":{"name":"Product FAQs Wrapper"},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|section-m","bottom":"var:preset|spacing|section-m"}}},"layout":{"type":"constrained"}} -->
  <!-- wp:chairforce/product-faqs /-->
<!-- /wp:group -->
```

Editors add heading / background / align-full styling **inside or around** this
group in the Site Editor (Figma shows centered “Frequently Asked Questions”
above the accordion — that is **not** part of the block).

---

## ACF schema

### Options sub-page registration

Register via `acf_add_options_sub_page()` in `lib/class-acf.php`:

| Property | Value |
|---|---|
| `page_title` | FAQ Configurations |
| `menu_title` | FAQ Configurations |
| `menu_slug` | `chairforce-faq-configurations` |
| `parent_slug` | `edit.php?post_type=chairforce_faq` |
| `capability` | `manage_options` |

### Field group: `group_faq_configurations`

**Location:** `options_page == chairforce-faq-configurations`

| Field key | Name | Type | Notes |
|---|---|---|---|
| `field_faq_global` | `faq_global` | `relationship` or `post_object` | `post_type`: `chairforce_faq`, `multiple`: 1, return: ID |
| `field_faq_category_rows` | `faq_category_rows` | `repeater` | See sub-fields |
| ↳ `field_faq_category_term` | `product_category` | `taxonomy` | `taxonomy`: `product_cat`, `field_type`: `select`, return: term ID, **allow only one row per term** (validate on save) |
| ↳ `field_faq_category_posts` | `faqs` | `relationship` | `post_type`: `chairforce_faq`, `multiple`: 1, return: ID |

**Repeater layout:** Row layout with two columns (ACF `wrapper` width 50/50) —
category dropdown left, FAQ picker right.

**UI copy:**

- Tab/section 1 label: **Global FAQs**
- Tab/section 2 label: **Product Category FAQs**

Use ACF **`tab`** fields or **`message`** + **`group`** fields to separate the
two sections visually.

### Field group update: `group_product_additional_information`

Add after `additional_information`:

| Field key | Name | Type | Notes |
|---|---|---|---|
| `field_product_faqs` | `product_faqs` | `relationship` | `post_type`: `chairforce_faq`, `multiple`: 1, return: ID, label: **Product FAQs** |

Same meta box (`Product Additional Information`) — no new location rule needed.

### Product category dropdown (hierarchical)

Follow the **existing Chairforce ACF convention** from other themes’
`class-acf.php`: filter the taxonomy query so child categories appear indented
in the select.

**Implementation (this theme):**

- Add filter on `acf/fields/taxonomy/query` (scoped by field name/key
  `product_category` / `field_faq_category_term`).
- Query all `product_cat` terms ordered hierarchically (`orderby` =>
  `name`, walk tree or use `get_terms` with `parent` recursion).
- Prefix option labels with em-dash indentation per depth
  (`— Child`, `—— Grandchild`) — match sibling-theme pattern; do not rename
  terms.

**Note:** Could not locate a sibling `class-acf.php` in this workspace; implement
using the standard hierarchical `acf/fields/taxonomy/query` + indented label
pattern used across Chairforce themes.

---

## FAQ resolution algorithm

**File:** `includes/product-faqs-functions.php`

```php
/**
 * Resolve merged FAQ post IDs for a product (deduplicated, order preserved).
 *
 * @return int[]
 */
function chairforce_get_product_faq_ids( int $product_id ): array;
```

### Pseudocode

```
$ordered = []

// 1. Product-specific
$ordered = array_merge( $ordered, get_field('product_faqs', $product_id) ?: [] )

// 2. Category (child → parent per assigned term)
$term_ids_ordered = chairforce_get_product_cat_term_ids_child_to_parent( $product_id )
$rows = get_field('faq_category_rows', 'option') ?: []
$rows_by_term = index repeater rows by product_category term ID

foreach ( $term_ids_ordered as $term_id ) {
  if ( isset( $rows_by_term[ $term_id ] ) ) {
    $ordered = array_merge( $ordered, $rows_by_term[ $term_id ]['faqs'] ?: [] )
  }
}

// 3. Global
$ordered = array_merge( $ordered, get_field('faq_global', 'option') ?: [] )

// Deduplicate preserving first occurrence
return chairforce_array_unique_ints_preserve_order( $ordered )
```

### Helper: child → parent term walk

For each `product_cat` term assigned to the product:

1. Start at the term.
2. Append term ID to list.
3. Walk to `parent` until `0`.
4. When product has multiple assigned terms, process each branch (dedupe term
   IDs in walk output so each ancestor is visited once per merge pass — first
   visit wins position: **deeper terms before their ancestors**).

Document exact ordering with examples in code comments (product in
`Chairs > Office Chairs > Mesh` gets Mesh → Office Chairs → Chairs before
global).

### Helper: load FAQ posts for render

```php
function chairforce_get_product_faq_posts( int $product_id ): WP_Post[];
```

- Map IDs → `get_post()`; skip missing/trashed/wrong post type.
- Preserve resolved ID order.

---

## Block: `chairforce/product-faqs`

Mirror `chairforce/product-reviews` structure.

### Files

```
src-jsx-blocks/product-faqs/
├── block.json
├── index.js
├── edit.js          # Placeholder accordion preview
├── save.js          # null (dynamic)
├── render.php
├── view.js          # True accordion behaviour
├── style.scss
└── editor.scss
```

### block.json (sketch)

| Property | Value |
|---|---|
| `name` | `chairforce/product-faqs` |
| `category` | `woocommerce` |
| `usesContext` | `[ "postId" ]` |
| `supports.align` | `[ "wide", "full" ]` |
| `supports.html` | `false` |
| `render` | `file:./render.php` |
| `viewScript` | `file:./view.js` |

**No block attributes required for v1** — FAQs are fully data-driven from ACF.

### render.php

1. Resolve `$product_id` from block context / `get_the_ID()`.
2. `$faqs = chairforce_get_product_faq_posts( $product_id )`.
3. If empty → output empty-state markup only.
4. Else → build accordion markup (serialized blocks or minimal HTML wrapper).

**Suggested DOM (accessible true accordion):**

```html
<div class="cf-product-faqs" data-cf-product-faqs>
  <div class="cf-product-faqs__list" role="presentation">
    <!-- per FAQ -->
    <div class="cf-product-faqs__item" data-cf-product-faq-item>
      <button type="button" class="cf-product-faqs__trigger" aria-expanded="true|false" aria-controls="...">
        {question}
        <span class="cf-product-faqs__icon" aria-hidden="true"></span>
      </button>
      <div id="..." class="cf-product-faqs__panel" role="region" hidden|visible>
        {answer HTML}
      </div>
    </div>
  </div>
</div>
```

First item: `aria-expanded="true"`, panel visible. Others collapsed.

Prefer generating inner FAQ card markup via **`do_blocks()`** + helper
(analogous to `chairforce_get_product_review_card_blocks_markup()`) if it
keeps editor “Convert to Blocks” viable for FAQ post content later.

### view.js

- Module init on `.cf-product-faqs[data-cf-product-faqs]`.
- Click trigger → expand target, collapse siblings.
- Keyboard: Enter/Space on trigger; optional ArrowUp/Down between triggers.
- Import from `src/public.js` on single product pages (same as reviews toggle).

### Editor

- Placeholder showing 2–3 fake accordion rows (like `product-reviews/edit.js`).
- Note: “FAQs render from FAQ Configurations + product settings on the
  frontend.”
- Hide from inserter via `Editor_Curation` (template-placed block).

---

## Figma styling targets

**File:** `src/sass/woocommerce/_product-faqs.scss`

| Element | Figma |
|---|---|
| Section background | Light gray — applied by **editor wrapper**, not block |
| Accordion row | White card, subtle border/shadow, vertical gap between items |
| Question | Bold, dark, left-aligned; chevron right |
| Answer | Regular weight, neutral text, padding below question |
| Icon | Chevron up/down (Lucide mixin or CSS) — `@include cf-icon-after('chevron-down')` rotated when open |

Import in `src/sass/woocommerce/_index.scss`.

---

## Architecture

```
FAQ Configurations (options)
├── faq_global[]                    → chairforce_faq IDs
└── faq_category_rows[]
    ├── product_category (term ID)
    └── faqs[]                      → chairforce_faq IDs

Product (ACF)
└── product_faqs[]                  → chairforce_faq IDs

includes/product-faqs-functions.php
├── chairforce_get_product_cat_term_ids_child_to_parent()
├── chairforce_get_product_faq_ids()
├── chairforce_get_product_faq_posts()
└── chairforce_get_product_faq_accordion_markup()  (optional)

src-jsx-blocks/product-faqs/
├── render.php                      → accordion | empty state
└── view.js                         → true accordion

templates/single-product.html
├── product-reviews
├── Product FAQs Wrapper (group)
│   └── chairforce/product-faqs
└── Related Wrapper
```

---

## Implementation chunks

### Chunk 1 — ACF admin

**Files:**

- `lib/class-acf.php` — register FAQ Configurations sub-page + taxonomy query filter
- `acf-json/group_faq_configurations.json` — new field group
- `acf-json/group_product_additional_information.json` — add `product_faqs`

**Verify:**

- `wp-admin/edit.php?post_type=chairforce_faq` → **FAQ Configurations** submenu
- Global + repeater save/load
- Product edit → Product FAQs field after Additional Information
- Category dropdown shows indented child categories

### Chunk 2 — Resolution helpers

**Files:**

- `includes/product-faqs-functions.php`
- `includes/init.php` — require

**Verify (`ddev wp eval`):**

- Product with only global FAQs → correct IDs
- Product with category + global → category IDs before global (after dedupe order rules)
- Product-specific overrides ordering (most specific first)
- Duplicate ID across tiers appears once

### Chunk 3 — Block

**Files:**

- `src-jsx-blocks/product-faqs/*`
- `lib/class-editor-curation.php` — curate inserter

**Verify:**

- Block renders on single product template
- Empty product → “No FAQs Found”
- Populated → N accordion items, first open

### Chunk 4 — Frontend JS + Sass + template

**Files:**

- `src/js/product-faqs-accordion.js` (or colocate in block `view.js`)
- `src/public.js` — import
- `src/sass/woocommerce/_product-faqs.scss`
- `templates/single-product.html` — Product FAQs Wrapper group
- `npm run build:blocks && npm run build:assets`

**Verify:**

- True accordion: opening item 2 closes item 1
- Figma spacing/typography at wide breakpoint
- Mobile: full-width rows, readable tap targets

### Chunk 5 — QA + docs

**Files:**

- `context/PROGRESS.md` — add 3p row
- Manual browser checklist (below)

---

## Test plan (browser)

- [ ] FAQ Configurations saves global + category repeater rows
- [ ] Product-specific FAQs field saves on product
- [ ] Product with no configured FAQs → “No FAQs Found”
- [ ] Global-only FAQs appear on all products
- [ ] Category FAQs appear only on products in that category (and children if configured on parent term row)
- [ ] Product-specific FAQs appear first in list
- [ ] Same FAQ in global + category → shown once
- [ ] First accordion item open by default; only one open at a time
- [ ] FAQ answer HTML renders (paragraphs, links)
- [ ] Editor can add “Frequently Asked Questions” heading above block in wrapper group
- [ ] Template wrapper spacing matches Related Wrapper rhythm

---

## Open questions

None — all decisions locked 6 Aug 2026 (see chat).

---

## Related

| Doc | Purpose |
|---|---|
| `context/figma/components/Single Product FAQ Section.png` | Accordion visual target |
| `context/plans/3o-product-reviews-section-plan.md` | Dynamic product block pattern |
| `includes/product-reviews-functions.php` | Block markup helpers precedent |
| `templates/single-product.html` | Related Wrapper spacing reference |
| `jobs/class-import-elementor-faqs.php` | FAQ CPT population |
