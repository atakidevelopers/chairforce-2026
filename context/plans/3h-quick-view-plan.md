# 3h — Quick View Rebuild (Modal | Drawer, admin-selectable) — Implementation Plan

## Status: ✅ Done (29 Jul 2026)

| Chunk | Scope | Commit |
|---|---|---|
| 1 | JS refactor (`bindSingleProductSwatchListeners` + `primeVariationForms`) | `61630e0` |
| 2 | REST endpoint + ACF `quick_view_display` + eye icon + archive script enqueue | `0685c9c` |
| 3 | `chairforce/quick-view-button` block + template + `quick-view.js` | `aff0297` |
| 4 | Sass (shell + modal/drawer skins) + build | `c21dfaf` |

Verified on `/product-category/chairs/cafe-chairs/` (drawer default — eye icon
on card image, Breeze Armchair variable product: gallery/title/price/swatches/add
to cart; Orange swatch inside popup → gallery thumbnailed from ~17 to 5 images +
Clear options; Escape close → reopen Adella Chair shows correct product, not
stale Breeze content). Modal skin verified after switching ACF to `modal`
(Breeze Armchair content loads identically). Simple product (Ava Vintage School
Chair) verified earlier in session. Page-2 pagination quick view trigger opens
popup (delegated handler). **Update (31 Jul 2026):** quick-view trigger now lives
in shared `parts/product-card.html` (`2a455c9`); Load More append shipped as
**3i** (`d7d7acc`) — minor quirks pending polish. REST endpoint verified via
`ddev wp eval` for Breeze Chair (#1000290) — returns gallery + `variations_form`
+ `cf-swatches-single`. Zero quick-view-specific console errors (pre-existing
jQuery migrate + copyright block only).

**Not verified this session:** 3e single-product page regression after chunk-1
refactor; backdrop-click close; rapid reopen mid-fetch stale flash; formal
saved screenshots for PM/client. **Update (31 Jul 2026):** card trigger placement
moved to `parts/product-card.html`; DB template sync note no longer applies when
theme template parts are used (0 DB overrides at time of writing per
`context/implementation/product-grid.md`).

## Goal

Execute the next roadmap item from `context/PROGRESS.md`: a "Quick view" trigger
icon on product cards (eye icon, per `context/figma/components/Product Cards.png`)
that opens a popup showing the same swatches/gallery/add-to-cart experience built
in Phase 3e, **without navigating away from the grid**.

**Confirmed via live-site investigation this session (not guesswork):** the
live site's popup is Woodmart's own native Quick View (Magnific Popup +
`inc/integrations/woocommerce/modules/quick-view.php`) with the **"vertical"**
content layout (`quick_view_layout` theme setting — image on top, details
below), re-skinned via custom CSS that repositions Magnific's own
`.mfp-wrap` container into a fixed, full-height, right-anchored panel:

```css
.mfp-wrap {
	position: fixed;
	inset: var(--wd-admin-bar-h) 0 0 0;
	right: 0 !important;
	top: 0 !important;
	left: auto !important;
	max-width: 100% !important;
	height: 100vh;
	transform: translateX(100%);
	transition: transform 0.4s ease;
	background-color: #fff !important;
}
```

Stripping that CSS reverts it to Woodmart's stock centered modal. **Decision
(client-directed, this session):** rather than picking one and hardcoding it
like the grid's swatch style-3/dis-style-3 combo, add **one admin-configurable
toggle** — Modal vs. Drawer — since it's cheap (one ACF field + one CSS
modifier class) and both are genuinely-observed live states worth keeping
selectable, unlike the swatch style matrix (file `09` §3's "hardcode one
combination" guidance doesn't apply here because there's no matrix to avoid —
just two states, both already confirmed in use).

## Scope decision — read this before starting

### What's already shipped and reusable (this phase adds zero new swatch/gallery logic)

- **Swatch markup + click-proxy + gallery-swap, all of it (3e):**
  `Chairforce\Product_Swatches::render_single_product_swatches()` (fires via
  the `woocommerce_dropdown_variation_attribute_options_html` filter — a
  filter on a *function's return value*, with **zero dependency on page
  context/main query**, confirmed by reading `wc-template-functions.php`) and
  `src/js/single-product-swatches.js`'s delegated click proxy +
  `found_variation`/`reset_data` gallery-swap. Phase 3e's own plan explicitly
  wrote this JS to *not* assume page-load-only markup, anticipating this
  phase.
