# 01 — Header & Mega Menu Requirements Spec

Derived from Figma component screenshots in `context/figma/components/`.  
Viewport references from Figma frames: desktop **1440px**, mobile **375px**.

---

## 1. Header overview

The header is a **three-layer system on desktop** and a **three-layer system on mobile**, but the layers differ in content and layout.

| Layer | Desktop | Mobile |
|---|---|---|
| 1 — Announcement bar | Single-line promo message | Two-line promo message (wraps) |
| 2 — Primary row | Logo · Search · Phone · Utility icons | Logo · Phone · Cart · Hamburger |
| 3 — Navigation | Full category nav + right-pulled items | Search bar (full width, own row) |

On mobile, category navigation lives inside the hamburger drawer — not in the sticky header.

---

## 2. Announcement bar

### Content
- **Message:** “Free shipping on orders over $999 • Same-day dispatch on in-stock items”
- **Icon:** Delivery truck (leading icon on desktop; centered above/beside text on mobile)

### Visual
| Property | Value |
|---|---|
| Background | Black (`--cf-black` / `black` palette) |
| Text | White |
| Typography | Small body; “Free shipping” may appear slightly bolder on mobile |

### Behaviour
- Always visible above the main header
- Content should be editable (theme option or block pattern field) — exact CMS mechanism TBD
- No dismiss/close control shown in Figma

---

## 3. Desktop header — primary row

### Layout (left → right)

```
[ Logo ]  [ ——— Search input + button ——— ]  [ Phone block ]  [ Showrooms | Account | Quotes | Cart ]
```

### 3.1 Logo
- Chairforce wordmark (red) + circular emblem
- Links to home
- Fixed height; scales within header row

### 3.2 Search bar
- **Placeholder:** “Search chairs, tables, stools…”
- **Input:** White background, light grey border, wide (dominant horizontal space in the row)
- **Submit button:** Square, dark navy (`primary`), white magnifying-glass icon, flush right of input
- **Behaviour:** Standard product/site search submit — navigates to search results (WooCommerce-aware search assumed)
- **Always visible** on desktop; not collapsed behind an icon

### 3.3 Phone / contact block
- Phone icon
- Two lines of text:
  - Line 1 (muted): “Mon–Fri 9–5”
  - Line 2 (bold): “1300 272 926”
- `tel:` link on the number

### 3.4 Utility icon cluster (right group)
Four items, each **icon above label**, evenly spaced:

| Label | Icon | Expected destination |
|---|---|---|
| Showrooms | Map pin | Showrooms page / locator |
| Account | User | My Account (WooCommerce) |
| Quotes | Document/list | Quote request flow |
| Cart | Shopping cart | Cart page |

- Labels are visible (not icon-only)
- Cart may need dynamic item count badge — not shown in Figma but likely required for WooCommerce

---

## 4. Desktop header — navigation row

### Layout

```
[ Chairs ▾ ] [ Stools ▾ ] [ Tables & Bench Seating ▾ ] [ Table Tops & Bases ▾ ] [ Outdoor Furniture ▾ ] [ Office ▾ ] [ Storage ▾ ]     [ Shop by Space ▾ ] [ New Arrivals ] [ Sale ]
```

### 4.1 Primary nav (left-aligned)
| Item | Has dropdown |
|---|---|
| Chairs | Yes (mega menu) |
| Stools | Yes |
| Tables & Bench Seating | Yes |
| Table Tops & Bases | Yes |
| Outdoor Furniture | Yes |
| Office | Yes |
| Storage | Yes |

- Each dropdown item shows a **down chevron** after the label
- Hover/focus opens the mega menu panel (see §6)

### 4.2 Right-pulled nav (desktop presentation only)

Shop by Space, New Arrivals, and Sale are **items in the same `chairforce-primary-nav` menu** as the categories (not a separate menu). On desktop they are **visually right-aligned** via CSS (`nav_align: right` → `.site-header__nav-item--align-right`). On mobile they appear in the **same drawer list** in menu order.

| Item | Has dropdown | Notes |
|---|---|---|
| Shop by Space | Yes | Mega menu (Pattern B variant) |
| New Arrivals | No | Direct link |
| Sale | No | Direct link; **`highlight-link`** (red text) |

