# Product grid — implementation tracker

Short reference for how **Quick View**, **Swatches**, and **Load More** land on Product Collection cards. Detailed plans live under `context/plans/` and `context/notes/`.

---

## Shared card markup

Canonical card lives in **`parts/product-card.html`** (`cf-card-media`, swatches, title, price, button).

| Template part | Uses `product-card` | Collection type |
|---|---|---|
| `parts/product-collection.html` | Yes (inside `woocommerce/product-template`) | Archive — `inherit: true` + Load More pagination |
| `parts/product-upsells.html` | Yes | Single product — `woocommerce/product-collection/upsells` |
| `parts/product-related.html` | Yes | Single product — `woocommerce/product-collection/related` |

**Templates:** `archive-product.html` → `product-collection` · `templates/single-product.html` → `product-upsells` + `product-related`

**DB overrides:** none at time of writing (0 `wp_template` / `wp_template_part` posts).

---

## Quick View

**Explicit block in `product-card.html`** — `<!-- wp:chairforce/quick-view-button /-->` inside `cf-card-media` (sibling after `woocommerce/product-image`).

| Piece | Location |
|---|---|
| Block render | `src-jsx-blocks/quick-view-button/render.php` — reads `postId` from block context |
| Popup content | REST `GET /wp-json/chairforce/v1/quick-view/{id}` — `includes/rest-api/quick-view.php` |
| Frontend | `src/js/quick-view.js` — delegated click on `.cf-quick-view-trigger`, reads `data-product-id` |
| Assets | `WooCommerce_Archive::enqueue_quick_view_assets()` — WC variation/single-product scripts on shop/single |
| Styles | `src/sass/quick-view/` — trigger absolute over image; `cf-card-media` is `position: relative` |

Archive, upsells, related, and Load More all get the trigger via the shared `product-card` partial (no `render_block` injection).

---

## Swatches

**Explicit block in `product-card.html`** — `<!-- wp:chairforce/product-swatches /-->` inside `cf-wrapp-swatches`.

| Piece | Location |
|---|---|
| Block render | `src-jsx-blocks/product-swatches/render.php` → `Product_Swatches::render_grid_swatches()` |
| PHP class | `lib/class-product-swatches.php` |
| Grid interactions | `src/js/single-product-swatches.js` — delegated hover/click, image swap |
| Context | Block uses `postId` from `woocommerce/product-template` ancestor |

Unlike Quick View, swatches are authored in the template, not injected.

---

## Load More

**Custom REST append** — Query Loop Load More plugin is incompatible with `woocommerce/product-collection` (see `context/notes/load-more-findings.md`).

| Piece | Location |
|---|---|
| REST | `GET /wp-json/chairforce/v1/load-more` — `includes/rest-api/load-more.php` |
| Query helpers | `includes/load-more-functions.php` |
| Button | `lib/class-load-more.php` — replaces page-1 pagination when `loadMore: true` on `core/query-pagination` |
| Frontend | `src/js/shared/load-more.js` — `.cf-load-more__button`, appends server-rendered `<li>` HTML |
| Config | `Chairforce_Public.loadMoreRestUrl` in `lib/class-front.php` |

**Scope (v1):** shop / category / attribute archives only — inherited Product Collection (`inherit: true`). Not related/upsell grids.

### Card render path (Option A)

Load More reads **`parts/product-card.html` directly** — not `product-collection.html`.

```
product-card.html
  → parse_blocks() (root blocks)
  → synthetic core/null wrapper
  → WP_Block->render() per product (postId context + global $post)
  → wrap in <li class="wc-block-product">
  → append to .wc-block-product-template
```

Function: `chairforce_get_product_card_template_parsed_block()` in `includes/load-more-functions.php`.

Previously parsed `product-collection.html` and searched for `woocommerce/product-template` → `template-part product-card`. That indirection was removed once `product-card.html` became the shared partial.

Appended cards rely on **delegated events** (`src/js/shared/delegated-events.js`) so Quick View + swatches work without rebind.

---

## Single product grids

**Fixed:** upsells and related now use block Product Collections with the same `product-card` partial.

| Grid | Template part | Legacy hook |
|---|---|---|
| Upsells (“You may also like…”) | `parts/product-upsells.html` | Removed — `WooCommerce_Single_Product::remove_legacy_upsell_grid()` |
| Related products | `parts/product-related.html` | WC default pattern replaced |

Template: `templates/single-product.html` (theme file, not DB).

---

## Card composition summary

| Feature | In template? | How it appears on each card |
|---|---|---|
| Card layout | Yes | `parts/product-card.html` (shared partial) |
| Swatches | Yes | `chairforce/product-swatches` block in card partial |
| Quick View | Yes | `chairforce/quick-view-button` block in `product-card.html` |
| Load More | N/A (pagination) | REST replays query; renders `product-card.html` per product |
