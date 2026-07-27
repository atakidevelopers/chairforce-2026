# Header / Mega Menu — Cleanup Notes (deferred)

## Layout Variant ACF field (`layout_variant`)

**Status:** Keep for now; candidate for removal after mobile nav ships.

The **Layout Variant** field (`grouped-text` / `flat-grid` / `grouped-thumbnails`) outputs
`site-header__layout-*` classes on top-level menu items. These drive desktop mega menu Sass
only (grid vs flex, thumb orientation). They do **not** affect mobile drill-down.

**Before removing:**

1. Confirm desktop mega menus can be styled from **Grid Columns** + **Column Span** +
   **Child Columns** + **Link Type** alone (or simplify to one layout approach).
2. Remove ACF field from `group_chairforce_menu_options.json`.
3. Delete `src/sass/menu/layout-patterns/` and layout-specific rules in `_desktop.scss`.
4. Remove `layout_variant` handling in `includes/menu/menu-hooks.php`.
5. Re-test Chairs (Pattern A), Storage (Pattern B), and any Pattern C menus.

**Tracked:** 2026-07-27 — user feedback: variants may be unnecessary complexity.