### 4.3 Nav row styling
- White background, full width
- Thin grey bottom border separating nav from page content
- Nav link typography: dark body/heading colour, medium weight
- Active/hover state for open mega menu parent: visually distinct (exact treatment TBD — likely underline or colour shift)

---

## 5. Mobile header

### 5.1 Announcement bar
Same content as desktop; text wraps to **two lines**:
1. “**Free shipping** on orders over $999”
2. “Same-day dispatch on in-stock items”

### 5.2 Primary row

```
[ Logo ]                                    [ Phone ] [ Cart ] [ ☰ Hamburger ]
```

| Element | Behaviour |
|---|---|
| Logo | Same as desktop, left-aligned |
| Phone | Icon only — tap opens `tel:1300272926` (or dialer) |
| Cart | Icon only — links to cart; badge optional |
| Hamburger | Opens full-screen mobile menu drawer (§7) |

**Not in mobile header row:** Search, Account, Quotes, Showrooms text labels, full phone number block.

### 5.3 Search row (below primary row)
- Full-width search input + submit button
- Same placeholder and button styling as desktop
- **Always visible** below the logo/utility row — not inside the hamburger menu
- This is a key mobile vs desktop difference: search stays in the sticky header; navigation moves to the drawer

---

## 6. Desktop mega menu

Mega menus open as a **full-width panel** directly below the navigation row, white background, aligned to the content width (1440px frame).

Four distinct layout patterns appear in Figma (updated after additional screenshots):

### 6.1 Pattern A — Grouped sections, text only (Chairs example)

**Trigger:** “Chairs” nav item

**Structure:** Up to 3 major columns, each with an **uppercase grey section heading**:

| Column | Heading | Items |
|---|---|---|
| 1 | TYPE | Cafe Chairs, Office Chairs, Dining Chairs, Outdoor Chairs · Armchairs, Stackable Chairs, Visitors Chairs |
| 2 | STYLES | Bentwood Chairs, Crossback Chairs, Parisian Chairs |
| 3 | MATERIALS | Plastic Chairs, Metal Chairs, Timber Chairs, Upholstered Chairs, Chair Cushions |

- Column 1 (TYPE) uses **two sub-columns** of links within the same section
- Section headings: small caps, muted grey, with a thin horizontal rule above the link list
- **No thumbnails** in the Chairs desktop mega menu — text links only

### 6.2 Pattern B — Flat thumbnail grid (Tables & Bench Seating example)

**Trigger:** “Tables & Bench Seating” nav item

**Structure:** Three equal columns, **no section headings**:

| Col 1 | Col 2 | Col 3 |
|---|---|---|
| Folding Tables | Dry Bar Tables | Table & Chair Sets |
| Dining Tables | Picnic Tables | Communal Tables |
| Kitchen Counter Tables | Indoor Tables | Bench Seating |
| Mobile Tables | Alfresco Tables | |
| Bar Tables | Outdoor Tables | |

- Each item = **small square thumbnail image** + text label (horizontal row per item)
- Thumbnails have a thin light border
- Items stack vertically within each column

### 6.3 Mega menu — shared behaviour

| Requirement | Detail |
|---|---|
| Trigger | Hover and keyboard focus on parent nav item |
| Close | Mouse leave, Escape key, focus move, or click outside |
| Width | Full content area width (not a small dropdown) |
| Animation | Staggered reveal of columns/groups (theme already has `megaMenuAnimation` mixin scaffold) |
| Link target | Each item links to a category/archive URL |
| Thumbnails | Required for Pattern B; optional/absent for Pattern A — **editors must be able to assign an image per menu item** where the design calls for it |
| Multiple open | Only one mega menu open at a time |

### 6.3 Pattern C — Grouped sections with thumbnails (Stools, Table Tops & Bases)

**Trigger examples:** “Stools”, “Table Tops & Bases”

**Structure:** Two major sections, each with uppercase grey heading and **two sub-columns** of thumbnail+label links.

**Stools sections:** TYPE (7 items) · MATERIAL (5 items)

**Table Tops & Bases sections:** TABLE TOPS (10 items) · TABLE BASES & LEGS (11 items)

- Same thumbnail+label treatment as Pattern B
- Section headings match Pattern A styling

### 6.4 Pattern B variant — Four-column flat grid (Shop by Space, Storage)

Same as Pattern B but **four equal columns** instead of three.

