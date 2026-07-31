# 3j — Wishlist (logged-in only, custom table) — Implementation Plan

## Status: ⏳ Not started

Plan locked from investigation feedback (31 Jul 2026). No implementation yet.

| Chunk | Scope | Status |
|---|---|---|
| 1 | ACF options + DB table + `Wishlist` class bootstrap | ⏳ |
| 2 | REST toggle/list API + login-only permissions | ⏳ |
| 3 | Product loop heart (`product-card.html` + JSX block + JS) | ⏳ |
| 4 | Single-product wishlist control | ⏳ |
| 5 | My Account `wishlist` endpoint + list UI | ⏳ Deferred |

## Goal

Rebuild wishlist as a **theme-owned, logged-in-only** feature with a **clean custom
table**, admin toggles on the **Chairforce → Theme Options → WooCommerce** tab,
and three display surfaces:

1. **Product loop** — heart icon on product cards (Figma: stacked above quick-view
   eye in `cf-card-media`; see 3h out-of-scope note).
2. **Single product** — add/remove control on the product summary column.
3. **My Account** — WooCommerce account endpoint listing saved products.

**No migration.** Live Woodmart data is 3 empty wishlist shells and 0 products
(file `13`). Woodmart table names, cookie guest flow, multi-list groups, and
header badge are **explicitly out of scope**.

**Source docs (read before implementing):**

- `context/existing-functionality/13-wishlist.md` — legacy Woodmart investigation.
- `context/plans/3h-quick-view-plan.md` — card corner layout; heart deferred here.
- `context/implementation/product-grid.md` — canonical `parts/product-card.html`.
- `.cursor/rules/16-icon-system.mdc` — Lucide heart icon registration.
- `.cursor/skills/chairforce-woocommerce/` — class + REST patterns.

---

## Locked decisions

### Feature policy

| Decision | Value |
|---|---|
| **Guest wishlist** | ❌ **No** — logged-in users only |
| **Multi-list** | ❌ **No** — one implicit list per user |
| **Migration** | ❌ **None** — fresh schema, zero Woodmart compatibility |
| **Header wishlist icon/count** | ❌ **Not built** — no header element |
| **Storage** | ✅ **Custom DB table** (not user meta) |

**Rationale for table over user meta:** keeps product ID sets out of bloated
`usermeta`, supports indexed lookups (`user_id`, `product_id`), and leaves room
for efficient account-page queries without unserializing PHP arrays on every
request.

### Theme options (ACF — WooCommerce tab)

Add a **WooCommerce** tab to `acf-json/group_theme_options.json` (new tab; move
existing commerce fields such as `quick_view_*` here only if convenient — not
required for 3j).

| Field name | Type | Default | Purpose |
|---|---|---|---|
| `wishlist_enabled` | `true_false` | `1` (on) | **Master switch.** When on: registers the WooCommerce `wishlist` account endpoint, enables REST + DB writes, shows single-product control. When off: wishlist is fully disabled site-wide. |
| `wishlist_loop_enabled` | `true_false` | `1` (on) | Show heart toggle on **product loop cards** (`parts/product-card.html`). Conditional on `wishlist_enabled` (ignored when master is off). |

**Two fields only** — no separate “guest access”, “multi-list”, or header toggles.

**PHP guard pattern:**

```php
function chairforce_is_wishlist_enabled(): bool {
    return (bool) get_field( 'wishlist_enabled', 'option' );
}

function chairforce_is_wishlist_loop_enabled(): bool {
    return chairforce_is_wishlist_enabled()
        && (bool) get_field( 'wishlist_loop_enabled', 'option' );
}
```

Place helpers in `includes/helper-functions.php` (or a small wishlist helper
include loaded by the Wishlist class).

### Logged-out UX

When wishlist is enabled and a **logged-out** user hits a wishlist control:

- **Do not** write cookies or localStorage.
- REST returns `401` with a translatable message.
- Frontend redirects to `wc_get_page_permalink( 'myaccount' )` (or opens login
  with `redirect` back to current URL) — pick one pattern in chunk 3 and reuse
  everywhere.

