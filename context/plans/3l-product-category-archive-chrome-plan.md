# 3l — Product Category Archive Chrome — Implementation Plan

## Status: 🔄 In progress — Chunks 1–2 ✅ (Aug 2026)

Phase **3l** in `context/PROGRESS.md` covers three archive chrome pieces.
This plan tracks them in order.

| Chunk | Scope | Status |
|---|---|---|
| 1 | Reusable category swiper + archive child-category block | ✅ Done (`7be0750`) |
| 2 | Manual category-picker block (reuses swiper) | ✅ Done (`abd6076`) |
| 3 | Taxonomy banner (Banner CPT + config + archive block) | 📋 Plan locked — see `3l-product-category-banner-plan.md` |
| 4 | Taxonomy thumbs grid | ❌ Dropped — covered by swiper (Chunks 1–2) |

**Design reference (Chunk 1):**

- Desktop: `context/figma/components/Product Category Title Area.png`
- Mobile: `context/figma/components/Product Category Title Area - Mobile.png`

**Example URL:** `/product-category/chairs/` — term **Chairs** (`product_cat`
ID **183**) shows a swiper of its **immediate child** terms only.

**Related:**

- Accordion reuse pattern: `includes/accordion-functions.php`,
  `src/js/accordion.js`, `src/sass/components/_accordion.scss`
- Current archive title area: `templates/archive-product.html` (`query-title`
  + `term-description` in a white header band above `shop-archive-shell`)
- Swiper 11 in theme build (`package.json`); category swiper is the shared
  term-card carousel layer (distinct from Quick View product gallery).

---

## Shipped — Chunks 1–2

### Shared category swiper layer

| Piece | Location |
|---|---|
| PHP markup + flex preview | `includes/category-swiper-functions.php` |
| Term mappers + sort | `includes/product-cat-swiper-functions.php` |
| Frontend init | `src/js/category-swiper.js` |
| Styles | `src/sass/components/_category-swiper.scss` |

- Swiper config: `slidesPerView: 'auto'`, `spaceBetween: 12`, slide width
  `6.25rem` (WP `thumbnail` size).
- Images via WooCommerce `thumbnail_id` term meta + theme placeholder.
- `watchOverflow: true` — arrows/scrollbar hidden when locked (Swiper CSS).
- Editor preview: `chairforce_get_category_swiper_flex_list_html()` (static
  flex list, no Swiper JS) for `ServerSideRender` with `previewMode: true`.

### Blocks

| Block | Item source | Commit |
|---|---|---|
| **`chairforce/product-cat-child-swiper`** | Queried `product_cat` → immediate children (`menu_order`) | `7be0750` |
| **`chairforce/product-cat-swiper`** | Editor-selected terms; `FormTokenField` picker; orderby/order | `abd6076` |

Both blocks expose display toggles: `showArrowsDesktop`, `showArrowsMobile`,
`showProgressBar`, `showLabels`.

Manual block **Order by**: `manual` (token order), `menu_order`, `name`,
`slug`, `term_id`, `count`, `description`, `term_group`. **Order**: `asc` |
`desc` (default `asc`; shown when order by ≠ manual).

### Template

`templates/archive-product.html` — two-column header: title/description |
`product-cat-child-swiper` block. Styling targets `.cf-category-swiper` /
block wrapper classes (no separate BEM header wrappers).

---

## Goal (Chunk 1) — reference

On **`product_cat` archive pages**, replace/enhance the plain title row with a
**block-based header** that includes a horizontal **child-category swiper**
beside the archive title and term description (Figma layout).

---

## Locked decisions — Chunk 1 *what*

### Data rule (archive block)

- Block is **context-aware** on `product_cat` taxonomy archives.
- Resolve **immediate child terms only** of the **currently queried term**.
- Each slide = one child term: **image + label**, linking to that term's
  archive URL.
- **Do not** include grandchildren, siblings, or ancestors.
- **Leaf categories** (no children): parent block outputs nothing — swiper
  is never invoked with an empty list.

### Term imagery & labels

- Each item needs at least: **label** (term name), **link** (term archive
  URL), **image** (attachment ID preferred for `wp_get_attachment_image()`).
- Source for images: WooCommerce **`thumbnail_id`** term meta on
  `product_cat` (fallback: theme placeholder — see open items).

### Layout (Figma)

**Desktop**

- Left: archive **title** + **description** (existing `query-title` /
  `term-description` blocks may remain alongside or be composed in the same
  header group — template composition TBD).
- Right: horizontal swiper of child category cards.
- **Prev/next arrows** on the right (stacked in Figma).
- **Progress / scrollbar** along the bottom of the swiper track.

**Mobile**

- Title + description stacked above the swiper row.
- Figma shows swipe/scroll without arrows; **arrows on mobile are optional**
  and controlled by swiper display options (see below).

### Block output scope

Chunk 1 archive block outputs the **swiper region** (and any minimal wrapper
required for layout). It does **not** necessarily own the full marketing
banner (Chunk 3) or unrelated shop shell content.

---

## Locked decisions — reusable swiper architecture

Same layering as the **accordion** refactor (3p):

