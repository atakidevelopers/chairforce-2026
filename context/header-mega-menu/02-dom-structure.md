# 02 — DOM Structure (Desktop vs Mobile)

Target semantic HTML for the header and mega menu. **Implementation: full PHP render** via `partials/site-header.php` and custom walker — not WordPress blocks.

**Naming:** All IDs and block classes follow [plan § Naming conventions](../plans/header-mega-menu-plan.md#naming-conventions-avoid-woodmart-collision). Use **`site-header__*`** BEM; prefix IDs with **`chairforce-`**. Never `mobile-menu`, `main-menu`, or bare `mega-menu`.

See [05-stakeholder-decisions.md](./05-stakeholder-decisions.md).

---

## 1. Desktop DOM

```html
<header class="site-header">

  <!-- Layer 1: Announcement bar -->
  <div class="site-header__announcement">
    <div class="site-header__announcement-inner alignwide">
      <span class="site-header__announcement-icon" aria-hidden="true"><!-- truck --></span>
      <p class="site-header__announcement-text">
        Free shipping on orders over $999 • Same-day dispatch on in-stock items
      </p>
    </div>
  </div>

  <!-- Layer 2: Primary row -->
  <div class="site-header__primary">
    <div class="site-header__primary-inner alignwide">

      <a class="site-header__logo" href="/"><!-- logo --></a>

      <form class="site-header__search" role="search" action="/" method="get">
        <label class="screen-reader-text" for="chairforce-header-search">Search products</label>
        <input
          id="chairforce-header-search"
          class="site-header__search-input"
          type="search"
          name="s"
          placeholder="Search chairs, tables, stools…"
        />
        <button class="site-header__search-submit" type="submit" aria-label="Search"></button>
      </form>

      <div class="site-header__contact">
        <span class="site-header__contact-icon" aria-hidden="true"></span>
        <div class="site-header__contact-text">
          <span class="site-header__contact-hours">Mon–Fri 9–5</span>
          <a class="site-header__contact-phone" href="tel:1300272926">1300 272 926</a>
        </div>
      </div>

      <nav class="site-header__utilities" aria-label="Utility navigation">
        <!-- from chairforce-utility-nav + cart -->
      </nav>

    </div>
  </div>

  <!-- Layer 3: Navigation row — single chairforce-primary-nav menu -->
  <div class="site-header__nav">
    <div class="site-header__nav-inner alignwide">

      <nav class="site-header__nav-primary" aria-label="Primary navigation">
        <ul class="site-header__nav-list">
          <li class="site-header__nav-item site-header__nav-item--has-mega menu-item">
            <button
              class="site-header__nav-link"
              aria-expanded="false"
              aria-haspopup="true"
              aria-controls="chairforce-mega-menu-chairs"
            >
              Chairs <span class="site-header__nav-chevron" aria-hidden="true"></span>
            </button>

            <div
              id="chairforce-mega-menu-chairs"
              class="site-header__mega-menu"
              role="region"
              aria-label="Chairs"
              hidden
            >
              <div class="site-header__mega-menu-inner alignwide">
                <div class="site-header__mega-menu-column">
                  <h3 class="site-header__mega-menu-heading">Type</h3>
                  <ul class="site-header__mega-menu-list site-header__mega-menu-list--two-col">
                    <li><a href="…">Cafe Chairs</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </li>

          <!-- Right-pulled items (nav_align: right → site-header__nav-item--align-right) -->
          <li class="site-header__nav-item site-header__nav-item--has-mega site-header__nav-item--align-right menu-item">
            <button class="site-header__nav-link" aria-expanded="false">Shop by Space …</button>
          </li>
          <li class="site-header__nav-item site-header__nav-item--align-right menu-item">
            <a class="site-header__nav-link" href="/new-arrivals/">New Arrivals</a>
          </li>
          <li class="site-header__nav-item site-header__nav-item--align-right site-header__nav-item--highlight menu-item">
            <a class="site-header__nav-link site-header__nav-link--sale" href="/sale/">Sale</a>
          </li>
        </ul>
      </nav>

    </div>
  </div>

</header>
```

**Single menu rule:** Shop by Space, New Arrivals, and Sale are **`chairforce-primary-nav`** items. Desktop right alignment = `.site-header__nav-item--align-right` + CSS.

### Desktop layout notes

| Region | CSS layout intent |
|---|---|
| `site-header__primary-inner` | Grid/Flex: logo · search · contact · utilities |
| `site-header__nav-list` | Flex; first `.site-header__nav-item--align-right` gets `margin-inline-start: auto` (desktop only) |
| `site-header__mega-menu` | Full-width panel below nav row |

---

## 2. Mobile DOM

### 2.1 Sticky header

```html
<header class="site-header">
  <!-- announcement, primary row, search row — same BEM as desktop -->
  <button
    class="site-header__menu-toggle"
    aria-expanded="false"
    aria-controls="chairforce-mobile-drawer"
    aria-label="Open menu"
  ></button>
</header>
```

### 2.2 Off-canvas drawer

```html
<div
  id="chairforce-mobile-drawer"
  class="site-header__mobile-drawer"
  aria-hidden="true"
  inert
>
  <div class="site-header__mobile-drawer-panels">

    <div class="site-header__mobile-drawer-panel site-header__mobile-drawer-panel--root" data-panel="root">
      <div class="site-header__mobile-drawer-bar">
        <span class="site-header__mobile-drawer-bar-title">Menu</span>
        <button class="site-header__mobile-drawer-close" aria-label="Close menu">×</button>
      </div>

      <nav class="site-header__mobile-drawer-nav" aria-label="Main menu">
        <ul class="site-header__mobile-drawer-list">
          <li>
            <button class="site-header__mobile-drawer-link site-header__mobile-drawer-link--drill" data-target-panel="chairs">
              Chairs <span class="site-header__mobile-drawer-chevron" aria-hidden="true">›</span>
            </button>
          </li>
          <li><a class="site-header__mobile-drawer-link" href="/new-arrivals/">New Arrivals</a></li>
          <li><a class="site-header__mobile-drawer-link site-header__mobile-drawer-link--sale" href="/sale/">Sale</a></li>
        </ul>

        <ul class="site-header__mobile-drawer-list site-header__mobile-drawer-list--utility">
          <!-- chairforce-utility-nav items -->
        </ul>
      </nav>
    </div>

    <div class="site-header__mobile-drawer-panel site-header__mobile-drawer-panel--sub" data-panel="chairs" hidden>
      <div class="site-header__mobile-drawer-bar">
        <button class="site-header__mobile-drawer-back" data-target-panel="root">‹ Back</button>
        <button class="site-header__mobile-drawer-close" aria-label="Close menu">×</button>
      </div>
      <h2 class="site-header__mobile-drawer-title">Explore Chairs</h2>
      <!-- drill-down sections -->
    </div>

  </div>
</div>
```

**Do not use:** `id="mobile-menu"`, class `.mobile-menu` — Woodmart collision.

### Mobile layout notes

| Concern | Approach |
|---|---|
| Panel transitions | Translate `.site-header__mobile-drawer-panels` or swap `hidden` + CSS |
| Overlay position | `fixed` below sticky header; `top: var(--site-header-height)` |
| Data source | Same **`chairforce-primary-nav`** + **`chairforce-utility-nav`** as desktop |

---

## 3. DOM differences at a glance

| Concern | Desktop | Mobile |
|---|---|---|
| Search | Primary row | Search row below primary |
| Category nav | Horizontal nav row | Vertical list in `#chairforce-mobile-drawer` |
| Right-pulled items | CSS align-right in nav row | Same list order in drawer |
| Utilities | Header cluster | Drawer utility list |
| Mega menu | `.site-header__mega-menu` panel | Drill-down panels in drawer |
| Thumbnails | Patterns B/C (desktop only) | Text-only drill-down |

---

## 4. Data source

Both desktop and mobile trees render from:

- **`chairforce-primary-nav`** — one `wp_nav_menu()` call
- **`chairforce-utility-nav`** — separate call for utility cluster + drawer utilities

Use PHP constants `CHAIRFORCE_MENU_PRIMARY` and `CHAIRFORCE_MENU_UTILITY` — never hardcode Woodmart slugs.
