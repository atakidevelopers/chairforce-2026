# Header & Mega Menu — Implementation Plan

## Goal

Build the Chairforce header and mega menu to match Figma specs using:

1. **Full PHP-rendered header** — single markup pipeline, full control
2. **Classic WP Menus + ACF** — client-maintainable mega menu (~100 category images)
3. **FSE thin wrapper** — `parts/header.html` references a PHP pattern only

Replaces the live site's Classic Menu + Elementor template mega menus (a11y and markup problems) with structured, accessible navigation.

**Requirements:** `context/header-mega-menu/`  
**Port source:** `wp-content/themes/shineon`  
**Rejected reference:** `wp-content/themes/lasersight` (block Navigation)

---

## Scope

### In scope

- Announcement bar (ACF theme options)
- Desktop header: logo, AJAX product search, phone block, utility cluster (Showrooms, Account, Quotes, Cart + badge)
- Mobile header: logo, phone icon, cart + badge, hamburger, search row
- Desktop nav row: single primary menu (categories + Shop by Space, New Arrivals, Sale); right-pulled group via CSS on desktop
- Desktop mega menus: 4 layout patterns (A/B/C)
- Mobile drawer: root panel + drill-down panels (2 levels only)
- Desktop/mobile label variants (CSS toggle)
- WooCommerce cart fragments for badge
- AJAX product search with results dropdown
- ACF field group on `nav_menu_item` (JSON in theme)
- Hide-on-scroll header behaviour (default on; togglable via header options — revert if designer prefers always-visible)
- Accessible keyboard/touch navigation

### Out of scope (this phase)

