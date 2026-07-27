# 05 — Stakeholder Decisions (Resolved)

Decisions confirmed during approach evaluation (July 2026). These supersede open questions in doc 03.

---

## Approach

| Decision | Outcome |
|---|---|
| Mega menu technique | **Classic WP Menu + ACF fields on `nav_menu_item`** (shineon pattern, ported to Chairforce) |
| Header rendering | **Full PHP header** — not block-composed header, not ACF header block |
| FSE role | Thin wrapper only: `parts/header.html` → `patterns/header.php` → `chairforce_render_site_header()` |
| Block Navigation | **Rejected** for mega menu (lasersight approach) |
| Client priority | Result-oriented; FSE/block nav is not a requirement |

### Rationale

- ~100 submenu images across 4 layout patterns → content-management problem; WP Menus + ACF scales better than block editor
- Replacing current **Classic Menu + Elementor template** mega menus (a11y nightmare) with structured ACF + controlled PHP markup
- Full PHP header avoids split editing surfaces (blocks for shell + classic for nav)
- Single render pipeline for search AJAX, cart badge, announcement bar, and navigation

---

## Content & images

| Decision | Outcome |
|---|---|
| Category images | **Required on desktop mega menus** — Patterns B/C; mobile drill-down is text-only (Figma) |
| Image source in walker | Auto-pull from linked category term thumbnail; ACF `image` field as optional override |
| Registered size | **`chairforce-menu-thumb`** — **108×108** hard crop (2× Figma display for retina) |
| Display size | **54×54px** in CSS (`.site-header__menu-thumb`) — matches Figma |
| Mobile markup | **Omit `<img>` when `wp_is_mobile()`** — do not use `display:none` |
| Desktop markup | `wp_get_attachment_image( …, 'chairforce-menu-thumb' )` with `loading="lazy"`, `decoding="async"` |
| Menu maintenance | **Client** maintains menu in Appearance → Menus |
| Current live setup | Classic Menu + top-level item selects Elementor template for submenu — being retired |

---

## Labels (desktop vs mobile)

| Decision | Outcome |
|---|---|
| Different labels per breakpoint | **Yes** — e.g. desktop "Office" / mobile "Office Furniture"; desktop "Quotes" / mobile "Get a Quote" |
| ACF fields | `label_mobile` on menu items (menu title = desktop label) |
| Rendering | **CSS toggle** — both labels in markup; show/hide at breakpoint. **Do not use `wp_is_mobile()` for visible label text** (cache/CDN friendly) |

```html
<span class="menu-label menu-label--desktop">Office</span>
<span class="menu-label menu-label--mobile">Office Furniture</span>
```

---

## Mobile menu behaviour

| Decision | Outcome |
|---|---|
| Menu depth | **Two levels only** — root list → category drill-down panel |
| Leaf item chevrons | **No chevron** on drill-down panel items (they link directly to category pages) |
| Root chevrons | **Yes** — only on root items that open a drill-down panel |
| Third drill-down level | **Does not exist** |

---

## Search

| Decision | Outcome |
|---|---|
| Search type | **WooCommerce product search** |
| UX | **AJAX search** with results dropdown underneath the input (desktop + mobile) |
| Placement | Always visible — inline in desktop primary row; dedicated row on mobile |

---

## Cart

| Decision | Outcome |
|---|---|
| Cart badge | **Required on both desktop and mobile** |
| Implementation | WooCommerce cart fragments (`woocommerce_add_to_cart_fragments`) |

---

## Header scroll behaviour

| Decision | Outcome |
|---|---|
| Sticky / hide on scroll | **Hide on scroll down, show on scroll up** — default for build |
| Toggle | `header_hide_on_scroll` in header options — disable if designer prefers always-visible |

---

## Navigation structure

| Decision | Outcome |
|---|---|
| **Primary nav** | **Single `chairforce-primary-nav` menu** — product categories **and** Shop by Space, New Arrivals, Sale in one tree (required for mobile drawer) |
| **Desktop right-pulled items** | Same menu items with ACF **`nav_align: right`** + CSS (`.site-header__nav-item--align-right`) — not a separate menu location |
| Utility links | **`chairforce-utility-nav`** — Showrooms, Account, Quotes (`utility-link` type). Separate from category nav. |
| **Naming** | **`chairforce-` prefix** on menu slugs, image size, DOM IDs — avoid Woodmart `main-menu` / `mobile-menu`. See plan § Naming conventions. |
| Cart | WooCommerce — rendered in header partial, not a menu item |
| Sale styling | Red text via `highlight-link` on the Sale menu item |
| Breakpoint | **767px** (`$breakpoints.navigation`) |
| Mega menu open | **Click/tap** (not hover-primary); keyboard Enter/Space + Escape |
| Column layout | Port shineon **`column_span`** + **`child_columns`** on menu items |
| All mega menu layouts | **Documented** — see layout map in doc 01 and 04 |

---

## Reference themes

| Theme | Path | Role |
|---|---|---|
| shineon | `wp-content/themes/shineon` | **Port source** — confirmed available; ACF menu, walker, menu-hooks, column fields |
| lasersight | `wp-content/themes/lasersight` | Reference only (block approach — rejected) |

---

## Implementation plan

See [`context/plans/header-mega-menu-plan.md`](../plans/header-mega-menu-plan.md).