- **The event-delegation convention (3b):** `src/js/shared/delegated-events.js`
  — `delegateDocument()` binds once on `document` and matches by selector, so
  it already tolerates the popup's markup not existing yet at binding time.
- **The theme's existing REST convention:** `includes/rest-api/product-search.php`
  (`chairforce/v1` namespace, registered via `Chairforce\Api::register_hooks()`,
  wired in `lib/class-api.php`) — the new endpoint follows this exact pattern,
  not `admin-ajax.php` (Woodmart's own approach, but this theme already has an
  established REST convention to stay consistent with).
- **PHP → JS data bridge already exists:** `Chairforce\Front::get_localize_script_data()`
  (`lib/class-front.php`) populates `window.Chairforce_Public` — already has
  `rest_url`/`nonce`. Extend this object rather than inventing a second
  localize call.
- **The Lucide icon-font system (rule `16-icon-system.mdc`, Surface 3 — Sass
  mixins):** `src/sass/header/_search.scss`'s `@include cf-icon-before('search');`
  is the established pattern for the theme's *own* fixed UI chrome icons (as
  opposed to Surfaces 1/2, which are for content-editor-facing icon pickers).
  The quick-view trigger's eye icon should follow this same convention, not
  the Button-block icon-picker system.

### A genuine gap this phase must fix first — `single-product-swatches.js` is not currently safe to re-run

`initSingleProductSwatches()` (3e) is a single function that both (a) binds
delegated listeners on `document`/jQuery and (b) primes each existing
`.variations_form`'s default-gallery cache. It's only ever called once, from
`src/js/index.js`'s `DOMContentLoaded` handler. **Calling it a second time —
which the quick-view popup will need to do, to prime *its own*, newly-injected
`.variations_form`'s gallery cache — would double-bind every listener**
(`delegateDocument()` and `jQuery(document).on()` both add a new listener on
every call; neither is idempotent, confirmed by reading `delegated-events.js`).
Double-bound handlers would double-fire `handleSwatchClick`, double-swap the
gallery, etc. — a real bug, not a hypothetical one, if the popup naively
reuses the existing export as-is.

**Fix, as prerequisite work in this plan (chunk 1):** split the module's
public surface into:

- `initSingleProductSwatches()` — unchanged signature/behavior for
  `index.js`'s existing call site; internally now just calls the two
  functions below, once, guarded against double-init.
- `bindSingleProductSwatchListeners()` — the delegated click proxy +
  `found_variation`/`reset_data`/`woocommerce_update_variation_values`/reset-click
  jQuery bindings. Exported, but self-guards with a module-level boolean so
  calling it again is a safe no-op.
- `primeVariationForms( root = document )` — the "cache this form's current
  gallery HTML as its own default" loop, scoped to `root.querySelectorAll('.variations_form')`.
  **This one must stay safely repeatable** — it's what the quick-view module
  calls (chunk 3) right after injecting the popup's fetched HTML, so the
  *popup's own* form gets its own default-gallery baseline cached before any
  swatch click, exactly mirroring what page load already does for the main
  page's form.

No other 3e behavior changes — this is a refactor for reusability, not a
rewrite.

### New pieces this phase actually adds

1. **A trigger:** new dynamic block `chairforce/quick-view-button` (eye icon),
   inserted as a sibling to `chairforce/product-swatches` in
   `templates/archive-product.html`, wrapped together with
   `wp:woocommerce/product-image` in a new positioning wrapper so the icon can
   sit in the image's top-right corner per Figma (today, `product-image` and
   `product-swatches` are plain flow siblings — nothing in the current card
   markup is `position: relative`, so there's nothing to absolutely-position
   against yet; confirmed by reading `src/sass/swatches/_base.scss` and the
   template).
2. **A REST endpoint** returning rendered HTML for one product — reusing
   WooCommerce's own `woocommerce_before_single_product_summary` /
   `woocommerce_single_product_summary` action sequence (title, rating,
   price, short description, **the variations/add-to-cart form — which is
   where 3e's swatches automatically appear, with no new PHP needed for
   swatches specifically**, meta, share buttons) — deliberately **not**
   `woocommerce_after_single_product_summary` (tabs, related products,
   upsells — too much content for a popup, and not what Woodmart's own
   quick-view shows either). Confirmed by reading
   `wc-template-functions.php`/`content-single-product.php` that none of
   these action callbacks gate on `is_product()` or the main query — they
   only read `global $product`/`$post`, so calling them from a REST callback
   with those globals manually set works correctly (this is exactly why
   Woodmart's own AJAX-based quick view works too).
3. **A popup shell** — one component, two CSS-only skins (`--modal` /
   `--drawer` modifier class), not two separate implementations. Toggled by
   a new ACF `button_group` field.
4. **One new ACF field**, `quick_view_display` (`Modal` | `Drawer`), added to
   `acf-json/group_theme_options.json`'s existing `Misc` tab (following the
   `button_group` pattern already used in `group_chairforce_menu_options.json`
   — e.g. `field_chairforce_menu_link_type`). Default: `drawer` (matches the
   confirmed current live behavior; trivial one-line change if the client
   ends up preferring `modal` as the shipped default).
