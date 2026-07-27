# 15 — `/gallery/` Page: Infinite Scroll + Filters

Resolves QA question: *"The Gallery Functionality:
http://chairforce.test/gallery/ which loads on scroll and has filters on
the page as well."*

## What it is

A dedicated Elementor page built from three cooperating pieces:

1. **Data**: the `gallery-tabs` custom post type (34 published items) —
   see file `14` §A/§F for its field list (`image_item` cover image,
   `gallery_images` gallery, optional `_title`/`_description`) and the
   `gallery-category` taxonomy (file `14` §C) for filtering.
2. **Grid rendering**: JetEngine's `elementor-widget-jet-listing-grid`
   widget, bound to a JetEngine Listing Grid template named **"Gallery
   Listing"** (file `14` §F), with `data-query-id="gallery-listing"`.
3. **Filtering**: a **`jet-smart-filters-checkboxes`** Elementor widget
   (confirmed live — this is the plugin's first *confirmed* real usage
   anywhere on the site, superseding the earlier "maybe it's the shop
   color filter" guess in files `09`/`10`, which was wrong — see file `12`),
   wired to the same `gallery-listing` query ID so checking a
   `gallery-category` term re-queries and re-renders the grid via AJAX.

## Confirmed live markup (curl-verified against `chairforce.test/gallery/`)

```html
<div class="... elementor-widget-jet-smart-filters-checkboxes" ...>
  <div class="jet-smart-filters-checkboxes jet-filter " data-indexer-rule="show" ...>
    <!-- one checkbox per gallery-category term -->
  </div>
</div>
...
<div class="... elementor-widget-jet-listing-grid" data-query-id="gallery-listing" ...>
  <div class="jet-listing-grid__item jet-listing-dynamic-post-1067068">...</div>
  <!-- one .jet-listing-grid__item per gallery-tabs post -->
</div>
```

Pagination markers present in the page source: `data-page="1"`, plus
`infinite`/`load-more` strings in the widget's own JS config — i.e. the
"loads on scroll" behavior is **JetEngine Listing Grid's own built-in
pagination mode** (it supports classic pagination, a "Load More" button,
or infinite-scroll-on-scroll as alternate settings on the same widget),
not a separate plugin. The exact mode (infinite-scroll vs. a load-more
button that also happens to look continuous) wasn't pinned to 100%
certainty from static markup alone in this pass — **watch the live page's
network requests while scrolling to confirm** whether it's a true
`IntersectionObserver`-driven auto-load or a button that's just
visually minimal; either way the AJAX contract (re-fetch page N+1 of the
same query, append results) is the same to replicate.

## The gallery item popup

**Trigger mechanism — confirmed, not custom JS.** Each gallery card in the
"Gallery Listing" template has a "View Products" button widget with two
Elementor settings: `jet_attached_popup: "1066035"` and
`jet_engine_dynamic_popup: "yes"` — JetEngine's own native "attach an
Elementor popup to this button, populated dynamically from the current
loop item" integration. `1066035` is the **JetPopup** post "Gallery – Jet
Popup Template" (file `14` §J) — a real, separate custom post type
(`jet-popup`) that defines the modal *shell* (size/position/background/
overlay/close-button styling), while the modal's *content* is the
"Gallery - Pop-up Content" **Listing Grid** template (file `14` §F) bound
to whichever `gallery-tabs` post the clicked card represents. So there are
three cooperating layers, not one: JetPopup (shell) + JetEngine dynamic
binding (which item) + Listing Grid template (content markup) — worth
knowing since a rebuild only needs the *end result* (a modal showing that
item's content), not this three-plugin chain.

The popup content (per the Query Builder entries in file `14` §G)
additionally surfaces:

- The gallery item's own `gallery_images` (full-size viewer).
- Related products via Relation #5 ("Related Other Products in the
  Gallery") — a "shop this look" product list.
- The single featured product via Relation #9 ("Related Featured Product
  in the Gallery").
- Prev/next navigation to adjacent gallery items ("Query for Previous and
  Next Gallery").

This is functionally an "inspiration gallery with shoppable photos"
feature — closer in spirit to an Instagram-shop grid than a plain image
gallery. Worth confirming this full feature set (not just "photo +
lightbox") is actually wanted in the rebuild, since it's noticeably more
involved than a generic gallery.

## Rebuild recommendation

1. **Data**: `gallery-tabs` → new CPT with the same field set (cover
   image, image gallery, optional title/description) + `gallery-category`
   taxonomy for filtering. Zero migration for the post/meta/taxonomy data
   itself.
2. **Relations**: rebuild the "related products" and "featured product"
   links as a proper relationship (ACF Relationship fields on the new
   gallery CPT, or a simple join table) reading from JetEngine's
   `wp_jet_rel_default` table **once**, at migration time, to seed the new
   relationship field's initial values (per file `14` §D — this is real
   relational data worth carrying over, not a "rebuild empty" situation).
3. **Grid + pagination**: build with this repo's own patterns — per
   `09-blokki-integration.mdc`/WP Grid Builder conventions if that stack is
   adopted for archives generally (see file `12`'s shop-filter
   recommendation for the parallel case), implementing whichever load
   mode (infinite-scroll vs. load-more button) is confirmed live. **Follow
   file `17`'s event-delegation rule** — filtering and pagination must
   both work on freshly-appended items without rebinding handlers.
4. **Filters**: a simple checkbox list of `gallery-category` terms,
   AJAX-updating the grid query — no `jet-smart-filters` dependency needed,
   same "read terms + re-run query" pattern as file `12`'s shop filter,
   just scoped to a different taxonomy/post type.
5. **Popup**: build as one shared modal/dialog component (same
   recommendation as file `16`'s Quick View, and file `14` §J for the
   popup mechanism itself) — a click handler passing the clicked gallery
   item's ID, no JetPopup/JetEngine-dynamic-popup equivalent needed once
   you're not using those plugins. Decide explicitly (with the site owner)
   whether the full "shoppable photo" feature set (related + featured
   products, prev/next) is in scope, or whether a simpler "just show the
   full-size photos" lightbox is acceptable — this materially changes the
   rebuild size for this one component.