### Display surfaces vs options

| Surface | When shown |
|---|---|
| Product loop heart | `wishlist_enabled` **and** `wishlist_loop_enabled` |
| Single product control | `wishlist_enabled` only |
| My Account wishlist tab | `wishlist_enabled` only |

---

## Database schema

**Table:** `{prefix}chairforce_wishlist_items` (use `$wpdb->prefix` + constant in
class; e.g. `wp_chairforce_wishlist_items`).

Single-table design — no header/join table needed for one-list-per-user.

```sql
CREATE TABLE {prefix}chairforce_wishlist_items (
    id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT(20) UNSIGNED NOT NULL,
    product_id BIGINT(20) UNSIGNED NOT NULL,
    date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY user_product (user_id, product_id),
    KEY user_id (user_id),
    KEY product_id (product_id)
) {charset_collate};
```

**Install:** `dbDelta()` on theme activation or first `Wishlist` init when option
`chairforce_wishlist_db_version` is unset/outdated (same pattern as other theme
install routines — avoid running `dbDelta` on every request).

**Operations:**

- Add: `INSERT IGNORE` or check-then-insert on `(user_id, product_id)`.
- Remove: `DELETE WHERE user_id = ? AND product_id = ?`.
- List (account page): `SELECT product_id … ORDER BY date_added DESC`.
- Batch status (loop): `SELECT product_id FROM … WHERE user_id = ? AND product_id IN (…)`
  for visible card IDs.

Validate `product_id` is published `product` (or visible variation parent) before
insert.

---

## Architecture

```text
┌──────────────── Theme Options (ACF) ────────────────────────────────┐
│ wishlist_enabled (master) │ wishlist_loop_enabled (loop surface)     │
└────────────────────────┬────────────────────────────────────────────┘
                         │
                         ▼
              Chairforce\Wishlist (lib/class-wishlist.php)
              ├── db install / CRUD
              ├── WC account endpoint (when wishlist_enabled)
              └── hooks: conditional asset enqueue

┌──────────── REST (logged-in only) ──────────────────────────────────┐
│ POST   /wp-json/chairforce/v1/wishlist/toggle   { productId }       │
│ GET    /wp-json/chairforce/v1/wishlist          → product IDs       │
│ GET    /wp-json/chairforce/v1/wishlist/status?ids=1,2,3  → map      │
└────────────────────────┬────────────────────────────────────────────┘
                         │
     ┌───────────────────┼───────────────────┐
     ▼                   ▼                   ▼
 product-card.html   single-product.html   My Account endpoint
 (heart block)       (summary control)     (chunk 5 — deferred)
```

**Class registration:** `lib/class-init.php` → instantiate `Wishlist` from
`WooCommerce` entry or directly alongside other WC classes.

**REST registration:** `includes/rest-api/wishlist.php` via `Chairforce\Api`.

---

## REST API contract

All routes require `is_user_logged_in()` in `permission_callback`.

### Toggle

**Route:** `POST chairforce/v1/wishlist/toggle`

**Body:**

```json
{ "productId": 1000290 }
```

**Response (200):**

```json
{
  "productId": 1000290,
  "inWishlist": true,
  "count": 4
}
```

Toggle semantics: if row exists → delete (`inWishlist: false`); else insert
(`inWishlist: true`). `count` = total items for current user (optional but
useful for future UI; safe to omit in v1 if unused).

**Errors:** `400` invalid product; `401` not logged in; `403` wishlist disabled
(`wishlist_enabled` off).

### List (account page / hydration)

**Route:** `GET chairforce/v1/wishlist`

**Response:**

```json
{
  "productIds": [ 1000290, 1000438 ],
  "count": 2
}
```

### Batch status (loop cards)

**Route:** `GET chairforce/v1/wishlist/status?ids=1,2,3`

**Response:**