5. **One new icon-font entry:** `eye` is not yet in the curated Lucide set
   (`src/js-admin/lucide-icon-options.js` / `$cf-icon-codepoints` both
   currently lack it — confirmed by grep). Codepoint confirmed from
   `node_modules/lucide-static/font/codepoints.json`: `eye` → `0xe0ba`.
   Add it following rule `16-icon-system.mdc`'s exact script-generated,
   byte-verified process (do not hand-type the glyph character).

### What's explicitly out of scope for this plan

- **Wishlist heart icon** — Figma shows it stacked above the eye icon on the
  same card corner, but wishlist itself hasn't been rebuilt yet (separate,
  not-yet-started phase per the master checklist). Leave room in the new
  positioning wrapper's markup/CSS for a second icon to slot in above this
  one later; don't build the heart icon or wishlist logic now.
- **Simple (non-variable) products in quick view** — the endpoint should
  still work for them (no swatches block fires, `woocommerce_template_single_add_to_cart`
  just renders the simple-product form instead) — verify this in chunk 4,
  but no special-case code is expected to be needed.
- **Load More interaction** — quick-view trigger buttons on Load-More-appended
  cards are covered for free by the existing delegated-click convention (3b);
  no new work, just confirm in verification.
- **Tabs / related products / reviews inside the popup** — deliberately
  excluded (see architecture decision above), not a "missing feature."

## Architecture decisions

