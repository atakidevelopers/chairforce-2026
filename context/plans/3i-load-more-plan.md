# 3i — Page-1 Load More (WooCommerce Product Collection) — Implementation Plan

## Status: ⏳ Not started

| Chunk | Scope | Commit |
|---|---|---|
| 1 | REST endpoint + main-query rebuild + product-template HTML render | — |
| 2 | `core/query-pagination` extension (page-1 Load More button only) | — |
| 3 | Frontend JS (fetch, append, loading state) + Sass | — |
| 4 | Template/editor wiring, deactivate plugin, verify + PROGRESS update | — |

## Goal

Ship **theme-owned Load More** on WooCommerce shop / taxonomy / attribute
archives (main Product Collection, `inherit: true`):

- **Page 1 only:** replace pagination with a Load More button.
- **Page 2+:** default numbered / prev-next pagination (crawlable URLs for
  sitemap and SEO).
- **Transport:** partial HTML via REST (Rudrastyh *idea*, block-theme
  *execution*) — **not** full-page scrape (`query-loop-load-more` plugin model).
- **Query:** main query vars only (v1).
- **Append:** server-rendered product cards into `.wc-block-product-template`;
  dispatch `chairforce:content-updated` after each append.

**Source docs (read before implementing):**

- `context/notes/load-more-findings.md` — investigation + rejected plugin.
- `context/notes/product-grid-cards-and-load-more.md` — card markup dependency
  (separate workstream; Load More must render the same card HTML as the grid).
- `context/existing-functionality/17-load-more-and-event-delegation.md` —
  delegated-events architecture.
