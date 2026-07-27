# Header / Mega Menu — Cleanup Notes

## Layout Variant ACF field (`layout_variant`) — removed 2026-07-27

The **Layout Variant** field was removed. All desktop mega menus now use the former
Pattern A (grouped sections) styling by default, controlled by:

- **Grid Columns** — panel column count (3 or 4)
- **Column Span** — section width (e.g. TYPE spans 2 cols on Chairs)
- **Child Columns** — sub-column count under headings
- **Link Type** — heading, thumbnail-link, etc.

Removed: ACF field, `site-header__layout-*` classes, Pattern B/C Sass.