- Search autocomplete analytics / popular searches
- Mini-cart dropdown
- Transparent header variant (`SWTTransparentHeader` — separate feature)
- Menu content migration from live site (separate content task; structure documented here)
- Lazy-fetch mega menu HTML via AJAX (future performance enhancement)

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  FSE templates (page.html, archive.html, …)                 │
│    └── parts/header.html                                    │
│          └── wp:pattern chairforce/header                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  patterns/header.php                                        │
│    └── chairforce_render_site_header()                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  partials/site-header.php  (markup)                         │
│  lib/class-site-header.php (render, fragments, search form) │
│  lib/class-mega-menu.php   (registers walker, hooks)        │
│  includes/menu/            (walker, hooks, item renderers)  │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌──────────────────────────┐    ┌──────────────────────────────┐
│  ACF Header Options (new group) │    │  WP Menus + ACF nav_menu_item │
│  • announcement, logo, phone    │    │  • chairforce-primary-nav      │
│  • search placeholder           │    │  • chairforce-utility-nav      │
│  • header_hide_on_scroll        │    │    (Showrooms, Account, Quotes) │
└──────────────────────────┘    └──────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  Assets                                                     │
│  src/sass/header/ + src/sass/menu/                          │
│  src/js/site-header.js (scroll, mobile drawer, AJAX search)  │
└─────────────────────────────────────────────────────────────┘
```

### Why not blocks for header?

Client is result-oriented. Mixing block-composed header + classic mega menu creates two editing surfaces and two render pipelines. Full PHP gives one DOM, one place for AJAX search and cart fragments, and matches Figma without fighting `core/navigation` limits.

---

## Mega menu layout patterns

All desktop layouts now documented in Figma:

| Pattern | Menus | Structure | Images |
|---|---|---|---|
| **A — Grouped text** | Chairs | Headings: TYPE (2 sub-cols), STYLES, MATERIALS | No |
| **B — Flat thumbnail grid** | Tables & Bench Seating, Shop by Space, Storage | 3–4 equal columns, no headings | Yes |
| **C — Grouped thumbnails** | Stools, Table Tops & Bases, Outdoor, Office | 2 section headings × 2 sub-columns | Yes |

~**100** image+link pairs total across all menus.

---

## Pre-build decisions (locked)

| Topic | Decision |
|---|---|
| **Breakpoint** | **767px** (`$breakpoints.navigation`) — matches theme token; Figma 375 mobile / 1440 desktop |
| **Utility links** | **Menu items** in `chairforce-utility-nav` location — URLs + labels via menu item ACF (`label_mobile`, etc.). Not theme option fields. **Cart** rendered separately (WooCommerce). |
| **Header ACF** | **New group** `group_chairforce_header_options` on dedicated Header options sub-page (separate from general `group_theme_options`) |
| **Column layout** | Port shineon **`column_span`** + **`child_columns`** fields on menu items (Pattern A/C sub-columns) |
| **Mega menu open** | **Click/tap** to open panels (touch-safe). Keyboard: Enter/Space opens, Escape closes. Not hover-primary. |
| **Hide on scroll** | **Default on** — hide down, show up. `header_hide_on_scroll` toggle; revert if designer prefers always-visible. |
| **Port source** | shineon confirmed available at `wp-content/themes/shineon` |
| **Right-pulled nav items** | **Same `chairforce-primary-nav` menu** — Shop by Space, New Arrivals, Sale are menu items, not a separate location. Desktop: CSS pulls `nav_align: right` items to the end of the row. Mobile: same items in drawer list (natural menu order). |
| **Missing cat thumb** | Fallback to existing `placeholder_image_default` in `group_theme_options` |
| **Naming** | **`chairforce-` prefix** on menu locations, image sizes, DOM IDs — avoid Woodmart `main-menu` / `mobile-menu` (see § Naming conventions) |

---

## Naming conventions (avoid Woodmart collision)

The live site uses **Woodmart**, which registers menu locations `main-menu` and `mobile-menu`. Chairforce must not reuse those slugs or generic hooks that Woodmart CSS/JS may target.

### WordPress menu location slugs

Register in `lib/class-after-setup-theme.php`. Use PHP constants (e.g. in `includes/constants.php`):

| Constant | Slug | Admin label |
|---|---|---|
| `CHAIRFORCE_MENU_PRIMARY` | **`chairforce-primary-nav`** | Primary Navigation |
| `CHAIRFORCE_MENU_UTILITY` | **`chairforce-utility-nav`** | Utility Navigation |

```php
register_nav_menus( [
    CHAIRFORCE_MENU_PRIMARY => __( 'Primary Navigation', 'chairforce' ),
    CHAIRFORCE_MENU_UTILITY => __( 'Utility Navigation', 'chairforce' ),
] );
```

**Do not use:** `main-menu`, `mobile-menu`, `primary-menu`, `secondary-menu`, `primary-nav`, `utility-nav`, `menu-thumb` (unprefixed / Woodmart / generic).

ACF field group location rules must reference **`chairforce-primary-nav`** and **`chairforce-utility-nav`**.

### Image size slug

| Slug | Size |
|---|---|
| **`chairforce-menu-thumb`** | 108×108 crop |

**Do not use:** bare `menu-thumb` (too generic).

### DOM — IDs and root classes

Use **`site-header__*`** BEM under `.site-header`. Prefix standalone IDs with **`chairforce-`**.

| Element | ID | Root / block class |
|---|---|---|
| Mobile drawer | **`chairforce-mobile-drawer`** | `.site-header__mobile-drawer` |
| Mega menu panel (per item) | **`chairforce-mega-menu-{slug}`** | `.site-header__mega-menu` |
| Hamburger `aria-controls` | points to `chairforce-mobile-drawer` | `.site-header__menu-toggle` |
| Search input | `chairforce-header-search` | `.site-header__search-input` |

**Do not use as IDs or sole root classes:** `mobile-menu`, `main-menu`, `mega-menu` (Woodmart / generic collision risk).

### CSS modifiers (walker output)

| Purpose | Class |
|---|---|
| Right-pulled top-level item (desktop) | `.site-header__nav-item--align-right` |
| Has mega panel | `.site-header__nav-item--has-mega` |
| Sale highlight | `.site-header__nav-link--sale` |
| Desktop/mobile label | `.site-header__menu-label`, `.site-header__menu-label--desktop`, `.site-header__menu-label--mobile` |
| Submenu thumbnail | `.site-header__menu-thumb` |

WordPress core `.menu-item`, `.menu-item-has-children` etc. may remain on `<li>` — do not strip; scope custom Sass under `.site-header`.

### JS selectors

Target only prefixed/scoped hooks, e.g. `#chairforce-mobile-drawer`, `.site-header__menu-toggle`, `.site-header__mega-menu` — never `#mobile-menu` or `.mobile-menu` alone.