**Shop by Space:** 10 items (Cafe & Restaurant, Bar & Pub, … Hotel)

**Storage:** 11 items (Stainless Steel Benches, Sinks, … Catering Trolleys)

### 6.5 Pattern C — Grouped thumbnails (Outdoor, Office)

Same structure as Stools / Table Tops & Bases.

**Outdoor:** OUTDOOR SEATING (4 items, 2 cols) · OUTDOOR TABLES (5 items, 2 cols)

**Office:** OFFICE CHAIRS (6 items, 2 cols) · OFFICE TABLES (1 item)

- Desktop label: **Office** · Mobile label: **Office Furniture**

---

## 7. Mobile menu (hamburger drawer)

Full-screen overlay below the sticky header. Two levels shown in Figma: **root** and **drill-down**.

### 7.1 Root level

**Header bar:**
- Left: “MENU” label (grey, uppercase)
- Right: “×” close button

**Primary list** (bold black text, full-width rows, chevron right for expandable items):

| Item | Chevron | Notes |
|---|---|---|
| Chairs | › | Drill-down |
| Stools | › | Drill-down |
| Tables & Bench Seating | › | Drill-down |
| Table Tops & Bases | › | Drill-down |
| Outdoor Furniture | › | Drill-down |
| Office Furniture | › | Drill-down — **label differs from desktop (“Office”)** |
| Storage | › | Drill-down |
| Shop by Space | › | Drill-down |
| New Arrivals | — | Direct link |
| Sale | — | Red text, direct link |

**Secondary / utility list** (regular weight, grey text, leading icon):

| Item | Icon |
|---|---|
| Showrooms | Map pin |
| Get a Quote | Document |
| Account | User |

- Thin grey dividers between all rows
- Utility items appear **only in the mobile drawer**, not in the mobile header row (except phone/cart icons)

### 7.2 Drill-down level (Chairs example)

**Header bar:**
- Left: “‹ BACK” — returns to root
- Right: “×” close — closes entire menu

**Title:** “Explore Chairs” (bold heading)

**Grouped sections** (red uppercase labels — `secondary` colour):

| Section | Items (all with › chevron) |
|---|---|
| TYPE | Cafe Chairs, Office Chairs, Dining Chairs, Outdoor Chairs, Armchairs, Stackable Chairs, Visitors Chairs |
| STYLES | Bentwood Chairs, Crossback Chairs, Parisian Chairs |
| MATERIALS | Plastic Chairs, Metal Chairs, Timber Chairs, Upholstered Chairs, Chair Cushions |

- Single column, vertical stack
- Every item shows a right chevron — **resolved:** chevrons only on root items that open drill-down; **leaf items in drill-down panels have no chevron** (no third level)

### 7.3 Mobile menu behaviour

| Requirement | Detail |
|---|---|
| Open | Hamburger tap |
| Close | × button, BACK at root after drilling back, overlay tap (TBD), Escape |
| Scroll | Panel scrolls independently; header bar (MENU / BACK + ×) stays fixed |
| Body scroll lock | Page behind menu should not scroll while open |
| Height | Full viewport minus sticky header (`mobileMenuMinimumHeight` mixin accounts for this) |
| Animation | Slide-in from right for drill-down levels (standard mobile nav pattern — not explicitly shown but implied by BACK navigation) |
| Search | **Not inside the drawer** — remains in sticky header (§5.3) |

---

## 8. Search — cross-cutting requirements

| Context | Presentation | Submit |
|---|---|---|
| Desktop | Inline in primary header row, wide input | Navy square button with search icon |
| Mobile | Dedicated row below logo/utility row, full width | Same button treatment |
| Inside mega menu | **Not shown** — search is header-only | — |
| Inside mobile drawer | **Not shown** — search is header-only | — |

### Functional assumptions
- **WooCommerce product search** with **AJAX results dropdown** beneath the input (desktop + mobile)
- Debounced live results while typing; Enter submits to search results page as fallback
- Placeholder is configurable (ACF theme options)
- Accessible: labelled input, results `role="listbox"`, button has `aria-label`

---

## 9. Responsive breakpoint

Figma defines two artboards: **1440 desktop** and **375 mobile**.

**Decision:** **767px** — theme `$breakpoints.navigation`. Desktop ≥767px; mobile <767px.

