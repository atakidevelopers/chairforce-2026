# 3e — Single-Product Swatches + Gallery Swap — Implementation Plan

## Status: ⏳ Not started

## Goal

Execute the next roadmap item from `context/PROGRESS.md`:

- **3e** — color swatches on the single-product variation form, proxying
  into WooCommerce's real (hidden) `<select>`, plus swapping the whole main
  image gallery (not just one photo) when a variation with its own
  "additional variation images" is selected.

**Source of truth for behavior/data:** `context/existing-functionality/04-single-product-page-swatches-and-gallery.md`
(full behavioral spec), `09-rebuild-considerations.md` §0.1/§1/§2B/§3
(confirmed live settings + hardcode guidance), `05-css-styling-reference.md`
(single-product uses the *same* style-3/dis-style-3/round combo as the grid,
just a larger absolute size scale), `07-child-theme-overrides.md` §1
(confirms hover-to-click is **grid-only**, single-product stays click-based).
Hand the agent doing this work those files directly, not a paraphrase.

## Scope decision — read this before starting

### What's already shipped and reusable

- **Data + admin UI already registered (3a):** `wd_additional_variation_images_data`
  (CSV of attachment IDs, per `product_variation` post) with a working admin
  UI in `lib/class-woocommerce-admin.php` (`render_variation_gallery_field()`
  / `save_variation_gallery_field()`). This plan only adds the **read/frontend**
  side — no new admin work, no data migration.
- **Term swatch data + admin UI already registered (3c):** `color`/`image`
  term meta on `pa_colour` (+ 12 other taxonomies), ACF field group
  `group_pa_colour_swatch_fields`.
- **Swatch resolution logic already written (3d):** `Chairforce\Product_Swatches`
  (`lib/class-product-swatches.php`) has `has_swatch()` (color → image → text
  priority per term) and `get_option_variations()` (maps terms → variation
  id/stock/image) — both **generic across any attribute**, not grid-specific.
  This plan extends the same class with a single-product-specific render
  method rather than duplicating the term-meta-reading logic.
- **Event delegation convention (3b):** `src/js/shared/delegated-events.js`.
  Swatch clicks reuse this; the three WooCommerce-native jQuery events this
  plan also needs (`found_variation`/`show_variation`/`reset_data`) are a
  deliberate, scoped exception — see the JS architecture note below.
- **Frontend scaffold waiting for this work:** `lib/class-woocommerce-single-product.php`
  (currently an empty `register_hooks()`).

### A finding that changes this chunk's shape: no template override needed

