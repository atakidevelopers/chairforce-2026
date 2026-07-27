# 04 — Approach Evaluation: Block (lasersight) vs ACF Menu (shineon)

**Date:** July 2026  
**Context:** Chairforce header + mega menu rebuild. Client is result-oriented; FSE/block nav is **not** a hard requirement.

**Reference implementations:**
- **Approach A — Block-based:** `wp-content/themes/lasersight` (Briks fork, `core/navigation` mega menu)
- **Approach B — ACF Menu:** `wp-content/themes/shineon` (classic `wp_nav_menu` + ACF per item + custom walker)

**Figma sources:** `context/figma/components/` (10 screenshots including 4 new desktop mega menus)

---

## Recommendation (final)

**Use Approach B: Classic WP Menu + ACF on `nav_menu_item`, ported from shineon.**

**Render the entire header in PHP** — not a block-composed header shell, not an ACF header block. FSE stays as a thin wrapper only.

Do **not** build the mega menu in `core/navigation` blocks (lasersight approach).

See [05-stakeholder-decisions.md](./05-stakeholder-decisions.md) and [implementation plan](../plans/header-mega-menu-plan.md).

### Why

| Factor | Block (lasersight) | ACF Menu (shineon) | Winner |
|---|---|---|---|
| ~100 submenu images | Each image set individually on a `navigation-link` block in Site Editor | Image field on menu item, or auto from WC category thumbnail | **ACF** |
| 4 distinct mega menu layout patterns | Rebuild each submenu as columns/groups/headings in block editor | Menu hierarchy + `link_type` (heading, thumbnail-link, default) + CSS grid | **ACF** |
| Editor workflow | Site Editor → Navigation → nested blocks (structure locked anyway) | Appearance → Menus (familiar) | **ACF** |
| WooCommerce category links | Manual URLs + manual images | Link to `product_cat` term → pull term thumbnail | **ACF** |
| Version control / portability | Menu in DB (`wp_navigation` ref ID); not in git | Menu exportable (WP exporter, plugins); ACF JSON in theme | **ACF** |
| Performance (~100 images) | Full mega menu HTML on every page load | Same issue — but easier to add lazy-load in PHP walker | **Tie** (both need mitigation) |
| Mobile drill-down (Figma) | WP responsive nav + custom JS; close to Figma | Custom walker back button + panel stack; closer to Figma | **ACF** (slight edge) |
| Right-pulled nav items | CSS `margin-inline-start: auto` on first/last `<li>` (fragile) | Same fragility, or **two menu locations** (cleaner) | **ACF** (fixable) |
| Maintenance / WP core risk | `DOMDocument` rewrites nav HTML on every render | Standard walker + filters; well-understood pattern | **ACF** |
| FSE theme alignment | Native | Requires classic menu in FSE template part | Block (minor) |
| Existing chairforce scaffold | Sass mixins target `.wp-block-navigation` | Needs new Sass namespace | Block (minor) |

The decisive factor is **operational scale**: ~100 image+link pairs across 8+ mega menus is a content-management problem first and a rendering problem second. The ACF/classic-menu model is built for that; the block model fights it.

---

## Updated Figma layout patterns (all desktop screenshots)

The new images confirm **four** layout variants, not two:

| Pattern | Example menu | Structure | Thumbnails | Columns |
|---|---|---|---|---|
| **A — Grouped text** | Chairs | Section headings: TYPE (2 sub-cols), STYLES, MATERIALS | No | 3 sections |
| **B — Flat thumbnail grid** | Tables & Bench Seating, Shop by Space, Storage | No headings; equal columns of image+label links | Yes | 3–4 cols |
| **C — Grouped thumbnail** | Stools, Table Tops & Bases, Outdoor, Office | Section headings with 2 sub-columns each | Yes | 2 sections × 2 cols |

**All top-level mega menus are now documented in Figma.**

**Image count estimate (from provided screenshots):**

| Menu | Items with images |
|---|---|
| Tables & Bench Seating | ~13 |
| Stools | ~12 |
| Table Tops & Bases | ~21 |
| Shop by Space | ~10 |
| Storage | ~11 |
| Outdoor | ~9 |
| Office | ~7 |
| Chairs | 0 (text only) |
| **Total documented** | **~100** ✓ |

---

## Approach A — Block-based (lasersight) deep dive

### Architecture

```
parts/header.html (FSE)
  └── core/navigation [ref → wp_navigation CPT, is-style-mega-menu]
        ├── Block extensions allow columns/groups inside submenus
        ├── PHP Mega_Menu: inject images, rewrite invalid HTML, custom burger SVG
        ├── Sass: full-width fixed panels, mobile drill-down
        └── JS: sticky header, overlay positioning
```

### What works well