```json
{
  "1000290": true,
  "1000438": false
}
```

Limit `ids` param length server-side (e.g. max 50 per request) to avoid abuse.

---

## Frontend surfaces

### 1. Product loop (chunk 3)

**Markup:** Add `<!-- wp:chairforce/wishlist-button /-->` to
`parts/product-card.html` inside `cf-card-media`, **above** the quick-view block
(Figma stack order).

**Block:** New JSX block `src-jsx-blocks/wishlist-button/` mirroring
`quick-view-button` (static save, `render.php` for server class/state when
logged in).

**JS:** `src/js/wishlist.js` (or `src/js/shared/wishlist.js`):

- Delegated click on `.cf-wishlist-button` (follow 3b delegated-events rule).
- `POST` toggle endpoint; toggle `is-active` / `aria-pressed` on success.
- On `401`, redirect to login.
- After Load More append, optionally fetch batch status for new card IDs only
  (or rely on server-rendered initial state in REST HTML — prefer server truth
  in `render.php` for first paint, JS for toggles).

**Sass:** Extend `src/sass/quick-view/_card-media.scss` or new
`src/sass/woocommerce/_wishlist.scss` — position heart above eye; active state
(filled heart) per Figma.

**Icon:** Add `heart` to curated Lucide set (`lucide-icon-options.js`,
`button-icon-font.css`, `$cf-icon-codepoints`) using rule 16 byte-verified
process — same as `eye` in 3h.

**Enqueue:** Loop JS/CSS only when `chairforce_is_wishlist_loop_enabled()`.

### 2. Single product (chunk 4)

Add wishlist control to `templates/single-product.html` summary column — e.g.
after `woocommerce/add-to-cart-form` or as a JSX block
`chairforce/wishlist-button` with a `context: single` attribute if the same block
serves both surfaces.

Reuse the same REST toggle + JS module; different Sass wrapper (summary row, not
floating card corner).

**Enqueue:** When `chairforce_is_wishlist_enabled()`.

### 3. My Account wishlist page (chunk 5 — deferred)

**Not in initial 3j ship** per product owner direction (“don’t worry about that
now”), but architect for it now:

- Register endpoint key `wishlist` via `woocommerce_get_query_vars` /
  `add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES )` when `wishlist_enabled`
  is on.
- Add menu item via `woocommerce_account_menu_items` (label: “Wishlist”, after
  Orders or Dashboard — confirm with design).
- Render callback: `woocommerce_account_wishlist_endpoint` — query table,
  render product grid reusing `parts/product-card.html` or a slim list template.
- Flush rewrite rules when option toggled (admin notice or activation hook).

Document chunk 5 in PROGRESS as follow-up within 3j or overlap with Phase 5
(My Account polish).

---

## What's already reusable

| Piece | Location | Wishlist usage |
|---|---|---|
| Card media corner layout | `parts/product-card.html`, `_card-media.scss` | Stack heart above quick view |
| JSX block pattern | `src-jsx-blocks/quick-view-button/` | Clone structure for `wishlist-button` |
| REST convention | `includes/rest-api/quick-view.php`, `class-api.php` | New `wishlist.php` |
| Delegated events | `src/js/shared/delegated-events.js` | Toggle without rebind after Load More |
| Programmatic buttons | `chairforce_get_buttons_markup()` | Optional for account page CTAs |
| Lucide icon system | Rule 16 / `button-icon-font.css` | `heart` glyph |

---

## File checklist (expected new / touched files)

