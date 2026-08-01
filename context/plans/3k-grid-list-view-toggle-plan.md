# 3k — Shop archive grid/list view toggle — Implementation Plan

## Status: 🔄 In progress (1 Aug 2026)

Implementation landed locally; browser QA pending.

| Chunk | Scope | Status |
|---|---|---|
| 1 | **`chairforce/product-view-switcher` JSX block** + template placement + filters-band layout | ✅ |
| 2 | Frontend JS (`localStorage`, class toggle, `content-updated` re-apply) | ✅ |
| 3 | Sass — toolbar active states + list layout on shared product card | ✅ |
| 4 | Template wiring + archive enqueue | ✅ |
| 5 | QA matrix (toggle × filters × Load More × mobile) | ⏳ |

## Goal

Ship a **theme-owned grid/list toggle** on shop + WC product taxonomy archives
(Product Collection `inherit: true`), matching the Figma filter bar (icons beside
Sort):

- **Grid** — current multi-column Product Collection layout (unchanged block attrs).
- **List** — single-column rows with horizontal card layout (image + details).
- **Instant toggle** — no network request, no URL query param, no shell reload.
- **Persistence** — `localStorage` (per browser); re-applied after filter shell
  swap and Load More append.

**No WooCommerce core switcher block exists** — Product Collection layout
(grid/stack) is editor-only. This phase implements the Figma toolbar control
without replacing `woocommerce/product-collection` or forking Load More card
rendering.

**Source docs (read before implementing):**

- `context/notes/grid-list-switcher-findings.md` — full investigation + rejected approaches.
- `context/notes/product-filters-findings.md` — Figma toolbar spec (§3.1).
- `context/plans/3f-product-filters-plan.md` — filters band / shell architecture.
- `context/plans/3i-load-more-plan.md` — append contract (unchanged).
- `context/implementation/product-grid.md` — shared `product-card.html`.
- `context/existing-functionality/17-load-more-and-event-delegation.md` —
  `chairforce:content-updated` convention.
- `.cursor/rules/16-icon-system.mdc` — Lucide icons for toggle buttons.
- `.cursor/skills/chairforce-woocommerce/` — archive class patterns.

---

## Locked decisions

### Architecture

| Decision | Value |
|---|---|
| **Transport** | Client-side class toggle only — **no REST**, **no shell reload** |
| **Persistence** | **`localStorage`** key `cf_products_view` (`grid` \| `list`) |
| **URL query param** | ❌ Rejected — do not use Woodmart `?shop_view=` or `?cf_view=` |
| **Product Collection block** | ❌ Do not change `displayLayout` at runtime or in DB per toggle |
| **Card markup** | **One** `parts/product-card.html` for grid + list + Load More |
| **Scope** | **Shop archive shell only** — not related/upsells collections |
| **Default view** | **Grid** (matches editor template + Figma selected state) |
| **Delivery** | **`chairforce/product-view-switcher` JSX block** — rearrangeable in Site Editor, reusable on other templates; **not** a hardcoded PHP partial in the shell |

### v1 content scope

| Decision | Value |
|---|---|
| **List layout** | **Layout-only** — CSS reshapes existing card blocks (image left, details right) |
| **Woodmart list extras** | ❌ Deferred — excerpt, SKU, rating, categories not in v1 unless PM promotes |
| **Grid column density** | ❌ Out of scope — no Woodmart-style `per_row` 3/4/5 selector |
| **Theme option for default** | ❌ Not in v1 — hardcode grid default; add ACF later if needed |

### Integration with 3f / 3i

| Event | Behaviour |
|---|---|
| User toggles view | Class on ancestor only; **no** `pushState`, **no** filter REST |
| Filter / sort / clear (shell swap) | `product-filters.js` replaces shell → switcher JS re-reads `localStorage` and re-applies class |
| Load More append | Appended `<li>` inherit list class from parent wrapper — **no PHP change** |
| `popstate` (browser back) | Same as shell swap — re-apply after content update |
| Related / upsells | Unaffected — toggle scoped to `.cf-shop-archive-shell` |

---

## Rejected approaches

| Approach | Verdict |
|---|---|
| WooCommerce core block for storefront toggle | ❌ Does not exist |
| `?shop_view=list` + PJAX (Woodmart) | ❌ Pollutes filter URLs; reload for presentation-only change |
| Server `displayLayout.type: stack` via REST/shell | ❌ Heavy; fights Load More context; stack ≠ Figma horizontal list |
| Second template `product-card-list.html` | ❌ Duplicates Load More renderer — defer unless v1 CSS insufficient |
| Third-party Grid/List View plugin | ❌ Classic-loop only; incompatible with Product Collection |
| WC Interactivity store mutation | ❌ Experimental; undocumented for layout switching |

Full rationale: `context/notes/grid-list-switcher-findings.md` §6.

---

## Architecture