- Rich visual mega menu authoring (columns, headings, CTAs, image tiles)
- Desktop full-width panels with staggered animation (already matches Figma feel)
- Mobile drill-down with back navigation
- Synced navigation — one menu, site-wide updates

### What breaks down for Chairforce

1. **100 images in block editor** — each `navigation-link` needs its background image set individually. No bulk workflow, no WooCommerce category thumbnail auto-fill.

2. **Four layout patterns** — each top-level item's submenu is a different block structure (columns count, headings present/absent, thumbnails on/off). Editors must understand block layout, not just menu hierarchy.

3. **Structure is locked** — theme hides the block appender in submenus (`display: none` on `.block-list-appender`), so editors can't restructure anyway — you get block editor complexity without block editor flexibility.

4. **Fragile PHP** — `class-mega-menu.php` uses `DOMDocument` to rewrite columns/groups into valid `<ul>/<li>` and inject `<img>` from background-image attrs. Sensitive to WordPress core Navigation markup changes.

5. **Site-specific refs** — `header.html` hardcodes `"ref":2505`. Breaks on fresh installs; menu content not in theme repo.

6. **Dependencies** — Spectra (`UAGHideMob`, `uagb/icon`), Font Awesome, jQuery.

7. **Header shell gap** — lasersight header is logo + nav only. Figma requires announcement bar, inline search, phone block, 4 utility icons, separate nav row — **all net-new work** regardless of approach.

8. **Right-pulled items** — CSS pushes first and last top-level items to edges (`margin-inline-start: auto`). Adding/removing a nav item breaks alignment. Does not cleanly separate "Shop by Space / New Arrivals / Sale" from product categories.

### Porting cost to Chairforce

| Task | Effort |
|---|---|
| Port `Mega_Menu` class + block extensions | Medium |
| Port navigation Sass (~500 lines) | Medium |
| Port navigation JS | Low |
| Build full Figma header shell | **Large** (same as B) |
| Populate ~100 menu items with images in block editor | **Very large** (operational) |
| Remove Spectra dependency | Medium |

---

## Approach B — ACF Menu (shineon) deep dive

### Architecture

```
parts/header.html (FSE wrapper)
  └── ACF block: layout-header-main
        ├── Logo (ACF theme option)
        ├── wp_nav_menu('primary-menu') + Primary_Walker
        ├── Search toggle + form
        └── Mobile hamburger

Menu item (nav_menu_item post)
  └── ACF fields: link_type, icon, image, block_id, cards, visibility, …
        └── PHP filters render by type (heading, post, block, cards, tab, …)
```

### What works well

1. **Menu hierarchy maps to mega menu structure** — parent/child nesting = dropdown groups. Section headings = `link_type: heading`. Thumbnail links = `link_type: post` (or extended `default` with image field).

2. **Image management at scale** — ACF `image` field on menu item, or link to WooCommerce/product category and auto-pull term thumbnail via `menu-post.php` pattern.

3. **Familiar admin** — Appearance → Menus. Marketing teams know this workflow.

4. **Link types are extensible** — add `thumbnail-link` type with image+label for Pattern B/C items without the overhead of `post` card layout (title + description).

5. **Mobile drill-down** — walker injects `.submenu-back` button; JS handles panel stack. Matches Figma BACK + × pattern.

6. **Visibility control** — `menu-item-hide-for-small` / `menu-item-hide-for-large` ACF field for desktop-only or mobile-only items.

7. **Header block exists** — `layout-header-main` ACF block with logo, menu, search — closer starting point than lasersight (still needs Figma expansion).

### What needs fixing / extending for Chairforce

| Issue | Fix |
|---|---|
| ACF JSON out of sync with PHP (missing fields: `has_right_border`, `background_image`, `text`, `cards` types in JSON but used in PHP) | Re-export / rebuild field group in Chairforce |
| `wp_is_mobile()` vs CSS breakpoint (1024px) mismatch | Labels: CSS only. Thumbs: omit `<img>` on mobile UA |
| Stale `bjm-link-type-*` Sass (commented out partials) | Rewrite menu Sass for Chairforce tokens; don't port dead code |
| No image on `default` link type | Add optional `image` ACF field for `default` → render thumbnail+label |
| Tab system (`has-tabs`) not used in Figma | Skip tab JS; use static column CSS grid on `.submenu` |
| `column_span` / `child_columns` fields commented out in PHP | Re-enable for Pattern C (2 sub-columns under headings) |
| Right-pulled items via `:first/:last-of-type` CSS | Use **two menus**: `primary-nav` + `secondary-nav`, or ACF `nav_group` field |
| Broken refs (`partials/header-links` missing) | Build fresh for Chairforce Figma spec |
| Header doesn't match Figma | Extend header block: announcement bar, inline search (desktop), phone block, utilities |
| Full menu HTML on every page (~100 images) | `menu-thumb` 108×108 / 54px CSS display; omit `<img>` on mobile UA; lazy-load on desktop; closed panels `hidden`/`content-visibility` |
| jQuery dependency | Optional refactor to vanilla JS during port |

