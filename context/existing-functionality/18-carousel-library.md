# 18 — Carousel Library Decision

Resolves QA question: *"We shall need to decide upon a carousel library as
it's being used in many places... it has to be something which can work
with the existing markup and turn the grid into a carousel. Example: the
Product gallery on single product pages is carousel-based."*

## What's actually used today — one library, everywhere, no exceptions

Confirmed by direct inspection of every JS file under
`wp-content/themes/woodmart/js/scripts/`: **Swiper** is the only carousel
library referenced anywhere in the theme. No Slick, Owl Carousel,
Flickity, Tiny Slider, or any other library exists in this codebase.
`woodmart_enqueue_js_library('swiper')` is the single enqueue point; every
carousel-like feature site-wide — including the single-product image
gallery (file `04`'s "Swiper carousel mode", `productImagesGallery.js`) —
goes through it.

## How Woodmart makes "any grid → a carousel" work with one shared component

`wp-content/themes/woodmart/js/scripts/global/swiperInit.js` is a single,
generic initializer, **not** a bespoke per-feature carousel setup:

```js
document.querySelectorAll('.wd-carousel-container > .wd-carousel-inner > .wd-carousel:not(.scroll-init)')
  .forEach(function (carousel) {
    woodmartThemeModule.swiperInit(carousel);
  });
```

- Any markup that follows the `.wd-carousel-container > .wd-carousel-inner
  > .wd-carousel` structure automatically becomes a Swiper instance — the
  *same* function handles product grids-turned-carousel, testimonials,
  category sliders, banner carousels, the "frequently bought together"
  row, and the single-product image gallery. There's no separate
  "shop grid carousel" vs. "gallery carousel" code path.
- **Configuration is read from `data-*` attributes on the `.wd-carousel`
  element itself** (slides-per-view, breakpoints, loop, autoplay, etc.) —
  the PHP template/widget that renders a given carousel just needs to
  output the right `data-*` attributes; `swiperInit()` itself is
  feature-agnostic.
- **Lazy/on-scroll init**: elements marked `.scroll-init` are wrapped in an
  `IntersectionObserver` instead of initializing immediately — carousels
  far down the page don't pay Swiper's setup cost until they're about to
  be visible.
- **Idempotent, event-driven re-init** (see file `17`'s pattern in
  general): a `.wd-initialized` class flag guards against double-init, and
  `carouselsInit()` is designed to be safely re-called after AJAX content
  loads (`wdShopPageInit`, `wdLoadMoreLoadProducts`, `wdQuickViewOpen300`,
  Elementor's own `frontend/element_ready/*` hooks for a long list of
  widget types) rather than needing bespoke re-init logic per feature.

## Recommendation for the new theme: keep Swiper, keep the same declarative pattern

1. **Use Swiper** (https://swiperjs.com) — it's already the site's
   established vocabulary (matches the "existing markup" requirement
   directly, since any migrated/rebuilt markup can keep using
   Swiper-compatible slide structure), it's actively maintained, has no
   jQuery dependency (unlike Woodmart's usage, which wraps it in jQuery
   glue — the new theme can use it directly against vanilla DOM or via its
   official React wrapper, `swiper/react`, which fits this repo's
   JSX-block-first architecture well).
2. **Build one shared carousel component/mixin**, not one per feature —
   directly mirroring `swiperInit.js`'s role. A single React component
   (e.g. `<Carousel>` taking children + a config object) that any block
   (product grid, testimonials, category slider, single-product gallery)
   can wrap its markup in, rather than writing Swiper setup code
   redundantly in five different JSX blocks.
3. **Keep the "grid vs. carousel" decision purely presentational** — the
   same underlying grid of cards should be able to render as a static CSS
   grid *or* inside the shared `<Carousel>` wrapper depending on a
   block/section setting, exactly as Woodmart's own `.wd-carousel-container`
   wrapper pattern allows any grid section to become "carousel mode" via
   markup/config alone, no separate implementation.
4. **Apply file `17`'s idempotent-init rule** to this component too — if a
   carousel's slide content is ever appended to after the fact (e.g. an
   infinite-scroll gallery per file `15`), the component must tolerate
   re-init/update calls rather than assuming it's only ever mounted once
   against final content.