```text
┌─────────────────────────────────────────────────────────────────┐
│ parts/shop-archive-shell.html — filters band                    │
├─────────────────────────────────────────────────────────────────┤
│ filter bar + chips          │  catalog-sorting  │  view block  │
└─────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
              <!-- wp:chairforce/product-view-switcher /-->
              (dynamic JSX block — render.php → toggle markup)

User clicks "list"
         │
         ▼
product-view-switcher.js
  localStorage.setItem('cf_products_view', 'list')
  .cf-shop-archive-main.classList.add('cf-products-view-list')
         │
         ▼
Sass: single-column template + horizontal card grid on .wc-block-product

Filter/sort shell swap OR Load More append
         │
         ▼
chairforce:content-updated → re-apply view from localStorage
```

**Class target (locked):** apply `cf-products-view-list` on
`.cf-shop-archive-main` (contains results count + product collection). Keeps
toggle scope clear and avoids touching unrelated blocks.

**Storage key:** `cf_products_view` — values `grid` | `list`; omit key = grid.

---

## Chunk breakdown

### Chunk 1 — JSX block + template placement

**Block:** `chairforce/product-view-switcher` in `src-jsx-blocks/product-view-switcher/`

Follow the same pattern as `chairforce/product-filters` — dynamic block, editor
placeholder, PHP storefront render. **Do not** bury toggle markup only inside
`partials/` or `get_template_part()` calls from the shell; the **block** is the
composable unit editors can drag, remove, or reuse.

**Required files:**

| File | Purpose |
|---|---|
| `block.json` | Metadata; **`inserter: true`**, **`reusable: true`**, **`multiple: true`** — **do not** add to `Editor_Curation` inserter-deny list |
| `index.js` | `registerBlockType` |
| `edit.js` | `EditorPlaceholderNotice` — “Grid/list view toggle (storefront)” |
| `save.js` | `return null` (dynamic) |
| `render.php` | Storefront markup when `chairforce_is_product_filter_archive()` (or broader product-archive guard) |

**`block.json` sketch:**

```json
{
  "name": "chairforce/product-view-switcher",
  "title": "Product View Switcher",
  "category": "theme",
  "description": "Grid and list toggle for product archive toolbars.",
  "keywords": [ "shop", "grid", "list", "view", "woocommerce" ],
  "supports": {
    "html": false,
    "inserter": true,
    "reusable": true,
    "multiple": true
  },
  "render": "file:./render.php"
}
```

**`render.php`:** output toggle via `chairforce_get_buttons_markup()` (same as
filter bar). Wrapper root class: `.cf-product-view-switcher` + block wrapper attrs
from `get_block_wrapper_attributes()`.

**Template wiring** — `parts/shop-archive-shell.html`:

```html
<!-- wp:woocommerce/catalog-sorting /-->

<!-- wp:chairforce/product-view-switcher /-->
```

Editors can move the block relative to sort / filter chrome without code changes.
Default placement is beside sort; Sass grid areas target the block wrapper class.

**Layout Sass** — `src/sass/woocommerce/_shop-archive-layout.scss`:

```scss
grid-template-areas:
  "bar sort view"
  "chips sort view";

.cf-shop-archive-filters-band .cf-product-view-switcher,
.cf-shop-archive-filters-band .wp-block-chairforce-product-view-switcher {
  grid-area: view;
}
```

Mobile: view block stays on row with sort (Figma §3.2).

**Shell AJAX:** filter shell reload re-renders the block via `render.php` — no
separate partial to keep in sync.

**Reuse:** block may be inserted on other templates (e.g. a custom category
layout). JS must scope list-class application to the **nearest**
`.cf-shop-archive-main` (or ancestor Product Collection), not assume a single
global toggle per page.

---

### Chunk 2 — Frontend JS

**File:** `src/js/product-view-switcher.js`

**Import from:** `src/public.js` (same guard as filters — only on product filter
archives, or broader: any page with `.cf-shop-archive-shell`).

**API:**

```js
const STORAGE_KEY = 'cf_products_view';
const ROOT = '.cf-shop-archive-shell';
const MAIN = '.cf-shop-archive-main';
const LIST_CLASS = 'cf-products-view-list';
const BUTTON = '[data-cf-products-view]';

function getStoredView() { … }       // 'grid' | 'list', default 'grid'
function applyView(view) { … }       // class + aria-pressed on buttons
function initViewSwitcher() { … }    // read storage, apply, delegate clicks
```

**Delegated click:** use `delegateDocument` from `shared/delegated-events.js`
(same pattern as `load-more.js`, `product-filters.js`).

**Re-apply hook:**

```js
document.addEventListener('chairforce:content-updated', () => {
  applyView(getStoredView());
});
```

**Do not** dispatch `content-updated` from the switcher itself (no DOM subtree
replace — only a class toggle).

**Optional v1.1:** inline `wp_footer` script reading `localStorage` before paint
to reduce flash of grid layout — not required for chunk 5 sign-off.

---

### Chunk 3 — Sass