### Mapping Figma patterns to ACF link types

| Figma pattern | Menu structure | Link types |
|---|---|---|
| **A — Grouped text** (Chairs) | Top → heading (TYPE) → default links × N → heading (STYLES) → … | `heading`, `default` |
| **B — Flat thumbnail grid** (Storage, Shop by Space) | Top → thumbnail links × N (flat children) | `thumbnail-link` (new) or `post` |
| **C — Grouped thumbnail** (Stools, Table Tops) | Top → heading (TYPE) → thumbnail links × N → heading (MATERIAL) → … | `heading`, `thumbnail-link` |
| Section headings | Non-link label row | `heading` |
| Sale (red text) | Top-level link | `default` + CSS class `nav-item--highlight` |
| New Arrivals | Top-level link | `default` |

### Porting cost to Chairforce

| Task | Effort |
|---|---|
| Port walker + menu-hooks + template parts | Medium |
| Rebuild ACF field group (clean, Chairforce-prefixed) | Medium |
| Rewrite menu Sass for 4 layout patterns | Medium–Large |
| Port/adapt menu JS (or rewrite vanilla) | Medium |
| Build full Figma header shell (announcement, search, utilities) | **Large** |
| Populate ~100 menu items | **Large** (operational, but faster in Menus UI) |
| Add lazy-load + performance pass | Low |

---

## The header shell is independent

Both reference themes implement a **simpler header** than Figma requires. The following are **net-new for Chairforce** under either approach:

| Figma element | lasersight | shineon | Chairforce need |
|---|---|---|---|
| Announcement bar | ✗ | ✗ | ✓ |
| Inline search (desktop) | ✗ | Toggle only | ✓ always visible |
| Search row (mobile) | ✗ | ✗ | ✓ |
| Phone block (hours + number) | ✗ | ✗ | ✓ |
| Utility cluster (Showrooms, Account, Quotes, Cart) | ✗ | ✗ | ✓ |
| Separate nav row | ✗ (nav in main row) | ✗ | ✓ |

**Final architecture (approved):**

```
parts/header.html
  └── pattern: chairforce/header (PHP)
        └── chairforce_render_site_header()
              ├── partials/site-header.php
              │     ├── Announcement (ACF options)
              │     ├── Logo · AJAX search · Phone · Utilities · Cart
              │     └── Nav row: wp_nav_menu(primary-nav) + wp_nav_menu(secondary-nav)
              ├── lib/class-site-header.php
              ├── lib/class-mega-menu.php
              └── includes/menu/ (walker + ACF hooks)
```

No block-composed header. No ACF header block. Menus edited in Appearance → Menus only.

---

## Performance note (~100 images)

Both approaches render mega menu HTML on every page load. Mitigations:

1. Register `menu-thumb` at **108×108**; display **54×54px** in CSS (Figma) — do not use 80×80 or `large`
2. **Omit submenu `<img>` when `wp_is_mobile()`** — mobile drawer is text-only
3. `loading="lazy"` + `decoding="async"` on desktop submenu images only
4. `content-visibility: hidden` or `hidden` attribute on closed panels
5. Consider AJAX lazy-fetch of mega menu panel HTML on first open (future enhancement)
6. If many items link to WC categories, use term thumbnail meta rather than duplicate uploads

---

## Decision matrix (scored)

| Criterion (weight) | Block A | ACF B |
|---|---|---|
| Editor UX for 100 images (×3) | 2 | 9 |
| Figma layout fidelity (×3) | 7 | 8 |
| WooCommerce integration (×2) | 4 | 9 |
| Maintainability (×2) | 5 | 8 |
| Time to ship header shell (×1) | 5 | 6 |
| Time to populate content (×2) | 3 | 8 |
| Mobile UX match (×2) | 7 | 8 |
| FSE architectural purity (×1) | 9 | 4 |
| **Weighted total** | **~100** | **~155** |

---

## Stakeholder decisions (resolved)

All open questions resolved — see [05-stakeholder-decisions.md](./05-stakeholder-decisions.md):

- Category images from WC term thumbnails (+ ACF override)
- Client maintains menu in WP Admin (replacing Elementor templates)
- Desktop/mobile labels via ACF + CSS (not `wp_is_mobile()`)
- Mobile: 2 levels only; no chevron on drill-down leaf items
- AJAX product search with results dropdown
- Cart badge on desktop and mobile
- Hide-on-scroll preferred (TBD with designer)
- Outdoor + Office layouts confirmed (Pattern C)

**Implementation plan:** [`context/plans/header-mega-menu-plan.md`](../plans/header-mega-menu-plan.md)