### Element visibility summary

| Element | Desktop (≥ breakpoint) | Mobile (< breakpoint) |
|---|---|---|
| Announcement bar | ✓ | ✓ (wrapped) |
| Search in header | ✓ inline | ✓ own row |
| Phone block (text) | ✓ | ✗ (icon only) |
| Utility labels (Showrooms, Account, Quotes) | ✓ | ✗ (in drawer) |
| Cart | ✓ with label | ✓ icon only |
| Category nav row | ✓ | ✗ (in drawer) |
| Right-pulled items | ✓ in nav row (CSS) | ✓ in drawer (same menu) |
| Hamburger | ✗ | ✓ |

---

## 10. Content model (editor-facing)

Regardless of implementation approach, editors need to manage:

1. **Announcement bar** — text (and optional link)
2. **Phone number and hours** — structured fields
3. **Utility links** — Showrooms, Account, Quotes: **`chairforce-utility-nav`** menu items (ACF `label_mobile`, `utility-link` type). Cart is WooCommerce, not a menu item.
4. **Primary navigation tree** — **one `chairforce-primary-nav` menu**: categories + Shop by Space, New Arrivals, Sale (`nav_align: right` on the latter three for desktop CSS)
5. **Utility navigation** — **`chairforce-utility-nav`**: Showrooms, Account, Quotes
6. **Per menu item (where applicable):**
   - Label
   - URL
   - Thumbnail image (Pattern B desktop + potentially mobile)
   - Section grouping label (Pattern A: TYPE / STYLES / MATERIALS)
   - Column assignment (desktop)
7. **Sale styling** — `highlight-link` on the Sale menu item (red text)
8. **Mobile label override** — optional `label_mobile` ACF field when label differs on small screens

### Label rendering (decided)

- Menu title = desktop label
- ACF `label_mobile` = mobile label when different
- Both labels output in HTML; CSS toggles visibility at navigation breakpoint
- Do **not** use `wp_is_mobile()` for visible label text

### Submenu thumbnail rendering (decided)

- **Registered size:** `chairforce-menu-thumb` at **108×108** (2× Figma for retina)
- **Display size:** **54×54px** in CSS (class `.site-header__menu-thumb`)
- **Desktop:** render `<img>` via `chairforce-menu-thumb` size; lazy load
- **Mobile:** omit `<img>` when `wp_is_mobile()` — drill-down is text-only
- Do **not** use `display:none` to hide images on mobile — omit markup instead

---

## 11. Accessibility requirements

- All interactive elements keyboard reachable
- Mega menu: `aria-expanded`, `aria-haspopup`, focus trap not required for hover menus but **required** if click-to-open on touch devices
- Mobile drawer: focus trap while open, return focus to hamburger on close
- Skip link to main content (WordPress/theme standard)
- Icon-only mobile buttons: `aria-label` (“Open menu”, “Cart”, “Call us”)
- Colour contrast: red Sale text on white must meet WCAG AA

---

## 12. WooCommerce integration points

- **Cart** — dynamic count badge, links to `/cart/`
- **Account** — links to My Account endpoint
- **Search** — product-aware search results
- **Category links** — mega menu items map to product category URLs (`/product-category/...`)

---

## 13. Out of scope (not shown in Figma)

- Transparent header variant (theme templates reference `SWTTransparentHeader` — separate feature)
- Logged-in vs logged-out Account states
- Mini-cart dropdown
- Third level of mobile drill-down (**confirmed: does not exist**)

## 14. Scroll behaviour (TBD)

- **Preferred:** hide header on scroll down, show on scroll up
- Designer has not confirmed; implement with theme option toggle defaulting to hide-on-scroll

---

## 15. Figma → theme token mapping (initial)

| UI element | Suggested token |
|---|---|
| Announcement bar bg | `black` |
| Announcement bar text | `white` |
| Search button | `primary` (navy) |
| Sale link | `secondary` (red) |
| Section headings (desktop mega menu) | `neutral` / muted |
| Section headings (mobile drill-down) | `secondary` (red) |
| Nav borders / dividers | `outline` |
| Mobile utility text | `neutral` |

Exact typography sizes should be taken from `context/figma/Chairforce Tokens/Desktop.tokens.json` and `Mobile.tokens.json` during implementation.