| Layer | Responsibility |
|---|---|
| **Shared swiper** (CSS + JS + PHP markup helper) | Presentation only — renders slides from a **config array** + **display options**. Unaware of taxonomy, blocks, or archives. |
| **Parent blocks** | Resolve data → build config array → call shared helper. |

### Swiper input — items config array

Parent blocks map terms (or future manual selections) into a normalized
array. Swiper consumes **only** this shape:

```php
[
  [
    'id'       => 997,              // term ID (optional but useful for keys/a11y)
    'title'    => 'Cafe Chairs',    // accessible name
    'label'    => 'Cafe Chairs',    // visible slide label (may differ from title)
    'url'      => 'https://…/product-category/cafe-chairs/',
    'image_id' => 12345,            // attachment ID (preferred over raw URL)
  ],
  // …
]
```

Swiper must **not** call `get_term()`, walk parent/child, or read ACF/WC
meta directly.

### Swiper input — display options

Configurable flags passed with the config (block attributes or `$args`):

| Option | Purpose |
|---|---|
| `showArrowsDesktop` | Prev/next controls on desktop |
| `showArrowsMobile` | Prev/next controls on mobile (Figma omits; may still enable) |
| `showProgressBar` | Bottom track / scrollbar indicator (Figma) |
| `showLabels` | Term labels under slide images |

Archive child-category block and manual picker block may expose different
defaults; swiper renders whatever it receives.

### Consumers (two blocks, one engine) — ✅ both shipped

| Block | Item source | Status |
|---|---|---|
| **`chairforce/product-cat-child-swiper`** | Current queried `product_cat` → `get_terms( parent => $term_id )` | ✅ Chunk 1 |
| **`chairforce/product-cat-swiper`** | Editor-selected `product_cat` terms | ✅ Chunk 2 |

Both blocks:

1. Build the items config array.
2. Pass display options (from block attributes).
3. Echo shared markup via `chairforce_get_category_swiper_html()`.

### Parallels with accordion

| Accordion | Category swiper |
|---|---|
| `chairforce_get_accordion_html()` | `chairforce_get_category_swiper_html()` |
| `.cf-accordion` + `accordion.js` | `.cf-category-swiper` + `category-swiper.js` |
| `chairforce/product-faqs` resolves FAQ IDs | Archive block resolves child term IDs |
| Manual picker passes selected term IDs | `chairforce/product-cat-swiper` |

---

## Locked decisions — Chunk 1 *how* — ✅ implemented

### Theme-owned Swiper (shared category swiper layer)

Swiper 11 via npm/build pipeline — separate reusable component for term-card
carousels (not product slides, not tied to any single block).

### PHP API (canonical)

```php
chairforce_get_category_swiper_items_from_term_ids( array $term_ids ): array
chairforce_get_category_swiper_html( array $items, array $args = [] ): string
chairforce_get_category_swiper_flex_list_html( array $items, array $args = [] ): string // editor preview
chairforce_sort_product_cat_swiper_term_ids( array $term_ids, string $order_by, string $order ): array
```

- **`$items`** — normalized config array (see above).
- **`$args`** — display options + optional `instance_id` for multiple
  swipers on one page.

Markup wrapper: `[data-cf-category-swiper]` root, BEM `.cf-category-swiper`.

### JS init

- Idempotent init per root.
- Swiper modules: **Navigation** (arrows), **Scrollbar** for Figma bottom track.
- Display options from `data-*` attributes on root.
- `chairforce:content-updated` listener for archive shell reload.

---

## Out of scope (Chunks 1–2)

- Full **taxonomy banner** with hero background / CTA (**Chunk 3**).
- Static **thumbnail grid** without carousel (**Chunk 4**).
- **`venues` / `sales-by-location`** archives (**3n**).
- Shop archive shell, filters, Load More, product grid (**3f**, **3i**).

---

## Next — Chunk 3 (taxonomy banner)

**Plan:** `context/plans/3l-product-category-banner-plan.md`

- `chairforce_banner` CPT (Gutenberg content)
- Banner Configurations options sub-page (category → banner repeater +
  `display_on_child_categories` in v1)
- `chairforce/product-cat-banner` block on `archive-product.html`

## Chunk 4 — dropped

Category swiper blocks cover the thumbs-grid use case; no separate grid chunk.

---

## Open items (remaining)

1. **Missing thumbnail** — theme placeholder in use; confirm client preference
   (options vs first product vs static asset).
2. **Editor preview (child block)** — static placeholders in edit.js; SSR not
   used (archive context unavailable in editor).
3. **Accessibility pass** — arrow `aria-label`s, focus order, keyboard nav.
4. **Chunk 3 banner fields** — ACF on term vs reuse WC thumbnail + description.

---

## Verification (Chunks 1–2)

- [x] `/product-category/chairs/` shows swiper of immediate children only
- [x] Child term links resolve to correct archives
- [x] Desktop: arrows + progress bar when options enabled
- [x] Mobile: stacked title area + horizontal swiper
- [x] Leaf category archive: no swiper / no empty chrome
- [x] Manual picker block reuses same helper + editor flex preview
- [ ] Swiper re-inits after shop archive shell reload (if applicable)
- [ ] Full a11y pass