---

## ACF data model

### Header options (`group_chairforce_header_options`)

**New JSON group** + ACF options sub-page (register under Theme Options in `lib/class-acf.php`). Keep general theme settings in `group_theme_options` unchanged.

| Field | Type | Purpose |
|---|---|---|
| `announcement_text` | text/textarea | Promo bar copy |
| `announcement_link` | link | Optional CTA |
| `logo` | image | Header logo |
| `phone_number` | text | e.g. 1300 272 926 |
| `phone_hours` | text | e.g. Mon–Fri 9–5 |
| `search_placeholder` | text | e.g. Search chairs, tables, stools… |
| `header_hide_on_scroll` | true/false | Default **true** |

**Not in header options:** utility URLs/labels (menu items), cart (WooCommerce).

---

## Menu locations

| Location | Contents |
|---|---|
| **`chairforce-primary-nav`** | **All** desktop/mobile nav items: product categories (Chairs … Storage) **and** right-pulled items (Shop by Space, New Arrivals, Sale). Single menu — one tree for mobile drawer and desktop nav row. |
| **`chairforce-utility-nav`** | Showrooms, Account, Quotes — desktop utility cluster + mobile drawer utility list (separate from category nav) |

Cart icon/link is **not** a menu item — hardcoded WooCommerce `/cart/` + fragment badge in `partials/site-header.php`.

**No `secondary-nav` location.** Right alignment on desktop is presentation only (see `nav_align` below).

### Desktop right-pulled items (CSS)

Top-level items with ACF **`nav_align: right`** (Shop by Space, New Arrivals, Sale):

- Walker outputs class `site-header__nav-item--align-right` on the `<li>`
- Desktop Sass: first `.site-header__nav-item--align-right` in the flex row gets `margin-inline-start: auto`, pushing it and following siblings to the right
- Mobile: no special alignment — items appear in menu order in the drawer root list

Editors: keep right-pulled items **consecutive at the end** of the primary menu.

### Menu item fields (`group_chairforce_menu_options`)

Location: `nav_menu_item` assigned to `chairforce-primary-nav` or `chairforce-utility-nav`

| Field | Type | Purpose |
|---|---|---|
| `link_type` | select | Controls rendering (see below) |
| `label_mobile` | text | Mobile label override; empty = use menu title (e.g. Quotes → Get a Quote) |
| `nav_align` | select | Top-level only: `default` (left) or `right` (desktop right-pulled group — Shop by Space, New Arrivals, Sale) |
| `image` | image | Override category thumbnail |
| `layout_variant` | select | Top-level only: `grouped-text`, `flat-grid`, `grouped-thumbnails` |
| `grid_columns` | select | Top-level Pattern B: 3 or 4 |
| `column_span` | select | Port from shineon — item column span within section |
| `child_columns` | select | Port from shineon — sub-column count under heading (Pattern A/C) |
| `visibility` | select | `hide-mobile`, `hide-desktop`, or none |

#### Link types

| Type | Use for |
|---|---|
| `default` | Plain text link (Pattern A items) |
| `heading` | Section label (TYPE, STYLES, MATERIALS, …) — non-link |
| `thumbnail-link` | Image + label link (Patterns B/C) |
| `highlight-link` | Sale — red text |
| `utility-link` | Showrooms, Account, Quotes — icon + label in desktop cluster and mobile drawer |
| `divider` | `<hr>` separator (mobile utility section) |

