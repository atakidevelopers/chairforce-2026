# 12 — Shop/Category Archive Filters (the "filter by color" sidebar widget)

Resolves an open question from files `09`/`10` (which speculated this might
be `jet-smart-filters`). **Confirmed wrong guess** — it's actually
Woodmart's **own** widget, and it reuses the exact same swatch data model
documented in file `02`. This is genuinely good news for the rebuild: this
feature is much closer to "already built" than "needs a new system."

## What it actually is

File: `wp-content/themes/woodmart/inc/widgets/class-widget-layered-nav.php`
— class `WOODMART_Widget_Layered_Nav`, an extended/skinned version of
**WooCommerce's own native "Filter Products by Attribute" widget** (it
literally subclasses the same layered-nav concept WooCommerce ships with,
just with swatch rendering and extra layout options bolted on).

Confirmed live on `chairforce.test/shop/`: the rendered filter markup is
`<ul class="wd-swatches-filter wd-filter-list ...">` (Woodmart's own
classes) plus WooCommerce's native `widget_price_filter`. **Zero**
`jet-smart-filters` / `jsf-` markup found anywhere on that page — the
earlier speculation in files `09`/`10` was incorrect, not just unconfirmed.

> **AJAX transport:** File `12` covers the *widgets and WC query params*. The *live
> shop UX* (instant filter + sidebar refresh) is **Woodmart PJAX**, not REST facets.
> See **`12A-woodmart-ajax-shop-filtering.md`** for the full mechanism verified on
> `chairforce.test` (`ajax_shop: 1`). Summary: filter clicks trigger a GET partial
> reload of `.wd-page-content` — filters and grid re-render in one server pass.

## How it works (mechanically — standard WooCommerce filtering; AJAX is Woodmart PJAX)

- **Data source — identical to swatches**: for each term, reads
  `get_term_meta($term_id, 'color', true)` / `'image'` / `'not_dropdown'`
  — the *exact same* term meta keys documented in file `02` §1. No
  separate data model to build; whatever's already configured for swatches
  automatically works here too.
- **Styling — identical to swatches**: `style`/`shape`/`size` default to
  `'inherit'`, which resolves via `woodmart_wc_get_attribute_term($taxonomy,
  'swatch_style')` / `'swatch_shape'` — the same per-attribute `wp_options`
  rows from file `02` §2 (so on this site, `pa_colour`'s filter swatches
  come out styled `style=3`/`shape=round` too, same as the product-card/
  single-product swatches, unless the widget instance explicitly overrides
  it).
- **Filtering mechanism — plain WooCommerce, server-side**: renders
  `<a href="?filter_pa_colour=black,white">` links (or a `<select>` in
  "Dropdown" display mode, or a `wd-pf-checkboxes` AJAX-ish popover in a
  third "checkbox list" display mode). Reads currently-active filters via
  WooCommerce core's own `WC_Query::get_layered_nav_chosen_attributes()`
  (parses the `filter_{attribute}` query-string params WooCommerce already
  understands natively) — this is the same mechanism WooCommerce's stock
  "Filter Products by Attribute" widget uses, just with swatch markup
  layered on top of the `<a>`/`<li>` output instead of plain checkboxes.
- **Term counts** ("how many products remain if I pick this filter"): via
  `get_filtered_term_product_counts()`, which either runs a direct SQL
  query respecting the current archive's tax/meta query (older WooCommerce)
  or defers to WooCommerce's own
  `Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer` class
  (WC ≥ 5.5) — standard WooCommerce internals, not custom to Woodmart.
  Counts are cached in a `wc_layered_nav_counts_{taxonomy}` transient
  (**disabled on this site** — see file `07` §5, the child theme's
  `pre_set_transient` filter explicitly blocks this transient name too).
- **Widget admin options**: title, which attribute, which category(ies) to
  show on, AND/OR query type, layout (list/2-col/inline/dropdown),
  swatch size/style/shape, show labels, show tooltips, show checkboxes,
  optional search-within-filter input. All are presentation options on top
  of the same underlying WooCommerce filtering, not separate data.

## Rebuild implication — UI on native WC; transport should match Woodmart PJAX

Because the filter widget's data and styling are already 100% shared with
the swatches you're already rebuilding (file `02` §1/§2), and the actual
*filtering* is native WooCommerce (`filter_{attribute}` query args +
`WC_Query`), this doesn't need `jet-smart-filters`, WP Grid Builder, or any
other plugin to replicate.

**Transport (how AJAX should work):** the live site does **not** update filters
via a separate REST facet API. It **PJAX-reloads the whole shop content region**
(`.wd-page-content`) so filters, counts, grid, and load-more button all come from
one server render. See **`12A-woodmart-ajax-shop-filtering.md`** — the rebuild
should follow that **single-shell partial reload** contract rather than swapping
grid/chips/panel as independent REST fragments (which fights WC's one-query model).

Recommended approach:

1. Build a simple widget/block that lists `pa_colour` (and any other
   filterable attribute) terms with the same swatch rendering priority
   (color → image → text) already built for the card/single-product
   swatches — this can likely reuse the same "render one swatch" helper
   function/component, just in a different context (filter link instead of
   variation-select proxy).
2. Emit standard `?filter_pa_colour=slug1,slug2` links/form fields; let
   WooCommerce's own query-string parsing and `WP_Query` tax_query
   generation do the actual filtering — no custom filtering logic needed.
3. Term counts: either accept a live COUNT query per page load (this site
   explicitly disabled the transient cache anyway — see file `07` §5, so
   there's no correctness precedent to preserve around caching) or add a
   fresh, appropriately-scoped transient of your own if performance
   becomes a concern at scale.
4. Skip: the "Dropdown" (select2) and "checkbox popover" display variants
   unless specifically requested — confirm with the site owner which
   *display mode* is actually configured live before assuming "list" (the
   default) is what's needed; a quick widget-area / `wp_options` check for
   the specific `WOODMART_Widget_Layered_Nav` instance's saved `display`
   value would confirm this if it matters.

## Related, not yet confirmed: does `jet-smart-filters` do anything else?

The plugin is still active and its markers still appear in some products'/
variations' own `_elementor_data` (file `10` §4) — but not on the shop
archive page, and not for the color-swatch filter specifically. Whatever
it *is* doing elsewhere remains an open, lower-priority item; it's now
firmly ruled out as the answer to "how does shop-page color filtering
work," which was the original reason it was flagged at all.