No `woocommerce/single-product/add-to-cart/variable.php` override is
required. WooCommerce core's own `wc_dropdown_variation_attribute_options()`
(the function that renders each attribute's real `<select>`) already runs
its returned HTML through a filter:

```php
echo apply_filters( 'woocommerce_dropdown_variation_attribute_options_html', $html, $args );
```

`$args` includes `attribute`, `product`, `options`, `selected`, `id`, `name`
— everything needed to resolve and render swatches for that one attribute
row. **Prepending swatch markup to `$html` inside this filter** (returning
`$swatches_markup . $html`) reproduces Woodmart's exact "swatches
immediately followed by the real, now-hidden `<select>`" DOM relationship
(file `04` point 3) with zero template copying — future WooCommerce core
updates to `variable.php` can't drift out of sync with a copied template.

This works regardless of *where* the form renders — normal single-product
page, and (later, 3h) Quick View — since it's a filter on a core function,
not a page-specific template. Confirmed this theme currently uses
WooCommerce's default blockified `single-product.html`
(`<!-- wp:woocommerce/add-to-cart-form /-->` + `<!-- wp:woocommerce/product-image-gallery /-->`,
no theme override exists yet) — the classic `variable.php` template still
renders underneath both of those blocks (`AddToCartForm::render()` calls
`do_action('woocommerce_variable_add_to_cart')`, WooCommerce core's own
hook for exactly this), so the filter fires in the actual live markup, not
just Elementor/classic-theme contexts.

**Per-attribute, not hardcoded to `pa_colour`:** the filter checks *every*
attribute row via the same `Product_Swatches::has_swatch()` used by the
grid — if none of an attribute's terms have `color`/`image` set, the row
falls through untouched (plain `<select>`, no swatches). This matches
Woodmart's own per-row behavior and needs no separate "which attribute"
setting (unlike the grid's single `grid_swatches_attribute`, single-product
shows swatches for *every* qualifying attribute row — file `04` point 1).
In practice, today's data means this only ever fires for `pa_colour` (file
`02`'s confirmed scope), but the code doesn't need to special-case that.

### A second finding, already discussed and decided: don't adopt WooCommerce's own "Variation Gallery" feature (yet)

This installed WooCommerce version (10.9.x) ships a **native, first-party**
per-variation gallery feature (`Automattic\WooCommerce\Internal\VariationGallery\Package`,
admin UI included) — but it's an explicit **canary/experimental toggle**
(`Settings → Advanced → Features`, `variation_gallery`), **off by default**
("Defaults to off for the 10.9 canary period" — core's own comment), stores
data under its own meta key (`_product_image_gallery`), and its own
one-time legacy-migration only targets a different, already-confirmed-dead
meta key (`_wc_additional_variation_images` — file `02` §4d, zero rows on
this site), not our real data.

**Decision (confirmed):** keep our own system for now — read
`wd_additional_variation_images_data` directly, write our own
`woocommerce_available_variation` filter, don't touch the canary feature
flag or its meta key. Revisit only if/when that core feature graduates out
of canary and proves reliable — at that point a BatchPress job (same
pattern as 3c) could migrate `wd_additional_variation_images_data` →
`_product_image_gallery` and this plan's PHP could be deleted in favor of
core's own. Documented as a deferred future option, not acted on now.

One piece of that feature *is* safe and worth reusing on its own merits: the
general-purpose helper function `wc_get_product_gallery_html( $product,
$image_ids )` (added in the same WC release, but **not** gated behind the
canary flag — it's a plain global function, used unconditionally elsewhere
in core) renders the theme's own `single-product/product-image.php`
gallery template for an arbitrary list of image IDs. Using this instead of
hand-building gallery HTML strings means our gallery-swap markup is
*guaranteed* identical to the page's own default gallery markup (same
`data-thumb`/`data-large_image`/etc. attributes), because it's the exact
same template function. This is orthogonal to the canary feature — it's
just a well-designed core utility we get to use immediately.

### A third finding, which removes the need for a Swiper build in this phase

`PROGRESS.md`'s open-decisions list deferred "the shared Swiper carousel
component" to 3e ("that's the first real consumer"). Checking the actual
block in use (`woocommerce/product-image-gallery` →
`ProductImageGallery::render()` → core's `woocommerce_show_product_images()`)
shows the rendered gallery is WooCommerce's own classic
`.woocommerce-product-gallery` markup, which already has a **complete,
free, built-in carousel/zoom/lightbox story** gated entirely behind three
`add_theme_support()` flags this theme hasn't declared yet:

| Theme support | Enables |
|---|---|
| `wc-product-gallery-slider` | `wc-flexslider` — main-image slider + thumbnail rail, reading each gallery item's `data-thumb` (already present in core's own markup) |
| `wc-product-gallery-zoom` | Hover-zoom on the main image |
| `wc-product-gallery-lightbox` | PhotoSwipe click-to-enlarge |

And core exposes a public jQuery plugin, `$(...).wc_product_gallery()`
(`assets/js/frontend/single-product.js`), specifically so gallery markup can
be **rebuilt and re-initialized** after the fact — exactly the operation a
variation-driven gallery swap needs (destroy stale flexslider/zoom bindings
against replaced DOM, rebuild against the new DOM). **No custom carousel
code is needed for this specific feature.** Swiper remains genuinely
deferred — its first real consumer is still in the future (testimonials,
category sliders, etc., whenever that need arises), not this phase.

### What's explicitly out of scope for this plan

- **Quick View (3h)** — depends on this work existing first, but its own
  popup wiring (`wdQuickViewOpen`-equivalent event, reusing this same
  module against a second `.variations_form`) is a separate phase. This
  plan's JS should be written so it *can* be re-triggered against a
  dynamically-inserted form later (delegated click handlers, no
  page-load-only assumptions) without predicting Quick View's exact markup
  now.
- **"Parts" second swatch system (3g)** — a fully separate, independently-
  scoped `.variations_form` per related part product (file `10` §6 /
  file `07` §6). Not this plan's concern; per file `09` §2B, a from-scratch
  rebuild should give each Parts sub-form its own scoped instance of
  whatever this plan builds rather than needing the child theme's
  `MutationObserver` gallery-protection hack (two independent forms
  targeting two independent image elements don't need protecting from each
  other).
- **Shop/category filter sidebar (3f)** — unrelated feature, same term meta,
  different UI.
- **"Scroll to gallery on select" / "show selected option as inline text
  label" (file `04` point 4, Theme Settings `swatches_scroll_top_*`/
  `swatches_labels_name`)** — Woodmart admin-configurable extras with no
  live-confirmed value captured in this research. Per file `09` §3's
  "hardcode one combination, don't rebuild the admin matrix" rule: **both
  default to off/not-built** in this pass. Easy, cheap follow-ups on
  request (like the grid's +N limit) — flag to the client, don't block on
  them.
- **Keyboard/ARIA `role="radiogroup"`/`role="radio"`** (file `04` point 2's
  Woodmart markup) — the grid swatches shipped in 3d use plain
  `<button type="button">` elements (native keyboard/Enter/Space semantics
  for free, no manual `aria-checked` bookkeeping). This plan reuses the same
  `<button>` convention for single-product swatches rather than introducing
  ARIA radio-role management as a new pattern.

## Architecture decisions

1. **Swatch markup injection point:** `woocommerce_dropdown_variation_attribute_options_html`
   filter (see finding above) — no template override.
2. **Real `<select>` stays the source of truth.** Swatch click/hover
   (see below) sets `.value` on the matching real `<select id="{id}">` and
   fires `change` — WooCommerce's own `wc-add-to-cart-variation.js` does all
   variation-matching, stock/price updates, and `found_variation`/
   `show_variation`/`reset_data` dispatch, exactly as Woodmart proxies into
   it (file `09` §2B's "single most important architectural lesson").
   New Sass rule hides the sibling select once swatches render
   (`.cf-swatches-single + select { display: none; }`), mirroring file
   `05`'s `.wd-swatches-product + select` rule — **this is the one CSS rule
   the 3d plan explicitly noted does *not* apply to grid swatches; it
   belongs here.**
3. **Interaction is click (and keyboard), not hover.** File `07` §1
   confirms the live hover-to-click child-theme customization only targets
   `.product-grid-item`/`.product-teaser` swatches — single-product
   swatches keep Woodmart's own stock click behavior. Don't carry 3d's
   hover pattern into this phase.
4. **Gallery swap data contract — new, theme-owned keys** (deliberately
   *not* reusing Woodmart's `additional_variation_images`/`_default` key
   names, and *not* reusing WC core's canary `gallery_image_ids`/
   `gallery_images_html` key names, to avoid any future collision if that
   canary feature is ever turned on):
   - New filter on `woocommerce_available_variation` adds one key,
     `cf_variation_gallery_html` — present **only** when the variation's
     resolved image set (its own featured image, if set, plus
     `wd_additional_variation_images_data`'s attachment IDs) has **more
     than one image** (mirrors file `04` point 2's "only rebuild the
     gallery if there's more than one associated image; otherwise let
     WooCommerce's cheaper single-image swap handle it"). Value is the
     output of `wc_get_product_gallery_html( $product, $image_ids )`
     (core helper, see finding above) — ready-to-inject HTML matching the
     page's own default gallery markup exactly.
   - **No separate "default" key needed from PHP** (simplification vs.
     Woodmart's two-key approach): the frontend JS caches the *page's own*
     initial `.woocommerce-product-gallery__wrapper` HTML once on load,
     before any swap — that's already "the default", no need for PHP to
     recompute it.
5. **JS lives in the main frontend bundle**, not a JSX block — single-
   product swatches are a server-rendered-HTML enhancement of WooCommerce's
   own classic form markup (like `variation-gallery-admin.js` is for the
   admin side), not a block with its own editor UI. New module:
   `src/js/single-product-swatches.js`, imported from `src/js/index.js`
   (same convention as `site-header.js`/`product-search.js`), guarded to
   no-op when no `.variations_form` is present on the page.
6. **Scoped jQuery exception, documented inline.** `found_variation`/
   `show_variation`/`reset_data` are jQuery-only custom events dispatched
   by WooCommerce core's own bundled `add-to-cart-variation.js`
   (`$form.trigger('found_variation', [...])`, confirmed by reading that
   file) — a plain `document.addEventListener` cannot observe them; there
   is no vanilla-DOM equivalent to delegate onto. `import jQuery from
   'jquery';` at the top of the new module is the one deliberate exception
   to this theme's otherwise-vanilla frontend JS — `@wordpress/scripts`'
   dependency-extraction webpack plugin (already part of this theme's
   build, confirmed via `lib/class-front.php` reading `public.asset.php`'s
   dependency array dynamically) automatically maps this import to the
   `jquery` script handle; no manual PHP enqueue changes needed. Swatch
   click handling itself still uses the theme's own vanilla
   `delegateDocument()` from `src/js/shared/delegated-events.js` — only the
   three WC-native event listeners need jQuery.
7. **Theme support flags** (`wc-product-gallery-slider`/`-zoom`/`-lightbox`)
   added in `lib/class-after-setup-theme.php::setup_theme()` — gives the
   free flexslider/zoom/lightbox story from the finding above.

## Chunk breakdown

### Chunk 1 — PHP: single-product swatch markup

- Extend `Chairforce\Product_Swatches` (`lib/class-product-swatches.php`)
  with a new method, e.g. `render_single_product_swatches( \WC_Product $product, string $attribute_name, array $args ): string`
  — reuses `has_swatch()` per term (same color → image → text priority as
  the grid), returns `''` if no term of this attribute has swatch data
  (row falls through to a plain `<select>`).
- Wire it up in `Chairforce\WooCommerce_Single_Product`
  (`lib/class-woocommerce-single-product.php`, currently an empty
  scaffold): hook `woocommerce_dropdown_variation_attribute_options_html`,
  read `$args['attribute']`/`$args['product']`/`$args['options']`/
  `$args['selected']`, call the new render method, return
  `$swatches_markup . $html` (prepend, not replace — the real `<select>`
  must still render, just get hidden by CSS per architecture decision #2).
- Wrapper classes: `cf-swatches-product cf-swatches-single cf-swatches--style-3
  cf-swatches--dis-style-3 cf-swatches--size-single cf-swatches--shape-round`
  — reuses the exact same style-3/dis-style-3/shape-round Sass mixins 3d
  already shipped (they're written generically against `.cf-swatches--style-3`
  etc., not scoped to `.cf-swatches-grid` — confirmed by reading
  `src/sass/swatches/_style-3.scss`); only the size variant is new (chunk 4).
- Mark the currently-selected term active (`cf-swatch--active`) from
  `$args['selected']`, same source WooCommerce's own dropdown function
  already uses (`$_REQUEST['attribute_{name}']` / variation default attrs).
- Disabled state (`cf-swatch--out-of-stock`) is **not** computed here — it's
  synced client-side after every attribute change (chunk 3), matching
  Woodmart's own `resetSwatches()` behavior (file `04`'s JS section) of
  reading WooCommerce's own per-`<option>` `enabled`/invalid-combination
  class rather than recomputing eligibility server-side on initial render
  only.

### Chunk 2 — PHP: gallery data exposure + theme support flags

- In `Chairforce\WooCommerce_Single_Product`, hook `woocommerce_available_variation`
  (10, 2 — `$data`, `$variation`) to add `cf_variation_gallery_html` (see
  architecture decision #4). Read `wd_additional_variation_images_data`
  from the variation post meta (reuse the same CSV-parsing helper pattern
  already in `lib/class-woocommerce-admin.php::get_variation_gallery_attachment_ids()`
  — consider promoting that private method to a small shared static helper
  rather than duplicating the `explode(',', ...)` + `array_filter(absint)`
  logic a second time).
- Build the image ID list as: variation's own featured image (if set) +
  the CSV attachment IDs, deduped; only proceed (only set the key at all)
  if the resulting list has **2 or more** images.
- Add the three `add_theme_support()` calls (`wc-product-gallery-slider`,
  `wc-product-gallery-zoom`, `wc-product-gallery-lightbox`) to
  `lib/class-after-setup-theme.php::setup_theme()`.

### Chunk 3 — JS: proxy click + gallery swap/reset

New `src/js/single-product-swatches.js`, imported from `src/js/index.js`:

- **Swatch → select proxy** (vanilla, delegated per the 3b convention):
  `delegateDocument('click', '.cf-swatches-single .cf-swatch', ...)` finds
  the swatch's `data-id`-matching real `<select>` in the same
  `.variations_form`, sets `.value` to the swatch's `data-value` (term
  slug), dispatches a native `change` event (jQuery listens to native
  `change` events fine — no jQuery needed for *this* half). Toggle
  `cf-swatch--active` within the swatch's own group. Keyboard: buttons get
  this for free (Enter/Space triggers `click`) — no extra keydown handler
  needed (this is why chunk 1 uses real `<button>`s, consistent with the
  "no ARIA radiogroup" scope decision above).
- **WC-native event listeners** (the one jQuery exception, per
  architecture decision #6), bound via `jQuery(document).on(event,
  '.variations_form', handler)` (delegated, so it's Load-More/Quick-View-
  safe too, per the 3b convention applied to this jQuery-event case):
  - **On page load / module init:** cache each `.variations_form`'s
    `.woocommerce-product-gallery`'s current `.woocommerce-product-gallery__wrapper`
    outerHTML into a `data-cf-default-gallery` attribute (or a `WeakMap`
    keyed by the form element) — "the default", per architecture decision
    #4's simplification.
  - **`found_variation`:** if `variation.cf_variation_gallery_html` is
    present, replace the current `.woocommerce-product-gallery__wrapper`
    with it, then re-run `jQuery('.woocommerce-product-gallery').wc_product_gallery()`
    (WooCommerce core's own public re-init plugin — confirmed present at
    `assets/js/frontend/single-product.js`'s `$.fn.wc_product_gallery`) to
    rebuild flexslider/zoom/photoswipe bindings against the new DOM, then
    trigger a `resize` event (matches Woodmart's own pattern for its Swiper
    equivalent). If the key is absent, do nothing — WooCommerce's own
    default single-image swap already handles that case with zero extra
    code from this plan.
  - **`reset_data`:** if the gallery was previously swapped away from the
    cached default, restore the cached default HTML and re-run
    `wc_product_gallery()` + `resize` the same way.
  - **`show_variation`/`hide_variation` (or after `change`, whichever core
    event reliably fires once WooCommerce recomputes option availability):**
    walk every `<option>` in each attribute's real `<select>`; for each
    swatch, toggle `cf-swatch--out-of-stock` based on whether the matching
    `<option>` currently carries WooCommerce's own `enabled` class — this is
    the disabled/enabled sync deferred from chunk 1, and must re-run on
    *every* attribute change (not just once), exactly like Woodmart's
    `resetSwatches()`.
- **Guard clause:** if the page has no `.variations_form`, the module's
  init function returns immediately — safe to import unconditionally into
  the sitewide bundle.

### Chunk 4 — Sass + manual verification

- **Sass:** add a `.cf-swatches-single` size variant to
  `src/sass/swatches/_variables.scss` (larger absolute `--cf-swatch-size`/
  `--cf-swatch-text-size`/gap values than `.cf-swatches-grid`'s, per file
  `05`'s documented grid-vs-single-product density difference — grid and
  single "size M" are *not* the same pixel values). Add the
  `.cf-swatches-single + select { display: none; }` rule (architecture
  decision #2). Confirm `.cf-loading-image` (already shipped, currently
  scoped to grid card image selectors) either gets a single-product-scoped
  sibling rule or is left grid-only with a simpler opacity toggle written
  directly for the gallery wrapper — implementer's call, small either way.
- **Verification, against real data** (per this repo's established
  pattern — test against a genuine variable `pa_colour` product, not the
  first one clicked):
  1. Load a variable `pa_colour` product's single page; confirm swatches
     render above/beside the hidden real `<select>`, one swatch per
     `pa_colour` term on that product, color/image/text priority correct.
  2. Click a swatch with **no** `wd_additional_variation_images_data` set
     on its variation → confirm WooCommerce's own default single-image
     swap still happens (this plan adds nothing for this case — just
     confirm nothing broke it).
  3. Click a swatch **with** `wd_additional_variation_images_data` set
     (2+ images) → confirm the whole gallery/thumbnail rail rebuilds,
     flexslider/zoom/lightbox all still work against the new images.
  4. Click "Clear" (`reset_variations`) → confirm the gallery reverts to
     the product's own default images, not stuck on the last variation's.
  5. Select an out-of-stock/invalid combination → confirm the matching
     swatch visually reflects disabled style 3 and stays in sync as other
     attributes change.
  6. Screenshot before/after for the PM/client record, same as 3d.

## Related files

- `context/existing-functionality/04-single-product-page-swatches-and-gallery.md` —
  full behavioral spec this plan implements
- `context/existing-functionality/02-data-model-and-storage.md` §4 —
  `wd_additional_variation_images_data` data contract (4a), the confirmed-
  dead/orphaned sibling keys (4c/4d/4e) to *not* reproduce
- `context/existing-functionality/05-css-styling-reference.md` — styling
  reference, single-product vs. grid size-scale difference
- `context/existing-functionality/07-child-theme-overrides.md` §1 — confirms
  hover-to-click is grid-only, not single-product
- `context/existing-functionality/09-rebuild-considerations.md` §0.1, §1,
  §2B, §3 — confirmed live settings, proxy-into-real-select architecture
  guidance, hardcode-one-style-combo guidance
- `context/existing-functionality/18-carousel-library.md` — Swiper
  decision; this plan's finding narrows *when* it's actually needed
- `lib/class-product-swatches.php`, `lib/class-woocommerce-admin.php`,
  `lib/class-woocommerce-single-product.php` — existing code this plan
  extends
- `src/js/shared/delegated-events.js`, `.cursor/rules/18-event-delegation.mdc` —
  the delegation convention this plan's click handler follows
- `context/PROGRESS.md` — project-wide phase tracker this plan updates
  once chunks land
