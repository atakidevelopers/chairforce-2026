# Header & Mega Menu — Requirements

Requirements and implementation notes for the Chairforce site header and mega menu, derived from Figma component screenshots and stakeholder decisions.

## Decision (final)

**Classic WP Menu + ACF + full PHP-rendered header**, ported from `wp-content/themes/shineon`. FSE template part is a thin wrapper only. See [05-stakeholder-decisions.md](./05-stakeholder-decisions.md) and [implementation plan](../plans/header-mega-menu-plan.md).

## Source assets

All screenshots live in [`context/figma/components/`](../figma/components/):

| File | Description |
|---|---|
| `Header-desktop.png` | Desktop header — announcement bar, main row, nav row |
| `Header-mobile.png` | Mobile header — announcement bar, utility row, search row |
| `Mega-menu-desktop-chairs.png` | Pattern A — grouped text (TYPE / STYLES / MATERIALS) |
| `Mega-menu-desktop-seating.png` | Pattern B — flat 3-column thumbnail grid |
| `Mega-menu-desktop-stools.png` | Pattern C — grouped thumbnails (TYPE / MATERIAL) |
| `Mega-menu-dektop-table-tops--bases.png` | Pattern C — grouped thumbnails (TABLE TOPS / TABLE BASES) |
| `Mega-menu-desktop-outdoor.png` | Pattern C — grouped thumbnails (OUTDOOR SEATING / OUTDOOR TABLES) |
| `Mega-menu-desktop-office.png` | Pattern C — grouped thumbnails (OFFICE CHAIRS / OFFICE TABLES) |
| `Mega-menu-desktop-shop-by-specs.png` | Pattern B — flat 4-column thumbnail grid |
| `Mega-menu-desktop-storage.png` | Pattern B — flat 4-column thumbnail grid |
| `Mega-menu-mobile-root.png` | Mobile menu — root level |
| `Mega-menu-mobile-chairs.png` | Mobile menu — Chairs drill-down |

Additional: [`context/figma/screens/Mega Menu.pdf`](../figma/screens/Mega%20Menu.pdf)

## Documents in this folder

| Doc | Purpose |
|---|---|
| [01-requirements-spec.md](./01-requirements-spec.md) | Full functional and visual requirements |
| [02-dom-structure.md](./02-dom-structure.md) | Target DOM for desktop and mobile |
| [03-challenges-and-open-questions.md](./03-challenges-and-open-questions.md) | Original challenges (many now resolved in doc 05) |
| [04-approach-evaluation.md](./04-approach-evaluation.md) | Block vs ACF evaluation; final architecture |
| [05-stakeholder-decisions.md](./05-stakeholder-decisions.md) | **Resolved decisions** from stakeholder Q&A |

## Implementation plan

[`context/plans/header-mega-menu-plan.md`](../plans/header-mega-menu-plan.md)

## Current theme baseline

The theme today ships a minimal FSE header (`patterns/header.php` → `parts/header.html`): logo, a single `core/navigation` block, and a CTA button. This will be **replaced** by the PHP header + classic menu system.

Existing Sass from parent theme (`megaMenuAnimation`, `$header-height-*`, navigation breakpoint at 767px) will be **revisited or replaced** with Chairforce-specific header/menu Sass.

## Reference implementations

| Theme | Path | Status |
|---|---|---|
| shineon | `wp-content/themes/shineon` | Port source (ACF menu + walker) |
| lasersight | `wp-content/themes/lasersight` | Rejected (block Navigation mega menu) |