1. **REST endpoint**, not `admin-ajax.php`: `GET /wp-json/chairforce/v1/quick-view/{id}`,
   registered in a new `includes/rest-api/quick-view.php` (following
   `product-search.php`'s exact structure), wired via
   `Chairforce\Api::register_hooks()`. Returns `{ html: string }` on success;
   `404`/`{ html: '' }` shape for invalid/non-published product IDs (sanitize
   `id` as an absint route arg, verify `get_post_status() === 'publish'` and
   `get_post_type() === 'product'` before rendering — don't trust the client
   to only ever pass valid IDs).
2. **Server-side rendering approach** — inside the endpoint callback:
   - `wc_get_product( $id )`; bail with an empty/error response if invalid.
   - Set `global $post, $product`; `setup_postdata( $post )`.
   - `ob_start()`; `do_action( 'woocommerce_before_single_product_summary' )`
     (renders the gallery — same `wc_get_product_gallery_html()`-backed markup
     3e already integrates with, so the popup's gallery gets flexslider/zoom
     theme-support for free too, no new gallery code); wrap
     `do_action( 'woocommerce_single_product_summary' )` in the summary
     container div (matching `content-single-product.php`'s own markup shape,
     so existing single-product Sass mostly just works unmodified inside the
     popup); `ob_get_clean()`.
   - `wp_reset_postdata()`.
   - This is the **one and only** place with new "single-product rendering"
     PHP in this plan — no parallel quick-view template partial, per file
     `16`'s explicit warning against building "a second, parallel quick view
     product template."
3. **Popup shell is JS-built, not template-rendered.** A single hidden root
   (e.g. `<div id="cf-quick-view" class="cf-quick-view" hidden>…</div>`) is
   appended to `<body>` once, lazily, on the *first* trigger click — not
   eagerly on every page load (keeps this a true no-op on pages with no
   product cards). Contains: backdrop, panel, close button, and a content
   slot that gets replaced with the REST response's `html` on every open.
   The `--modal`/`--drawer` modifier class is read once from
   `window.Chairforce_Public.quickViewDisplay` (new key, added to
   `Front::get_localize_script_data()`) at shell-creation time.
4. **CSS mechanics for the drawer skin directly adapt the confirmed-working
   live CSS** (fixed position, `translateX(100%)` → `translateX(0)` on open,
   `transition: transform .4s ease`, `max-width:100%` safety clamp) — applied
   to *our own* shell element instead of `.mfp-wrap`, since we're not using
   Magnific. The modal skin is a conventional centered flex/backdrop dialog
   (`position:fixed` backdrop, centered panel, `max-width` clamp) — this is
   the "same as most day-to-day sites" baseline the client described, and
   matches Woodmart's own stock (undecorated) quick view.
   Both skins share the same inner content layout — **"vertical"**
   (image/gallery on top, details below) — matching the confirmed live
   content arrangement; there's no need to rebuild Woodmart's "horizontal"
   layout variant since nothing currently uses it.
5. **Open/close mechanics are plain vanilla JS**, consistent with this
   module's own delegated-click origins (no jQuery needed for the shell
   itself — jQuery is only needed, per 3e's precedent, for the
   WooCommerce-native variation-form events, which the *content* injected
   into the popup still relies on via the already-shipped
   `bindSingleProductSwatchListeners()`).
   - **Open:** trigger click → build/show shell if not already built → set
     loading state → `fetch()` the REST endpoint → inject `html` → call
     `primeVariationForms(popupContentEl)` (chunk 1's export) so the *popup's*
     `.variations_form` gets its own gallery-default baseline cached →
     add an `is-open` class (triggers the CSS transition) → focus the panel
     (accessibility) → bind `Escape` keydown + backdrop click to close.
   - **Close:** remove `is-open` → after the transition ends, hide/empty the
     content slot (so a stale product isn't visible mid-transition on next
     open) → return focus to the trigger button that opened it.
6. **Loading state**, since the REST fetch is async and the shell is visible
   immediately (drawer slides in before content exists): a simple
   `cf-quick-view--loading` modifier class showing a spinner/skeleton in the
   content slot until the fetch resolves. Woodmart's own AJAX popup has the
   equivalent (a loading spinner shown while its own admin-ajax call is in
   flight) — same idea, no library needed.

## Chunk breakdown

### Chunk 1 — JS refactor (prerequisite): make `single-product-swatches.js` safely re-initializable

- Split `initSingleProductSwatches()` into `bindSingleProductSwatchListeners()`
  (idempotent-guarded) + `primeVariationForms(root = document)` (safely
  repeatable) as described above; keep `initSingleProductSwatches()` exported
  with its current behavior for `index.js`'s existing call site.
- Export `primeVariationForms` for chunk 3's quick-view module to import.
- No behavior change for the existing single-product page — verify 3e's own
  manual test steps still pass (swatch click → gallery swap → Clear reverts)
  before moving on.

### Chunk 2 — PHP: REST endpoint + ACF field + icon codepoint

- `includes/rest-api/quick-view.php` (new) — route + callback per
  architecture decision #1/#2. Require it from `Chairforce\Api::register_hooks()`
  alongside the existing `product-search.php` require.
- Add `quickViewRestUrl` (or reuse a generalized key) + `quickViewDisplay` to
  `Front::get_localize_script_data()` — `rest_url( 'chairforce/v1/quick-view' )`
  (JS appends `/{id}`) and `get_field( 'quick_view_display', 'option' ) ?: 'drawer'`.
- Add `quick_view_display` `button_group` field to
  `acf-json/group_theme_options.json`'s `Misc` tab (choices
  `modal => Modal`, `drawer => Drawer`; default `drawer`).
- Add `eye` to `CHAIRFORCE_LUCIDE_ICON_OPTIONS`
  (`src/js-admin/lucide-icon-options.js`) and `$cf-icon-codepoints`
  (`src/sass/icon-font/_variables.scss`) via a short one-off script per rule
  `16`'s process (codepoint `0xe0ba`, confirmed above) — verify the written
  bytes, don't hand-type the glyph.

### Chunk 3 — Block + template wiring + JS quick-view module

- New dynamic block `chairforce/quick-view-button`
  (`src-jsx-blocks/quick-view-button/`: `block.json` — `ancestor:
  ["woocommerce/product-template"]`, `usesContext: ["postId"]`, matching
  `product-swatches`'s own block.json shape; `render.php` — outputs a
  `<button class="cf-quick-view-trigger" data-product-id="…">` with the eye
  icon via `@include cf-icon-before('eye')` in its own Sass partial, no
  `view.js` needed — interactivity lives in the sitewide module below, not a
  block-scoped script; `index.js` — editor registration + a simple static
  `edit.js` preview).