| File | Purpose |
|---|---|
| `acf-json/group_theme_options.json` | WooCommerce tab + `wishlist_enabled`, `wishlist_loop_enabled` |
| `lib/class-wishlist.php` | DB, CRUD, endpoint registration, asset guards |
| `lib/class-init.php` or `class-woocommerce.php` | Register `Wishlist` |
| `includes/rest-api/wishlist.php` | REST routes |
| `lib/class-api.php` | `require_once` wishlist REST |
| `includes/helper-functions.php` | `chairforce_is_wishlist_*()` helpers |
| `src-jsx-blocks/wishlist-button/*` | Loop/single block |
| `parts/product-card.html` | Insert wishlist block |
| `templates/single-product.html` | Single-product control |
| `src/js/shared/wishlist.js` | Toggle + login redirect |
| `src/public.js` | Import wishlist module |
| `src/sass/woocommerce/_wishlist.scss` | Loop + single styles |
| `assets/css/button-icon-font.css` | `heart` icon rule |
| `src/js-admin/lucide-icon-options.js` | `heart` in curated list |
| `src/sass/icon-font/_variables.scss` | `heart` codepoint (if mixin needed) |

---

## Chunk breakdown

### Chunk 1 — Options + schema + class shell

- [ ] Add **WooCommerce** tab and two ACF fields to `group_theme_options.json`.
- [ ] Create `Chairforce\Wishlist` with `dbDelta` install + version option.
- [ ] Add `chairforce_is_wishlist_enabled()` / `chairforce_is_wishlist_loop_enabled()`.
- [ ] Register class in init; verify table exists via WP-CLI or phpMyAdmin.

### Chunk 2 — REST API

- [ ] `includes/rest-api/wishlist.php` — toggle, list, status routes.
- [ ] Login-only permissions; master-switch guard when `wishlist_enabled` off.
- [ ] Product validation (published, type `product`).
- [ ] Manual test via `ddev wp eval` or REST client as logged-in user.

### Chunk 3 — Product loop UI

- [ ] `wishlist-button` JSX block + build.
- [ ] Wire into `parts/product-card.html` above quick-view.
- [ ] Frontend JS + Sass; heart icon in Lucide set.
- [ ] Verify on shop archive + Load More append (delegated toggle).
- [ ] Verify logged-out click → login redirect, no cookie writes.

### Chunk 4 — Single product UI

- [ ] Add control to `single-product.html` (or shared block with context attr).
- [ ] Reuse REST/JS; style summary placement.
- [ ] Browser verify toggle persists on reload.

### Chunk 5 — My Account endpoint (deferred)

- [ ] Register `wishlist` WC endpoint + account menu item.
- [ ] Endpoint template — product list from table.
- [ ] Rewrite flush on enable/disable.
- [ ] Browser verify full add → account list → remove flow.

---

## Verification rule

**Done means verified in browser** (chunks 3–4 minimum before marking 3j-a
complete; chunk 5 when scheduled):

1. Logged-in user can add/remove from loop card and single product; state survives reload.
2. Logged-out user cannot persist wishlist; sensible login redirect.
3. With `wishlist_enabled` off in Theme Options, no controls render and REST returns 403.
4. With `wishlist_loop_enabled` off, loop hearts hidden but single-product still works.
5. Load More appended cards: wishlist toggle works without page reload.
6. No Woodmart tables or cookies touched.

---

## Out of scope

- Guest / cookie wishlist and login merge
- Multi-list / named wishlists / group popup
- Header wishlist icon or count badge
- Woodmart AJAX action compatibility
- Migration from `wp_woodmart_wishlist*`
- Email “share wishlist” (Woodmart admin feature)
- YITH Wishlist plugin integration
- Wishlist inside quick-view popup

---

## Relationship to roadmap

- **Was:** Phase 5 (My Account) in `context/PROGRESS.md` and file `11`.
- **Now:** **3j** — loop + single + API first; account endpoint UI is chunk 5
  (can ship later without blocking card/single work).
- **3h follow-up:** Heart slot in `cf-card-media` was intentionally left empty;
  3j chunk 3 fills it.

---

## After ship

- Update `context/PROGRESS.md` 3j row + mark chunks complete.
- Update `context/implementation/product-grid.md` (card partial now includes wishlist).
- Note in file `13` that rebuild superseded Woodmart (optional one-line addendum).
- Phase 5 My Account section: reference 3j chunk 5 instead of standalone wishlist rebuild.
