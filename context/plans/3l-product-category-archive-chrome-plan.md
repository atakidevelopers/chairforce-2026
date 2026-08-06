# 3l — Product Category Archive Chrome — Implementation Plan

## Status: 🔄 In progress — Chunk 1 *what* + *how* locked (Aug 2026)

Phase **3l** in `context/PROGRESS.md` covers three archive chrome pieces.
This plan tracks them in order. **Chunk 1 (child category swiper)** scope,
architecture, and implementation direction are locked below.

| Chunk | Scope | Status |
|---|---|---|
| 1 | Reusable category swiper + archive child-category block | 🔄 Ready to implement |
| 2 | Manual category-picker block (reuses swiper) | ⏳ Not started |
| 3 | Taxonomy banner (hero image, title, description, optional CTA) | ⏳ Not started |
| 4 | Taxonomy thumbs grid (non-swiper sibling layout, if still needed) | ⏳ TBD vs Chunk 1 |

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
- Swiper already in theme build (`package.json` → `swiper ^11`); first use was
  Quick View gallery (`src/js/quick-view-gallery.js`). Chunk 1 introduces the
  **shared category swiper** layer for term-card carousels.

---

## Goal (Chunk 1)

On **`product_cat` archive pages**, replace/enhance the plain title row with a
**block-based header** that includes a horizontal **child-category swiper**
beside the archive title and term description (Figma layout).

Editors place the block in **`templates/archive-product.html`** (or a template
part) in the archive chrome area — same placement philosophy as other
Site-Editor-controlled sections.

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

### Planned consumers (two blocks, one engine)

| Block | Item source | Status |
|---|---|---|
| **`chairforce/product-cat-child-swiper`** (name TBD) | Current queried `product_cat` → `get_terms( parent => $term_id )` | Chunk 1 |
| **Manual category swiper block** (name TBD) | Editor-selected `product_cat` terms (any parent/child mix, order preserved) | Chunk 2 |

Both blocks:

1. Build the items config array.
2. Pass display options (from block attributes).
3. Echo shared markup via e.g. `chairforce_get_category_swiper_html( $items, $args )`.

### Parallels with accordion

| Accordion | Category swiper |
|---|---|
| `chairforce_get_accordion_html()` | `chairforce_get_category_swiper_html()` (name TBD) |
| `.cf-accordion` + `accordion.js` | `.cf-category-swiper` + JS init (TBD) |
| `chairforce/product-faqs` resolves FAQ IDs | Archive block resolves child term IDs |
| Future blocks pass arbitrary item lists | Manual picker passes selected term IDs |

---

## Locked decisions — Chunk 1 *how*

### Theme-owned Swiper (shared category swiper layer)

Use the theme's existing **Swiper 11** npm dependency and build pipeline —
same library family as Quick View gallery, but a **separate reusable
component** for term-card carousels (not product slides, not tied to any
single block).

Quick View and category swiper may share init conventions later; Chunk 1
ships the category swiper layer first.

### File layout (mirror accordion pattern)

| Piece | Location |
|---|---|
| PHP helpers + markup | `includes/category-swiper-functions.php` |
| Term → config mappers | `includes/product-cat-swiper-functions.php` (or colocated in above) |
| Frontend init | `src/js/category-swiper.js` |
| Styles | `src/sass/components/_category-swiper.scss` |
| Archive block | `src-jsx-blocks/product-cat-child-swiper/` (name TBD) |

Require helpers from `includes/init.php`. Enqueue/init via `public.js`
(same as accordion).

### PHP API (canonical)

```php
chairforce_get_category_swiper_items_from_term_ids( array $term_ids ): array
chairforce_get_category_swiper_html( array $items, array $args = [] ): string
```

- **`$items`** — normalized config array (see above).
- **`$args`** — display options + optional `instance_id` for multiple
  swipers on one page.

Markup wrapper: `[data-cf-category-swiper]` root, BEM `.cf-category-swiper`.

### JS init

- Idempotent init per root (`data-cf-category-swiper-init` or WeakMap).
- Swiper modules: **Navigation** (arrows), **Scrollbar** or **Pagination**
  for Figma bottom progress bar — pick during implementation to match design.
- Read display options from `data-*` attributes emitted by PHP (or a JSON
  blob on the root) so JS stays presentation-only.
- Listen for `chairforce:content-updated` if archive shell reload ever
  replaces header markup (same delegation pattern as other grid features).

### Swiper display options → markup/JS

Block attributes map to `$args` / `data-*`:

| Option | Default (archive block TBD) |
|---|---|
| `showArrowsDesktop` | `true` (Figma desktop) |
| `showArrowsMobile` | `false` (Figma mobile omits; block can override) |
| `showProgressBar` | `true` (Figma bottom track) |
| `showLabels` | `true` |

Shared helper hides arrows/progress/labels in DOM when flag is false.

### Chunk 1 block responsibilities

**`chairforce/product-cat-child-swiper`** (name TBD):

1. Detect queried `product_cat` term on taxonomy archives.
2. `get_terms( [ 'parent' => $term_id, 'taxonomy' => 'product_cat', … ] )`.
3. Map to config via `chairforce_get_category_swiper_items_from_term_ids()`.
4. If empty → return (no output).
5. Pass block attributes as display options → `chairforce_get_category_swiper_html()`.

Block attributes: display flags above (and any swiper-specific tuning e.g.
`slidesPerView` desktop/mobile — finalize in block.json during build).

### Template wiring

Keep **`query-title`** + **`term-description`** as sibling blocks in
`archive-product.html`; add archive child swiper in the same header group
(Figma two-column desktop / stacked mobile via CSS grid or flex on a wrapper
group — editor-controlled).

### Term helper (parent blocks only)

```php
chairforce_get_category_swiper_items_from_terms( array $terms ): array
// or from IDs:
chairforce_get_category_swiper_items_from_term_ids( array $term_ids ): array
```

Reads `thumbnail_id`, `name`, `get_term_link()` — **only here**, never in
swiper HTML/JS layer.

---

## Out of scope (Chunk 1)

- Full **taxonomy banner** with hero background / CTA (Chunk 3).
- Static **thumbnail grid** without carousel (Chunk 4 — may overlap; revisit
  after Chunk 1 ships).
- **`venues` / `sales-by-location`** archives (**3n**).
- Shop archive shell, filters, Load More, product grid (**3f**, **3i**).

---

## Open items (remaining)

1. **Missing thumbnail** — theme placeholder from options vs first product in
   category vs generic image asset in `/src/assets/images/`.
2. **Scrollbar vs pagination** — which Swiper module best matches Figma
   bottom track.
3. **Block slug** — final name (`product-cat-child-swiper` vs shorter).
4. **`slidesPerView`** — fixed count vs `auto` with slide width from Figma.
5. **Editor preview** — ServerSideRender with mock items when not on a
   category archive.
6. **Accessibility pass** — arrow `aria-label`s, focus order, keyboard nav.

---

## Verification (Chunk 1 — draft)

- [ ] `/product-category/chairs/` shows swiper of immediate children only
- [ ] Child term links resolve to correct archives
- [ ] Desktop: arrows + progress bar match Figma when options enabled
- [ ] Mobile: stacked title area + horizontal swiper; arrows optional
- [ ] Leaf category archive: no swiper / no empty chrome
- [ ] Swiper re-inits after shop archive shell reload (if applicable)
- [ ] Manual picker block (Chunk 2) can reuse same helper without code fork