- `.cursor/rules/18-event-delegation.mdc` — theme module paths.
- [Rudrastyh — Load More Products](https://rudrastyh.com/woocommerce/load-more-products.html) —
  classic partial-HTML reference (adapt, do not copy `content-product.php`).

---

## Locked decisions

### Page-1 Load More / page-2+ classic pagination (SEO)

```text
/shop/                    → Load More button (JS append, URL stays page 1)
/shop/page/2/             → default pagination (prev / numbers / next)
/shop/page/3/             → default pagination
…                         → sitemap + audit-friendly paginated URLs unchanged
```

**PHP rule:**

```php
if ( load_more_enabled && ! is_paged() ) {
    // render Load More button
} else {
    // render_block_core_query_pagination( … ) — WC Interactivity pagination OK
}
```

**Explicit product name:** **Page-1 Load More** — document in code comments so
future devs do not expect Load More on `/page/2/`.

Optional later (not v1): `<link rel="next" href="…/page/2/">` on page 1 for
crawlers that do not execute JS.

### Rejected approaches

| Approach | Verdict |
|---|---|
| `query-loop-load-more` plugin | ❌ Broken on Product Collection DOM; do not fork long-term |
| Full-page fetch + DOM scrape | ❌ Heavy, fragile selectors |
| WC Store API + client card render | ❌ Breaks PHP block cards |
| `wc_get_template_part( 'content', 'product' )` | ❌ Spike only; wrong markup for FSE card |
| Load More on every paginated page | ❌ SEO / sitemap requirement |

### v1 query scope

- **In:** main query archives — shop, `product_cat`, `product_tag`, attribute
  taxonomies where Product Collection uses `"inherit": true`.
- **Out:** multiple Product Collection blocks on one page; non-inherited
  collections; related / upsell grids on single product; `core/query` blog
  archives (future phase if needed).

---

## What's already reusable (no reinventing)

| Piece | Location | Load More usage |
|---|---|---|
| Delegated click handlers | `src/js/shared/delegated-events.js` | Swatches, quick view work on appended cards without rebind |
| Content-updated event | `dispatchContentUpdated()` | Call after each append |
| REST convention | `lib/class-api.php` → `includes/rest-api/*.php` | New `load-more.php` route |
| Public script data | `Chairforce\Front::get_localize_script_data()` | Extend with endpoint URL / nonce |
| Quick view on appended cards | `src/js/quick-view.js` (delegated) | Verify after append |
| Grid swatch hover | `src/js/single-product-swatches.js` (delegated) | Verify after append |
| Quick view runtime inject | `WooCommerce_Archive::inject_quick_view_button()` | Runs on rendered `product-image` blocks in REST HTML |

**Prerequisite (card markup):** REST handler must render the **same**
`woocommerce/product-template` inner blocks as `templates/archive-product.html`
(or the canonical card pattern once that work lands — see product-grid notes).
Load More does not solve global card parity; it **depends** on one stable markup
source.

---

## Architecture

```text
┌─────────────────────────────────────────────────────────────────┐
│ Page 1 — /shop/                                                 │
├─────────────────────────────────────────────────────────────────┤
│ woocommerce/product-collection (inherit: true)                  │
│   └── woocommerce/product-template  ← first batch from main query│
│   └── core/query-pagination                                       │
│         └── [theme] Load More button  (replaces numbers on p1)   │
└─────────────────────────────────────────────────────────────────┘
         │ click
         ▼
GET /wp-json/chairforce/v1/load-more?page=2&orderby=…
         │
         ▼
PHP: WP_Query( main_query_vars + paged )
     → loop products
     → render each as product-template item (<li class="wc-block-product">…)
         │
         ▼
JSON { html, nextPage, hasMore, maxPages }
         │
         ▼
JS: append html → .wc-block-product-template
    dispatchContentUpdated({ source: 'load-more' })
    update button data-page / hide at last page

┌─────────────────────────────────────────────────────────────────┐
│ Page 2+ — /shop/page/2/                                         │
├─────────────────────────────────────────────────────────────────┤
│ Same grid, standard core/WC query-pagination (no Load More)       │
└─────────────────────────────────────────────────────────────────┘
```

---

## REST API contract

**Route:** `GET chairforce/v1/load-more`

**Request params (v1):**

| Param | Required | Notes |
|---|---|---|
| `page` | yes | Next page number to fetch (integer ≥ 2) |
| `orderby` | no | From current URL if catalog sorting active |
| `order` | no | From current URL |

Server rebuilds query from **`$GLOBALS['wp_query']->query_vars`** captured at
request time **or** re-derives archive context from referer + main query API —
implementation detail in chunk 1; must match what page 1 displayed.

**Response (200):**

```json
{
  "html": "<li class=\"wc-block-product\">…</li><li>…</li>",
  "nextPage": 3,
  "hasMore": true,
  "maxPages": 58
}
```

**Empty / last page:** `html: ""`, `hasMore: false` → client removes button.

**Errors:** standard `WP_REST_Response` / `WP_Error` (400 bad page, 404 wrong context).

**Permission:** `permission_callback` → `__return_true` (public catalog; same as
quick-view / product-search).

**Security:** sanitize `page`, `orderby`, `order`; validate post type `product`;
do not accept arbitrary `query_vars` JSON from client in v1 (main query only).

---

## Pagination block extension (editor + frontend)

Mirror **Query Loop Load More** editor UX, implement in theme:

**Hook:** `register_block_type_args` filter on `core/query-pagination`.

**New attributes:**

| Attribute | Type | Default |
|---|---|---|
| `loadMore` | boolean | `false` |
| `loadMoreText` | string | `Load More` |
| `loadingText` | string | `Loading…` |

**Render callback (`Chairforce\Load_More`):**

1. If `loadMore !== true` → delegate to `render_block_core_query_pagination()`.
2. If `is_paged()` → delegate to core (page 2+ classic pagination).
3. Else → output Load More button with `data-max-pages`, `data-next-page="2"`,
   classes compatible with theme Sass (e.g. `.cf-load-more__button`).

**Editor JS:** extend `core/query-pagination` in `src/js-admin/` or
`src/blocks-extension/` — ToggleControl “Use Load More on page 1” (wording TBD).

**Assets:** enqueue frontend JS/CSS only when pagination block has
`loadMore: true` (block supports `script`/`style` handles or conditional
enqueue in render callback).

**Do not** reuse plugin script handles or full-page fetch logic.

---

## Card HTML render (chunk 1 core work)

**Preferred:** store product-template inner markup in one theme-owned source:

- Option A: PHP constant / include file exported from `archive-product.html`
  inner blocks (block comment string).
- Option B: read from theme pattern once `chairforce/product-card-template`
  exists.

**Render loop (per product):**

1. `$query->the_post()`
2. Build `WP_Block` instance with product-template parsed block + context:
   `postId`, `postType`, `query`, `displayLayout` (match collection).
3. Wrap rendered output in `<li class="wc-block-product …">` matching live grid.

**Spike fallback (time-boxed):** `wc_get_template_part( 'content', 'product' )` —
expect failure; abandon if markup diverges.

**Must trigger:** `render_block` filters on inner blocks (quick-view inject on
`woocommerce/product-image` with `isDescendentOfQueryLoop`).

---

## Frontend JS (chunk 3)

**File:** `src/js/shared/load-more.js` (import from `src/public.js`).

**Behaviour:**

1. `delegateDocument( 'click', '.cf-load-more__button', handler )`
2. `preventDefault()`; guard `loading` class / disabled state.
3. Read `data-next-page`, build REST URL (+ current `orderby`/`order` from
   `window.location.search`).
4. Fetch JSON; append `html` to closest
   `.wc-block-product-template` (within
   `.wp-block-woocommerce-product-collection`).
5. `dispatchContentUpdated({ source: 'load-more' })`
6. Increment `data-next-page`; remove button when `!hasMore`.
7. Toggle loading text / `aria-busy`.

**Out of scope v1:** `history.pushState`, infinite scroll, URL update.

---

## Sass

**File:** `src/sass/components/_load-more.scss` (or under `woocommerce/`).

- Button uses theme button tokens (match `core/button` / WC product grid CTA).
- Loading state (`.cf-load-more__button.is-loading`).
- Import from main Sass index.

---

## File checklist (expected new / touched files)

| File | Purpose |
|---|---|
| `lib/class-load-more.php` | Block extension, render callback, asset registration |
| `includes/rest-api/load-more.php` | REST route + query + HTML render |
| `lib/class-init.php` | Instantiate `Load_More` |
| `lib/class-api.php` | `require_once` load-more REST file |
| `src/js/shared/load-more.js` | Frontend append logic |
| `src/public.js` | Import load-more module |
| `src/js-admin/` or `src/blocks-extension/` | Pagination block editor controls |
| `src/sass/…/_load-more.scss` | Styles |
| `templates/archive-product.html` | Enable `loadMore` on pagination block attrs |
| Deactivate | `query-loop-load-more` plugin |

---

## Chunk breakdown

### Chunk 1 — REST + card render

- [ ] Create `includes/rest-api/load-more.php`; register in `class-api.php`.
- [ ] Implement main-query var capture + `WP_Query` with `paged`.
- [ ] Implement product-template item renderer (canonical markup source).
- [ ] Manual test: `curl`/browser `GET …/chairforce/v1/load-more?page=2` on shop
      — returns valid `<li>` HTML matching live cards.
- [ ] Resolve **perPage mismatch** (block says 10, UI shows 12) before trusting
      `maxPages`.

### Chunk 2 — Pagination block (page-1 only)

- [ ] Create `lib/class-load-more.php`; register in `class-init.php`.
- [ ] `register_block_type_args` for `core/query-pagination` + render callback.
- [ ] Page-1 button markup + `is_paged()` fallback to core pagination.
- [ ] Editor attributes + inspector toggle.
- [ ] Update `templates/archive-product.html` pagination attrs
      (`"loadMore": true`, button text).
- [ ] Sync DB-customised archive template on dev if needed (post 1514388).

### Chunk 3 — Frontend + styles

- [ ] `load-more.js` + import in `public.js`.
- [ ] Extend `Chairforce_Public` localize data if needed.
- [ ] Sass for button / loading state; `npm run build:assets`.
- [ ] End-to-end click on `/shop/` — products append, count grows.

### Chunk 4 — Verify, cleanup, docs

- [ ] Appended cards: swatch hover, quick view, add-to-cart triggers work
      (delegated — no rebind).
- [ ] `/shop/page/2/` shows **normal pagination**, no Load More button.
- [ ] Catalog sort change on page 1 → Load More fetches sorted page 2.
- [ ] Category archive spot-check (e.g. cafe-chairs).
- [ ] Deactivate `query-loop-load-more`.
- [ ] Update `context/PROGRESS.md` (Load More row + link to this plan).

---

## Verification rule

**Done means verified in browser**, not just code-complete:

1. Page 1 Load More appends at least two batches without full reload.
2. Page 2 URL shows classic pagination with crawlable `<a href="…/page/3/">`.
3. Network tab shows REST partial fetch (not full document).
4. No console errors; appended card interactions work.
5. Plugin deactivated.

---

## Known risks & mitigations

| Risk | Mitigation |
|---|---|
| REST query ≠ visible page-1 query | Derive vars from main query; pass sort from URL; test after sort change |
| Card markup drift vs archive template | Single markup source shared by template + REST |
| `max_pages` wrong due to perPage mismatch | Fix posts-per-page source of truth in chunk 1 |
| DB template ≠ theme file | Document sync step in chunk 4 |
| WC Interactivity conflicts on page 1 | Load More replaces pagination markup entirely on p1 |

---

## Out of scope (v1)

- Infinite scroll
- Multiple query / collection blocks per page
- Related / upsell Product Collections
- `core/query` blog Load More
- Jet Smart Filters / AJAX filter integration
- `history.pushState` / URL update on append
- Block Hooks for card composition (separate plan in product-grid notes)

---

## After ship

- Link this plan from `context/PROGRESS.md` under a new **3i** row (or extend
  existing Load More deferred item).
- Cross-link from `context/notes/load-more-findings.md` §8 checklist.
