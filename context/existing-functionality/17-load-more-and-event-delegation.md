# 17 — "Load More" / Infinite Scroll Compatibility & Event Delegation

Resolves QA question: *"There is a 'Load More' functionality as well, so
the build we make should make sure it's compatible with load more, and
not adding event handlers after the first load, instead detecting the
events even for the 'Load More' items."*

**Good news: this is exactly how Woodmart itself is built already** — the
whole swatch/carousel/quick-shop system is designed around this problem
from the ground up, using one consistent pattern. The rebuild just needs
to follow the same pattern, not invent a new one.

## Where "Load More" exists on this site

`wp-content/themes/woodmart/js/scripts/wc/productsLoadMore.js` handles
**three** distinct pagination UIs on any WooCommerce product grid
(shop/category archives, and Elementor "Products"/"Archive Products"
widgets):

1. `.wd-products-load-more` — a plain "Load More" button that **appends**
   new product cards to the existing grid (or `.isotope('appended', ...)`
   if the grid uses masonry layout).
2. `.wd-ajax-arrows` — prev/next arrow buttons that **replace** the
   current page's worth of cards (used for smaller "products carousel by
   page" style sections, not the main shop grid).
3. `woodmartThemeModule.clickOnScrollButton(...)` — an infinite-scroll
   variant: automatically "clicks" the load-more button once the user
   scrolls within `woodmart_settings.infinit_scroll_offset` pixels of it
   (Theme Setting-driven; can be disabled in favor of the plain button).

All three go through the same underlying AJAX call
(`woodmart_get_products_{source}`, e.g. `_main_loop`/`_shortcode`) with a
`paged` counter, and update the grid's `data-paged` attribute afterward so
a repeat click continues from the right page.

## The one rule that makes this all just work: **never bind directly to card elements**

Every single interactive script this research has documented —
`swatchesOnGrid.js`, `quickShopVariationForm.js`, `swatchesVariations.js`,
`imagesGalleryInLoop.js`, `swatchesLimit.js`, the "Load More" handler
itself — uses **jQuery event delegation on `document`** (or another
stable, never-replaced ancestor), not a direct `.on('click', ...)` bound
to each card at page-load time:

```js
// The pattern used everywhere, confirmed by direct inspection of the
// relevant scripts (this is the actual source, not paraphrased):

// productsLoadMore.js
woodmartThemeModule.$document
  .off('click', '.wd-products-load-more')
  .on('click', '.wd-products-load-more', function(e) { /* ... */ });

// quickShopVariationForm.js
woodmartThemeModule.$document
  .on('mouseenter touchstart mousemove', '.wd-product.product-type-variable', function() { /* lazy per-card init, see below */ });

// swatchesOnGrid.js / swatchesLimit.js — same delegated-on-document pattern
```

Because the handler is registered on `document` with a selector string
(not on the matched elements themselves), **it automatically applies to
any element matching that selector added to the DOM later** — including
every card appended by Load More/infinite scroll/AJAX filtering — with
zero extra code. There is no "re-bind handlers after Load More" step
anywhere in this codebase, because delegation makes that step
unnecessary by construction.

## The second piece: idempotent, class-flag-guarded lazy init (for the few things that DO need real per-element setup)

A couple of features genuinely need one-time, real work per card/element
(not just an event listener) — e.g. `.wc_variation_form()` needs to run
once per quick-shop form, Swiper needs a real `new Swiper(...)` call once
per carousel. These use a **"guard flag + custom event" pattern** instead
of delegation:

```js
// quickShopVariationForm.js — real init happens once, on first interaction,
// guarded by a class so a second mouseenter/click is a no-op:
if (!$form.length || $form.hasClass('wd-variations-inited') || ...) {
    return;
}
...
$form.addClass('wd-variations-inited');
$form.wc_variation_form();

// swiperInit.js — same idea, but triggered by custom events fired after
// AJAX content loads, rather than by a DOM interaction:
woodmartThemeModule.$document.on(
  'wdInstagramAjaxSuccess wdLoadDropdownsSuccess wdProductsTabsLoaded ' +
  'wdSearchFullScreenContentLoaded wdShopPageInit wdRecentlyViewedProductLoaded ' +
  'wdQuickViewOpen300',
  function() { woodmartThemeModule.carouselsInit(); }
);
woodmartThemeModule.carouselsInit = function() {
  document.querySelectorAll('.wd-carousel:not(.wd-initialized)').forEach(...);
  // .wd-initialized flag prevents double-init if this runs again
};
```

`productsLoadMore.js` fires `wdLoadMoreLoadProducts` (and
`wdArrowsLoadProducts`) after appending new cards specifically so this
second category of script knows to scan the *newly added* DOM for
not-yet-initialized carousels/forms — `carouselsInit()` re-running is cheap
because of the `.wd-initialized`/`.wd-variations-inited` guard, so calling
it again after every Load More is safe and correct rather than wasteful.

## Rebuild checklist — two rules, apply both, everywhere

1. **All click/hover/keyboard interaction handlers for repeatable card/grid
   elements must be delegated** — bind once, on a stable container (or
   `document`), with a CSS-selector string identifying the target, never
   bound directly to the elements that Load More/AJAX/filtering will
   create copies of later. If using React/JSX components (this repo's
   preferred block approach), the equivalent is: don't rely on
   component-mount-time `addEventListener` calls scoped to a ref that only
   exists for the *first* render's elements — either use React's own
   synthetic event system (which is delegation-based by default, so this
   is largely free if building with React components per card) or
   explicitly delegate at a parent level if mixing with vanilla JS/legacy
   markup.
2. **Anything requiring real one-time setup work (carousel instantiation,
   3rd-party widget init, non-delegatable library calls) must be
   idempotent and re-triggerable via a shared custom event**, guarded by a
   flag/attribute so repeat calls are cheap no-ops. Fire that event after
   every AJAX append (Load More, infinite scroll, filter re-query, Quick
   View open — file `16`) rather than writing separate "restart
   everything" logic per feature.

Following just these two rules is what let Woodmart avoid ever writing
"re-attach handlers to new products" code anywhere in this codebase — the
same discipline in the rebuild removes an entire category of "works on
page 1, breaks on page 2" bugs before they can happen.