**Files:**

- `src/sass/woocommerce/_product-view-switcher.scss` — new, import from woocommerce index
- Extend `_shop-archive-layout.scss` if needed for toolbar alignment

**Toolbar:**

- Icon-only buttons; active state matches filter bar / sort row visual weight
- `aria-pressed="true"` styling for selected grid or list

**List layout** (under `.cf-shop-archive-main.cf-products-view-list`):

```scss
// Pseudocode — tune against Figma
.wc-block-product-template {
  grid-template-columns: 1fr !important; // override WC responsive columns
}

.wc-block-product {
  display: grid;
  grid-template-columns: minmax(8rem, 12rem) minmax(0, 1fr);
  gap: $spacing-medium;
  align-items: start;
}

// Reset grid-centric card alignment
.post-title, .wp-block-woocommerce-product-price, .wp-block-woocommerce-product-button {
  text-align: left;
}
```

**Preserve:** quick view, wishlist, swatches, sale badge — all stay in card;
adjust positioning only. Reference `src/sass/quick-view/_card-media.scss`.

**Do not** override WC Interactivity attributes or remove `wc-block-product-template__responsive` classes — list mode is theme layer on top.

---

### Chunk 4 — Wiring

**PHP:**

- Enqueue / module registration via `lib/class-woocommerce-archive.php` (or
  confirm `public.js` already loads on shop archives via `class-front.php`).

**No changes to:**

- `includes/load-more-functions.php`
- `includes/rest-api/load-more.php`
- `parts/product-collection.html`
- `src/js/product-filters.js` (switcher listens globally to `content-updated`)

**Icons:** if `layout-grid` / `layout-list` not in curated Lucide set, add to
`src/js-admin/lucide-icon-options.js` + static icon font CSS per
`.cursor/rules/16-icon-system.mdc`.

---

### Chunk 5 — QA matrix

| Scenario | Expected |
|---|---|
| Shop — toggle list | Single column, horizontal cards |
| Toggle grid ↔ list | Instant, no network |
| Hard refresh in list mode | Grid SSR briefly OK; JS restores list from storage |
| List + Load More | Page 2 cards match list layout |
| List + filter apply | Shell swaps; stays list |
| List + sort change | Shell swaps; stays list |
| Browser Back after filter | View restored from storage |
| Mobile toolbar | Toggle reachable, tappable targets |
| Quick View / wishlist / swatches in list | Functional |
| Related products on single product | Still grid — unaffected |

---

## File touch list

| File | Action |
|---|---|
| `src-jsx-blocks/product-view-switcher/` | **Create** — block.json, index.js, edit.js, save.js, render.php |
| `parts/shop-archive-shell.html` | **Edit** — `<!-- wp:chairforce/product-view-switcher /-->` |
| `src/js/product-view-switcher.js` | **Create** |
| `src/public.js` | **Edit** — import |
| `src/sass/woocommerce/_product-view-switcher.scss` | **Create** |
| `src/sass/woocommerce/_index.scss` (or equivalent) | **Edit** — import |
| `src/sass/woocommerce/_shop-archive-layout.scss` | **Edit** — grid areas |
| `src/js-admin/lucide-icon-options.js` | **Edit** — if icons missing |
| `assets/css/button-icon-font.css` | **Edit** — if icons missing |

Run `npm run build:blocks` after adding the JSX block.

**Do not create** `partials/product-view-switcher.php` as the primary surface —
markup lives in the block's `render.php` (may call shared PHP helpers if needed).

**Explicitly unchanged:**

- `parts/product-collection.html`
- `parts/product-card.html`
- `includes/load-more-functions.php`
- `src/js/product-filters.js`
- `src/js/shared/load-more.js`

---

## Dependencies

| Phase | Relationship |
|---|---|
| **3f** | ✅ Required — filters band + shell must exist (done / in QA) |
| **3i** | ✅ Required — Load More append path (done) |
| **3d / product-grid** | ✅ Required — shared card partial (done) |

**Schedule:** implement after **3f QA** sign-off or in parallel if toolbar layout
work is independent — no hard blocker beyond shop shell being stable.

---

## Open questions (PM / design)

| # | Question | Plan default |
|---|---|---|
| 1 | List mode — layout-only or add excerpt/rating blocks to card? | Layout-only v1 |
| 2 | Shareable list URL for support/marketing? | No URL state v1 |
| 3 | Default list on mobile? | No — grid default everywhere |
| 4 | Hide toggle when ≤1 product? | No — always show if shell present |

Escalate #1 before chunk 3 if Figma list comp shows content grid cannot render.

---

## Related docs

| Doc | Purpose |
|---|---|
| `context/notes/grid-list-switcher-findings.md` | Investigation source |
| `context/PROGRESS.md` | Phase 3k tracker |
| `context/plans/3f-product-filters-plan.md` | Filters band (grid/list originally deferred here) |
| `context/plans/3i-load-more-plan.md` | Append contract |