**Removed from shineon port:** `has-tabs`, `has-tabs-with-block`, `tab`, `block`, `post`, `cards` — not used in Figma; reduces complexity.

Register image size: **`chairforce-menu-thumb`** — **108×108** hard crop (2× Figma display size for retina). Display at **54×54px** in CSS. Do not use 80×80.

For `thumbnail-link` items linked to `product_cat`:

1. ACF `image` override if set
2. Else WooCommerce category thumbnail (`get_term_meta( $term_id, 'thumbnail_id', true )`)
3. Else `placeholder_image_default` from `group_theme_options` (existing theme option)

All resolved images use the `chairforce-menu-thumb` size in `wp_get_attachment_image()`.

**Mobile:** omit `<img>` entirely when `wp_is_mobile()` — mobile drill-down is text-only (Figma). Do not rely on `display:none`.

---

## File plan (Chairforce theme)

### New PHP

| File | Purpose |
|---|---|
| `lib/class-site-header.php` | Main header class; render, cart fragment, enqueue |
| `lib/class-mega-menu.php` | Menu hooks registration, walker bootstrap |
| `partials/site-header.php` | Header HTML template |
| `includes/menu/walker/class-primary-walker.php` | Custom walker (port from shineon) |
| `includes/menu/walker/primary-walker-start.php` | Submenu container open + mobile back btn |
| `includes/menu/walker/primary-walker-end.php` | Submenu container close |
| `includes/menu/menu-hooks.php` | Filters: classes, titles, item output |
| `includes/menu/chairforce-menu-thumbnail-link.php` | Thumbnail+label renderer |
| `includes/menu/menu-utility-link.php` | Utility cluster + mobile drawer item renderer |
| `includes/helper-functions.php` | Add `chairforce_render_site_header()` |
| `includes/rest-api/product-search.php` | REST endpoint for AJAX search |

### Modified PHP

| File | Change |
|---|---|
| `lib/class-init.php` | Instantiate `Site_Header`, `Mega_Menu` |
| `includes/constants.php` | Add `CHAIRFORCE_MENU_PRIMARY`, `CHAIRFORCE_MENU_UTILITY` |
| `lib/class-after-setup-theme.php` | Register menu locations via constants |
| `lib/class-acf.php` | Register Header options sub-page |
| `patterns/header.php` | Replace block markup with `chairforce_render_site_header()` |
| `acf-json/group_chairforce_header_options.json` | New header options field group |
| `acf-json/group_chairforce_menu_options.json` | New menu item field group (incl. shineon column fields) |

### New Sass

Follow [Menu System rule](../../.cursor/rules/17-menu-system.mdc) — variables + mixins in `menu/`, header shell in `header/`.

**`src/sass/header/`** — shell only (no mega menu logic):

| File | Purpose |
|---|---|
| `src/sass/header/_index.scss` | Header entry |
| `src/sass/header/_announcement.scss` | Promo bar |
| `src/sass/header/_primary-row.scss` | Logo, search, phone, utilities |
| `src/sass/header/_search.scss` | Search input + AJAX results dropdown |
| `src/sass/header/_scroll-behaviour.scss` | Hide on scroll |

**`src/sass/menu/`** — self-contained menu system (see rule 17):