- Update `templates/archive-product.html`: wrap the existing
  `wp:woocommerce/product-image` in a new `wp:group` wrapper (e.g.
  `cf-card-media`, `position: relative` in Sass) alongside the new
  `chairforce/quick-view-button` block as a sibling inside that same group,
  positioned `absolute; top; right;` in Sass (leave visual room above it for
  the future wishlist heart icon per the out-of-scope note).
- New `src/js/quick-view.js` — the shell builder + open/close/fetch logic
  from architecture decision #5/#6, importing `primeVariationForms` from
  chunk 1. Exports `initQuickView()`; imported and called from
  `src/js/index.js`'s existing `DOMContentLoaded` block, alongside
  `initSingleProductSwatches()`.
- Delegated trigger binding: `delegateDocument('click', '.cf-quick-view-trigger', …)`
  — Load-More-safe for free, per the 3b convention.

### Chunk 4 — Sass (shell + both skins) + verification

- New `src/sass/quick-view/` partial(s): shared shell/content styles, plus
  `.cf-quick-view--modal` and `.cf-quick-view--drawer` modifier rules per
  architecture decision #4. Reuse 3e's `.cf-swatches-single` styles unmodified
  inside the popup content (no swatch-specific CSS changes needed here).
- **Verification, against real data** (same established pattern as 3d/3e —
  test a genuine variable `pa_colour` product, not the first one clicked):
  1. Confirm the eye icon renders top-right on grid cards (image, not
     swatches area), Figma-adjacent placement.
  2. Click it on a variable product → popup opens (drawer by default) →
     confirm gallery, title, price, swatches, and Add to Cart all render and
     match the real single-product page's data for that product.
  3. Click a colour swatch **inside the popup** → confirm dropdown sync +
     gallery swap work (this is the chunk-1 refactor's actual payoff —
     confirm no double-firing from any pre-existing main-page listeners).
  4. Close (Escape, backdrop click, and the close button) → reopen a
     **different** product → confirm no stale content/gallery from the
     previous product flashes before the new fetch resolves.
  5. Switch the ACF field to `Modal`, reload, repeat steps 2–4 → confirm the
     same content works identically inside the centered-modal skin.
  6. Click quick view on a **simple** (non-variable) product → confirm it
     still renders correctly with a plain Add to Cart button, no swatch
     wrapper, no JS errors.
  7. Trigger a Load More / paginated fetch first (if available on the test
     page), then click quick view on a newly-appended card → confirm the
     delegated trigger still works with no extra wiring.
  8. Check browser console for errors throughout — zero expected, matching
     3e's own verification bar.
  9. Screenshot both skins before/after for the PM/client record, same as
     3d/3e.

## Related files

- `context/existing-functionality/16-quick-view.md` — original behavior
  contract (AJAX reuse of single-product markup, `wdQuickViewOpen` event
  equivalent, Magnific Popup as prior art for the shell)
- `context/existing-functionality/04-single-product-page-swatches-and-gallery.md`,
  `context/plans/3e-single-product-swatches-and-gallery-plan.md` — the
  swatch/gallery component this phase reuses without modification (besides
  the chunk-1 re-init refactor)
- `context/existing-functionality/17-load-more-and-event-delegation.md`,
  `src/js/shared/delegated-events.js`, `.cursor/rules/18-event-delegation.mdc` —
  delegation convention the trigger button and popup follow
- `context/figma/components/Product Cards.png` — trigger icon placement
  (eye + heart, top-right of card image)
- `includes/rest-api/product-search.php`, `lib/class-api.php` — REST
  convention this phase's endpoint follows
- `lib/class-front.php` (`Front::get_localize_script_data()`) — PHP→JS data
  bridge this phase extends
- `.cursor/rules/16-icon-system.mdc` — icon-font addition process (`eye`)
- `acf-json/group_chairforce_menu_options.json` — `button_group` field
  pattern reused for `quick_view_display`
- `templates/archive-product.html`, `src-jsx-blocks/product-swatches/` —
  existing card markup/block this phase adds a sibling block next to
- `context/PROGRESS.md` — project-wide phase tracker this plan updates once
  chunks land

## Ready to implement?

Everything above is grounded in this session's confirmed findings (live CSS
inspection, WooCommerce core source reading, and this codebase's existing
conventions) rather than assumption — the only genuinely open item is the
`quick_view_display` default (`drawer` proposed, trivially changeable) and
the exact ACF tab placement (`Misc` proposed). Neither blocks starting.
**Yes, ready to hand to an implementing agent as-is.**
