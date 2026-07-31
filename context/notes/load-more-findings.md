# Load More — investigation & architecture notes

Captured: 30 Jul 2026.

Findings from evaluating Load More for WooCommerce shop/archive grids in the
Chairforce block theme. **Product card composition** (swatches, quick view,
related grids) is documented separately in
`context/notes/product-grid-cards-and-load-more.md`.

**Implementation plan:** `context/plans/3i-load-more-plan.md`.

Related docs:

- `context/existing-functionality/17-load-more-and-event-delegation.md` —
  legacy Woodmart behaviour + mandatory delegated-events rule.
- `.cursor/rules/18-event-delegation.mdc` — theme module
  (`src/js/shared/delegated-events.js`).
- `context/PROGRESS.md` — 3b event-delegation shipped; **3i Load More shipped**
  (`d7d7acc`, minor quirks pending polish).

---

## 1. Requirement

Append-style pagination on **shop / category / attribute archives** (main Product
Collection, `inherit: true`):

- User clicks **Load More** (or infinite scroll later).
- Next batch of **server-rendered product cards** appends to the existing grid.
- No full page reload.
- Appended cards must work with existing **delegated** grid handlers (no rebind
  step).

**Out of scope for v1:**

- Multiple Query Loop / Product Collection blocks on one page.
- Non-inherited custom Product Collection queries.
- Related products, upsells, “You may also like” grids on single product.

---

## 2. Plugin evaluated: Query Loop Load More

**Path:** `wp-content/plugins/query-loop-load-more` (v1.0.18, Automattic Special
Projects).

**What it does:**

- Extends `core/query-pagination` with a Load More toggle in the block editor.
- Replaces numbered pagination with a button (or infinite scroll).
- Frontend fetches the **full next page URL** as HTML, finds the matching query
  region, extracts `.wp-block-post-template` inner HTML, appends it.

**Why it was considered:**

- Native Gutenberg / FSE approach.
- No custom REST endpoint.
- Works with Query Loop block architecture.

### 2.1 Live verification (Chairforce dev site)

On `https://chairforce-2026.test/shop/` (Load More enabled in DB-customised
archive template):

| Check | Result |
|---|---|
| Plugin active, assets load | ✅ `frontend.js` + CSS enqueued |
| Load More button renders | ✅ `.wp-load-more__button`, `data-query-max-page="58"` |
| Click Load More | ❌ Nothing happens |
| Network request to next page | ❌ No fetch fired |
| Console errors | ❌ None (silent failure) |

Product count stays at “Showing 1–12 of 693”; URL unchanged.

### 2.2 Root cause — WooCommerce Product Collection incompatibility

The shop uses `woocommerce/product-collection`, not `core/query`.

**Frontend JS** (`assets/js/src/frontend.js`) hardcodes:

```js
const container = button.closest( '.wp-block-query' );
// …
const posts = temp.querySelector(
  `.wp-block-query[data-qllm-query-region="${ containerRegion }"] .wp-block-post-template`
);
const targetTpl = container.querySelector( '.wp-block-post-template' );
```

On WC shop:

| Plugin expects | WC renders |
|---|---|
| `.wp-block-query` | `.wp-block-woocommerce-product-collection` |
| `.wp-block-post-template` | `.wc-block-product-template` / `.wp-block-woocommerce-product-template` |
| `data-qllm-query-region` on query wrapper | Attribute never added (PHP filter only hooks `core/query`) |

Click handler calls `fetchPosts()` → `container` is `null` → early return before
fetch. No error surfaced to the user.

### 2.3 Other plugin notes

- Tested on fresh WP+WC — same failure when Load More is enabled on Product
  Collection pagination (not a Chairforce-theme-specific bug).