| File | Purpose |
|---|---|
| `src/sass/menu/_index.scss` | Menu entry + import order |
| `src/sass/menu/_variables.scss` | Menu tokens mapped from global settings (`$menu-breakpoint-key`, thumb 54px, panel colours) |
| `src/sass/menu/_mixins.scss` | `breakpoint-menu-up/down`, `menu-desktop-only`, `menu-mobile-only`, panel + a11y mixins |
| `src/sass/menu/_structure.scss` | Shared list/panel layout |
| `src/sass/menu/_desktop.scss` | Nav row, align-right, mega panels, `[aria-expanded]` / `.is-open` |
| `src/sass/menu/_mobile-drawer.scss` | `#chairforce-mobile-drawer`, drill-down |
| `src/sass/menu/_labels.scss` | `.site-header__menu-label` breakpoint toggle |
| `src/sass/menu/_animations.scss` | Mega panel stagger (port `megaMenuAnimation` to `.site-header__mega-menu`) |
| `src/sass/menu/_link-types/_thumbnail-link.scss` | Thumb 54×54 display (`chairforce-menu-thumb`) |
| `src/sass/menu/_link-types/_heading.scss` | Section headings |
| `src/sass/menu/_link-types/_highlight-link.scss` | Sale red |
| `src/sass/menu/_link-types/_utility-link.scss` | Utility cluster + drawer items |
| `src/sass/menu/_layout-patterns/_grouped-text.scss` | Pattern A |
| `src/sass/menu/_layout-patterns/_flat-grid.scss` | Pattern B |
| `src/sass/menu/_layout-patterns/_grouped-thumbnails.scss` | Pattern C |

Import `@import "header"` and `@import "menu"` in `src/sass/index.scss`. **Do not port** shineon stale partials (tabs, block, cards).

### New JS

| File | Purpose |
|---|---|
| `src/js/site-header.js` | Mobile drawer, scroll hide, submenu panels |
| `src/js/product-search.js` | Debounced AJAX search, results dropdown, keyboard nav |

Import in `src/public.js`. Prefer **vanilla JS** (no jQuery) for new code.

### Patterns / parts (minimal change)

`parts/header.html` stays a thin wrapper. **`patterns/header.php` must not retain any block markup** — the existing scaffold (logo + `core/navigation` + CTA button) is a dev placeholder only and must be fully replaced in Phase 1.

```html
<!-- parts/header.html — unchanged structure -->
<!-- wp:pattern {"slug":"chairforce/header"} /-->
```

```php
<?php
/**
 * Title: header
 * Slug: chairforce/header
 * Inserter: no
 */
chairforce_render_site_header();
```

---

## DOM rules (from stakeholder decisions)

### Desktop/mobile labels

Both labels always in HTML; CSS toggles visibility at `$breakpoints.navigation` (767px):

```html
<span class="site-header__menu-label site-header__menu-label--desktop"><?php echo esc_html( $item->title ); ?></span>
<?php if ( $mobile_label ) : ?>
  <span class="site-header__menu-label site-header__menu-label--mobile"><?php echo esc_html( $mobile_label ); ?></span>
<?php endif; ?>
```

