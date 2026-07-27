# 13 — Wishlist

Resolves QA question: *"Have we considered the Wishlist, how it currently
works and what we shall need to do to make sure our system uses the same
meta/option keys?"*

## What it is

**Woodmart's own native wishlist feature** — not a separate plugin. There
is no YITH Wishlist (or similar) plugin active on the live site; Woodmart
theme just ships a small compatibility stylesheet for YITH
(`int-woo-yith-wishlist.min.css`) in case a site *does* have it, which this
one doesn't need.

Source: `wp-content/themes/woodmart/inc/integrations/woocommerce/modules/wishlist/`
(`class-wc-wishlist.php`, `class-wishlist.php`, `class-wishlist-group.php`,
plus admin list-table and "send about wishlist" email classes). No child-theme
customization at all — `woodmart-child/functions.php` has zero
wishlist-related code.

## Data model — two dedicated custom DB tables (not post meta/options)

```
wp_woodmart_wishlists          (ID, user_id, wishlist_group, date_created)
wp_woodmart_wishlist_products  (ID, product_id, wishlist_id, date_added, on_sale)
```

- One row per wishlist in the first table (a user can have multiple named
  wishlists — `wishlist_group`, default `"My wishlist"`); one row per
  product-in-a-wishlist in the second, foreign-keyed by `wishlist_id`.
- `user_id` is a real (non-null) integer — tied to a logged-in WordPress
  user, not a session/cookie value, for the *stored* record. Guest/
  not-logged-in behavior is still supported client-side (Theme Setting
  `wishlist_logged` is **off** on this site, confirmed via
  `xts-woodmart-options`), but the actual persistence path is
  user-account-based — guests get a transient client-side wishlist
  (localStorage-style) that isn't in these tables until inspected further;
  not fully traced in this pass since it's not relevant given the data
  volume below.

## Live data volume — effectively unused, zero migration risk

```
wp_woodmart_wishlists:          3 rows (3 different real users, all "My wishlist")
wp_woodmart_wishlist_products:  0 rows
```

**There is no real wishlist data to preserve.** Three users have an empty,
auto-created wishlist shell and nothing has ever been added to one. This
removes essentially all migration pressure from this feature — there's
nothing meaningful to read/carry over.

## Rebuild recommendation

Since there's no legacy data to honor, there's no reason to replicate
Woodmart's exact table schema/names. Build wishlist the way that best fits
this repo's own conventions instead:

- Simplest option: a small custom table (or plain user meta storing an
  array of product IDs, if multi-list support isn't needed) — either is
  fine given zero existing rows to reconcile.
- If multiple named wishlists per user is a requirement, keep the
  two-table shape (wishlist header + wishlist-product join) since it's a
  clean, proven design — just don't feel obligated to reuse Woodmart's
  literal table names.
- Decide fresh (not dictated by legacy data) whether guests should be able
  to wishlist without an account — Woodmart supports this today
  (`wishlist_logged` off), so if that UX matters, plan for a
  cookie/localStorage-backed guest wishlist that merges into the account
  table on login, same as most modern wishlist implementations.
- No AJAX endpoint compatibility is needed (no other system calls
  Woodmart's `wp_ajax_woodmart_*` wishlist actions) — build your own
  endpoints freely.