- Open upstream issue: [query-loop-load-more#22 — Support for WooCommerce](https://github.com/a8cteam51/query-loop-load-more/issues/22) (Aug 2024, still open).
- Plugin readme / docs assume **Query Loop + Pagination** only.
- Also installed but not evaluated for this use case:
  `load-more-products-for-woocommerce` (BeRocket) — classic AJAX plugin, different
  architecture.

### 2.4 Verdict on plugin

**Do not use for WC Product Collection shop grids.** Editor toggle works; frontend
append does not. Patching the plugin is viable only as a throwaway spike, not a
long-term dependency.

---

## 3. Architecture options considered

### Option 1 — Patch plugin, then recreate in theme

- Quick proof that append UX works on `/shop/`.
- Throwaway work if theme owns the feature anyway.

### Option 2 — Theme-owned Load More (recommended)

- Partial AJAX returning **server-rendered card HTML** (not full-page scrape).
- REST route under `chairforce/v1` (same family as existing theme APIs).
- Generalise selectors / rendering for Product Collection (v1: main query only).
- Integrate with `chairforce:content-updated` after append.

### Option 3 — Fully custom system (ignore plugin model)

- e.g. WC Store API + client-side card rendering, or WC Interactivity-only
  navigation.
- **Rejected:** card markup is block/PHP-driven; JSON client render duplicates
  logic and breaks parity with FSE templates.

---

## 4. Recommended approach — partial AJAX (Rudrastyh idea, block-theme execution)

Reference: [WooCommerce Load More Products (Rudrastyh)](https://rudrastyh.com/woocommerce/load-more-products.html) —
classic theme pattern:

1. Pass **query context** to the server (his: `$wp_query->query_vars` via
   `wp_localize_script`).
2. **AJAX request** for next page.
3. Server runs query with `paged + 1`.
4. Return **HTML fragment** (his: `wc_get_template_part( 'content', 'product' )`).
5. Client **appends** to grid.

### 4.1 Chairforce adaptation

| Rudrastyh (classic) | Chairforce (block theme) |
|---|---|
| `admin-ajax.php` + `wp_ajax_*` | `GET /wp-json/chairforce/v1/load-more` (or similar) |
| `$wp_query->query_vars` | Main query vars on archive pages (`inherit: true`) |
| `wc_get_template_part( 'content', 'product' )` | Render canonical `woocommerce/product-template` inner markup server-side (`WP_Block` / stored template string — **not** `content-product.php`) |
| jQuery append | Vanilla JS + `delegated-events.js` |
| `.products` container | `.wc-block-product-template` (`<ul>`) |

**Why partial HTML beats full-page scrape (plugin model):**

- Smaller payload.
- No DOM region matching / `data-qllm-query-region` hacks.
- No dependency on `.wp-block-query` existing in the document.
- Explicit server contract.

**Tradeoff:** PHP must reconstruct the query faithfully (sort params, archive
context). Acceptable for v1 main-query scope.

### 4.2 v1 query scope

**Use main query only** — do not support multiple query blocks on a page.

- Shop, `product_cat`, `product_tag`, attribute archives: Product Collection
  with `"inherit": true` **is** the main query.
- Pass from client: `page` (next), current URL sort params (`orderby`, `order`).
- Server: `new WP_Query( $main_query_vars )` with updated `paged` — **not**
  `query_posts()`.

Revisit block-context / Query Loop params only if a future page needs a
non-inherited grid.

### 4.3 Frontend integration

- Module: e.g. `src/js/shared/load-more.js` (or `src/js/load-more.js`).
- Bind Load More click via **document delegation** (already the 3b convention).
- Append response HTML to `.wc-block-product-template` with `insertAdjacentHTML`.
- Call `dispatchContentUpdated({ source: 'load-more' })` from
  `src/js/shared/delegated-events.js`.
- Optional: infinite scroll via `IntersectionObserver` on the button (same fetch
  function).

### 4.4 Card HTML source for the endpoint

The REST handler must return the **same product card markup** as the live grid
template. How that canonical markup is defined is a **product-card composition**
 concern (see separate notes doc). Load More depends on it but does not solve it.

**Render order of attempts (from discussion):**

1. Server-render stored **product-template block markup** (preferred).
2. Fallback spike: `content-product.php` — expect mismatch on block theme; move
   on quickly.
3. Avoid: full-page fetch + scrape.

---

## 5. WooCommerce Product Collection — pagination interaction

WC Product Collection ships **enhanced client-side pagination** via the
Interactivity API when `forcePageReload` is false (default):

- `data-wp-interactive="woocommerce/product-collection"`
- `data-wp-router-region="wc-product-collection-{queryId}"`
- Standard prev/next links get `woocommerce/product-collection::actions.navigate`

When Load More **replaces** pagination markup (as the plugin does when enabled):

- WC interactivity directives are not applied to the Load More button.
- Load More JS and WC pagination are **mutually exclusive** UI — only one system
  should be active; do not mix numbered WC pagination with a broken Load More
  button.

Theme implementation should **replace** pagination with Load More in the template
(or block extension), not layer both.

---

## 6. Known quirks & open questions

### 6.1 Observed on dev site

- **perPage mismatch:** block `data-query` shows `perPage: 10`; results count
  shows 12 products. Reconcile before calculating `max_pages` / button visibility
  (Customizer vs block setting vs WC filter).
- **DB vs file template:** live shop template is customised in the database; theme
  `templates/archive-product.html` may differ until synced in Site Editor.

### 6.2 Open for next session

1. **Endpoint contract** — params (`page`, `orderby`, `order`?) and response
   `{ html, nextPage, hasMore, maxPages }`.
2. **Canonical card markup** — where the product-template string lives (pattern,
   PHP partial, template export).
3. **Editor UX** — extend `core/query-pagination` attributes vs standalone block vs
   template-only button.
4. **Catalog sorting** — ensure AJAX query respects active sort (URL params).
5. **Future filters** — Jet Smart Filters / native filters must pass same query
   args (post–v1).
6. **Infinite scroll** — same endpoint, `IntersectionObserver` on sentinel.
7. **Deactivate** `query-loop-load-more` after theme feature ships.
8. **Update URL** — optional `history.pushState` for shareable “loaded depth”
   (plugin had `updateUrl` setting; default off since v1.0.7).

### 6.3 Plugin issues not affecting WC but worth knowing

- [#55](https://github.com/a8cteam51/query-loop-load-more/issues/55) — search
  pages losing `?s=` on fetch (fixed in v1.0.17 for plugin; irrelevant if we
  don’t use the plugin).
- Multiple query loops on one page — region counter edge cases (plugin v1.0.16+).

---

## 7. Decision log

| Date | Decision |
|---|---|
| 30 Jul 2026 | `query-loop-load-more` plugin **rejected** for WC shop — architectural mismatch with Product Collection DOM. |
| 30 Jul 2026 | Production approach: **theme REST + partial HTML append**, not full-page scrape. |
| 30 Jul 2026 | v1 query scope: **main query only** (shop / taxonomy archives, `inherit: true`). |
| 30 Jul 2026 | Classic `content-product.php` — spike only; block-template render is the real path. |
| 30 Jul 2026 | Must dispatch `chairforce:content-updated` after append (3b delegated-events). |
| 30 Jul 2026 | Optional plugin patch acceptable as 1–2 hr spike only; do not maintain a fork. |
| 31 Jul 2026 | Theme Load More **shipped** as 3i (`d7d7acc`); minor quirks pending polish. |

---

## 8. Implementation checklist

- [x] Spec REST route `chairforce/v1/load-more`.
- [x] Implement main-query reconstruction + paged increment.
- [x] Render product cards from `parts/product-card.html` (canonical source).
- [x] Frontend: delegated click, append, loading state, hide button at last page.
- [x] Dispatch `chairforce:content-updated`.
- [x] Page-1 Load More via `core/query-pagination` `loadMore` attribute.
- [x] Update `context/PROGRESS.md` + plan doc.
- [ ] **Follow-up:** minor quirks polish + full browser verification.
- [ ] Deactivate `query-loop-load-more`.