See [§ Submenu thumbnails (decided)](#submenu-thumbnails-decided).

### Mobile chevrons

| Context | Chevron? |
|---|---|
| Root panel — item has children (opens drill-down) | Yes |
| Drill-down panel — all items (leaf links) | **No** |
| New Arrivals, Sale (direct links) | No |

### Search

- `<form role="search">` with `aria-expanded`, `aria-controls` on results list
- Results: `<ul role="listbox">` beneath input
- Debounce ~300ms; min 2 characters; `post_type=product`
- Enter submits to WC search results page as fallback

### Cart badge

- Desktop: inside utility cluster (after `chairforce-utility-nav` items)
- Mobile: on cart icon in primary row
- Updated via `woocommerce_add_to_cart_fragments`

### Responsive breakpoint

**767px** (`$breakpoints.navigation`) — desktop vs mobile header, label CSS toggle, drawer vs nav row.

### Desktop mega menu interaction

- **Click/tap** opens panel (touch-safe; not hover-primary)
- **Keyboard:** Enter/Space on trigger opens; Escape closes; one panel open at a time
- Triggers with panels: `<button aria-expanded aria-controls>` (not plain links)

### Desktop nav row (single menu)

- One `wp_nav_menu( CHAIRFORCE_MENU_PRIMARY )` — categories and right-pulled items in the same `<ul>`
- Right-pulled items: ACF `nav_align: right` → class `site-header__nav-item--align-right`; desktop CSS only

---

## Implementation phases

### Phase 1 — Foundation & header shell

**Goal:** Visible header matching Figma layers 1–2; no mega menu yet.

- [ ] Define `CHAIRFORCE_MENU_PRIMARY` / `CHAIRFORCE_MENU_UTILITY` constants; register menu locations
- [ ] Create `Site_Header` class + `partials/site-header.php`
- [ ] ACF header options sub-page + `group_chairforce_header_options` (announcement, logo, phone, search placeholder, scroll toggle)
- [ ] Register Header options sub-page in `lib/class-acf.php`
- [ ] **Replace** `patterns/header.php` — remove the current placeholder block markup (`core/site-logo`, `core/navigation`, `core/buttons` “Get Started”) and repurpose the pattern to PHP-only: `chairforce_render_site_header();` only. Do not leave the FSE block header in place alongside the new PHP header (two render pipelines, duplicate nav, wrong layout).
- [ ] Confirm `parts/header.html` still references only `chairforce/header` (no direct block markup in the template part)
- [ ] Sass: announcement, primary row, utilities, mobile icon row, search row (`src/sass/header/`)
- [ ] Scaffold `src/sass/menu/_variables.scss` + `_mixins.scss` (`breakpoint-menu-up/down`, `$menu-breakpoint-key: navigation`)
- [ ] Placeholder nav (simple links or empty) for layout QA
- [ ] Measure and set `$header-height-desktop`, `$header-height-mobile` from built markup

**Exit criteria:** Header visually matches Figma desktop/mobile screenshots (without mega menu panels). `patterns/header.php` contains no `core/*` blocks — only the PHP render callback.

---

### Phase 2 — AJAX product search

**Goal:** Working product search with dropdown results.

- [ ] REST route: `chairforce/v1/product-search?s={term}`
- [ ] Return: product ID, title, URL, thumbnail, price (optional)
- [ ] `product-search.js`: debounce, dropdown, arrow keys, Escape, click outside
- [ ] Sass: results dropdown
- [ ] Fallback: form submit to `?s={term}&post_type=product`

**Exit criteria:** Typing in search shows product results beneath input on desktop and mobile.

---

### Phase 3 — Menu infrastructure

**Goal:** ACF fields, walker, hooks — render menu structure without full mega styling.

- [ ] Port and clean `Primary_Walker` → `Chairforce\Primary_Walker`
- [ ] Port `menu-hooks.php` — strip unused link types; **include shineon `column_span` / `child_columns`**
- [ ] Add `chairforce-menu-thumbnail-link.php` renderer
- [ ] Add `utility-link` renderer (desktop cluster + mobile drawer from `chairforce-utility-nav`)
- [ ] ACF JSON: `group_chairforce_menu_options` (incl. column fields from shineon)
- [ ] Register `chairforce-menu-thumb` image size (**108×108** crop) via `add_image_size()`
- [ ] Image resolver: ACF override → WC term thumbnail → placeholder; output via `wp_get_attachment_image( …, 'chairforce-menu-thumb' )`
- [ ] **`chairforce-menu-thumbnail-link.php`:** omit `<img>` when `wp_is_mobile()`; render thumb + lazy load on desktop only
- [ ] Desktop/mobile label spans in walker output (CSS toggle — not `wp_is_mobile()`)
- [ ] Walker: output `site-header__nav-item--align-right` from ACF `nav_align` field (top-level only)
- [ ] Single `wp_nav_menu( CHAIRFORCE_MENU_PRIMARY )` in nav row (categories + Shop by Space, New Arrivals, Sale)
- [ ] `wp_nav_menu( CHAIRFORCE_MENU_UTILITY )` rendered separately (utility cluster + mobile drawer utilities)

**Exit criteria:** Menu renders in nav row with correct hierarchy and labels. Desktop shows `chairforce-menu-thumb` images from category links; mobile markup has no submenu `<img>` tags.

---

### Phase 4 — Desktop mega menu

**Goal:** Full-width panels matching Patterns A, B, C.

- [ ] Sass Pattern A/B/C in `_layout-patterns/`; desktop shell in `_desktop.scss`
- [ ] Sass: `.site-header__nav-item--align-right` desktop pull; open states via `[aria-expanded='true']`, `.is-open`, `:focus-within` (not hover-only)
- [ ] `layout_variant` ACF field drives panel CSS class on top-level `<li>`
- [ ] Full-width fixed panel below nav row
- [ ] **Click/tap** to open (not hover-primary); Escape to close; one panel open at a time
- [ ] `loading="lazy"` + `decoding="async"` on submenu images
- [ ] `content-visibility` or `hidden` on closed panels
- [ ] Sale red styling on `highlight-link`
- [ ] Staggered open animation (port `megaMenuAnimation` logic to classic menu selectors)

**Exit criteria:** All 8 top-level desktop mega menus match Figma layouts.

---

### Phase 5 — Mobile drawer

**Goal:** Hamburger menu matching Figma root + drill-down.

- [ ] `_mobile-drawer.scss`: drawer open/close; `:focus-visible` on links/buttons
- [ ] `site-header.js`: open/close drawer, body scroll lock, focus trap
- [ ] Root panel: MENU + ×, category list, utilities from `chairforce-utility-nav` (Showrooms, Get a Quote, Account)
- [ ] Drill-down panel: BACK + ×, "Explore {Category}" title, red section headings
- [ ] Chevrons: root items with children only; **no chevron** on drill-down leaves
- [ ] Mobile label CSS active
- [ ] Cart + phone remain in sticky header (not in drawer)
- [ ] Search remains in sticky header row (not in drawer)

**Exit criteria:** Mobile menu matches `Mega-menu-mobile-root.png` and `Mega-menu-mobile-chairs.png`.

---

### Phase 6 — WooCommerce integration & scroll

**Goal:** Cart badge, hide-on-scroll, polish.

- [ ] Cart fragment for `.site-header__cart-count` (desktop + mobile selectors)
- [ ] Hide-on-scroll JS (down = hide, up = show); default **on**; respect `header_hide_on_scroll` option
- [ ] Admin bar offset for fixed/sticky positioning
- [ ] Lucide icons for utilities (theme icon system — Surface 3 mixins or icon font)
- [ ] Keyboard accessibility audit: tab order, Escape, aria-expanded on submenus

**Exit criteria:** Cart count updates on add-to-cart; header hides/shows on scroll.

---

### Phase 7 — Content build & QA

**Goal:** Populate menus; cross-browser and a11y QA.

- [ ] Build **one** primary menu: categories + Shop by Space, New Arrivals, Sale (set `nav_align: right` on the latter three)
- [ ] Link category items to `product_cat` terms; add utility items to `chairforce-utility-nav`; verify thumbnails
- [ ] Set mobile labels (Office → Office Furniture, Quotes → Get a Quote, etc.)
- [ ] Set `layout_variant` per top-level item
- [ ] Export menu + ACF for staging deploy
- [ ] QA: desktop all panels, mobile all drill-downs, search, cart, keyboard, VoiceOver
- [ ] Performance: verify desktop thumbs at 54×54 display (`chairforce-menu-thumb` 108×108), no submenu `<img>` on mobile UA, panel hidden state, no layout shift

**Exit criteria:** Client can maintain menu; site matches Figma; passes a11y smoke test.

---

## Port map: shineon → Chairforce

| shineon | Chairforce | Notes |
|---|---|---|
| `includes/menu/walker/class-primary-walker.php` | Same path, `Chairforce\` namespace | Clean up |
| `includes/menu/menu-hooks.php` | Same | Remove tab/block/cards cases |
| `includes/menu/menu-post.php` | **Drop** | Replace with `chairforce-menu-thumbnail-link.php` |
| `blocks/layout-header-main/` | **Drop** | Replace with `partials/site-header.php` |
| `src/sass/menu/_menu-item-tabs.scss` | **Drop** | Not in Figma |
| `src/js/menu.js` | Rewrite as `site-header.js` | Vanilla JS |
| `acf-json/group_theme_primary_menu_options.json` | `group_chairforce_menu_options.json` | Simplified fields |
| `wp_is_mobile()` | **Use for submenu thumbs** — omit `<img>` on mobile UA | Labels: CSS only |

---

## Accessibility requirements

- Semantic landmarks: `<header>`, `<nav aria-label="…">`
- Mega menu triggers: `<button aria-expanded aria-controls>` for items with panels
- Mobile drawer: focus trap, return focus to hamburger on close
- Search results: `role="listbox"`, active item `aria-selected`
- Skip link preserved (theme default)
- No Elementor-style unbounded widget markup inside navigation
- Colour contrast: Sale red on white ≥ WCAG AA

---

## Performance checklist

- [ ] `chairforce-menu-thumb` registered at **108×108**; CSS displays at **54×54px** (Figma)
- [ ] Submenu `<img>` **omitted when `wp_is_mobile()`** — mobile drawer is text-only
- [ ] Desktop submenu images: `loading="lazy"`, `decoding="async"`, `chairforce-menu-thumb` size
- [ ] Closed mega panels not visible to screen readers (`hidden` or `inert`)
- [ ] Consider `content-visibility: hidden` on closed panels
- [ ] Labels: both spans in HTML; CSS toggles at breakpoint (not `wp_is_mobile()`)
- [ ] Monitor DOM weight on desktop (~100 images acceptable with lazy load); mobile UA skips images when cache splits by UA

---

## Submenu thumbnails (decided)

| Decision | Outcome |
|---|---|
| **Registered size** | `chairforce-menu-thumb` — **108×108** hard crop (2× Figma for retina) |
| **Display size** | **54×54px** in CSS — matches Figma |
| **Avoid** | 80×80 (awkward middle); serving `large` or full-size uploads |
| **Mobile markup** | **Omit `<img>` when `wp_is_mobile()`** — mobile drill-down is text-only |
| **Desktop markup** | `<img>` via `wp_get_attachment_image( …, 'chairforce-menu-thumb' )` + lazy load |

```php
// menu-thumbnail-link.php — thumbnail output only
if ( ! wp_is_mobile() ) {
    echo wp_get_attachment_image( $attachment_id, 'chairforce-menu-thumb', false, [
        'loading'  => 'lazy',
        'decoding' => 'async',
        'class'    => 'site-header__menu-thumb',
    ] );
}
```

### Caching note

- **UA-split cache:** mobile HTML has no submenu images — full bandwidth savings.
- **Shared cache:** mobile may receive desktop HTML with `<img>` tags present — graceful degradation only; no broken UX. UA-split cache is a bonus, not a requirement.

---

## Risks & mitigations

| Risk | Mitigation |
|---|---|
| shineon code drift (stale Sass, missing ACF fields) | Port only listed files; rewrite ACF JSON fresh |
| Header height vars wrong | Measure after Phase 1 build |
| Menu content migration effort | Phase 7 separate; structure ready in Phase 3 |
| WC category without thumbnail | `placeholder_image_default` from existing theme options |
| Designer rejects hide-on-scroll | `header_hide_on_scroll` option (default on) — disable without code change |

---

## Related docs

- [`context/header-mega-menu/01-requirements-spec.md`](../header-mega-menu/01-requirements-spec.md)
- [`context/header-mega-menu/02-dom-structure.md`](../header-mega-menu/02-dom-structure.md)
- [`context/header-mega-menu/04-approach-evaluation.md`](../header-mega-menu/04-approach-evaluation.md)
- [`context/header-mega-menu/05-stakeholder-decisions.md`](../header-mega-menu/05-stakeholder-decisions.md)
