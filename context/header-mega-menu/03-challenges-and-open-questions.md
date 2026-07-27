# 03 — Implementation Challenges & Open Questions

> **Status:** Approach chosen and stakeholder questions resolved. See [05-stakeholder-decisions.md](./05-stakeholder-decisions.md) and [implementation plan](../plans/header-mega-menu-plan.md). Items below are retained as historical context for why the ACF + PHP header approach was selected.

---

## 1. WordPress core Navigation block limits

**Challenge:** `core/navigation` supports nested submenus but not, out of the box:
- Full-width mega menu panels with multi-column CSS Grid
- Two distinct mega menu layouts (grouped sections vs thumbnail grid) per top-level item
- Per-item thumbnail images in submenu entries
- Splitting one menu into “primary left” and “right-pulled” groups
- Desktop hover mega menu + mobile drill-down from the same menu data

**Evidence in theme:** Sass already targets `.wp-block-navigation__submenu-container` and provides `megaMenuAnimation` — suggesting a prior plan to extend core Navigation rather than replace it.

**Decision needed:** Extend core Navigation with CSS/JS/filter hooks vs custom JSX block vs hybrid (Navigation for links + custom mega menu renderer).

---

## 2. Two mega menu layout patterns

**Challenge:** Figma shows materially different structures:

| Pattern | Example | Columns | Headings | Thumbnails |
|---|---|---|---|---|
| A — Grouped | Chairs | 3 sections; TYPE has 2 sub-columns | Uppercase grey | No |
| B — Thumbnail grid | Tables & Bench Seating | 3 flat columns | None | Yes, per item |

Editors need to pick a layout per top-level category. Core Navigation has no concept of “layout variant per branch.”

**Decision needed:** How is layout type stored and rendered? Custom block attributes on nav items? ACF repeater keyed by category slug? Hard-coded PHP map?

---

## 3. Right-pulled navigation items

**Challenge:** “Shop by Space”, “New Arrivals”, and “Sale” must render **right-aligned** in the desktop nav row, separate from the 7 product category links. On mobile they appear in the **same list** as categories (not a separate visual group).

**Options:**
- Two separate `core/navigation` blocks in the header template (flex space-between)
- Single menu with CSS `margin-left: auto` on specific items (fragile if order changes)
- Custom nav component with explicit `position: primary | secondary` field per item

**Additional:** “Sale” requires red text styling — a menu CSS class or item-level flag.

---

## 4. Search bar placement and duplication

**Challenge:** Desktop embeds search in the primary row; mobile puts it in a **third row**. Same form, different DOM position.

**Considerations:**
- One HTML form in the template with CSS reordering (`order`, grid areas) vs two forms (bad for a11y/maintenance)
- WooCommerce product search may need `post_type=product` hidden field
- Search results page template already exists (`templates/search.html`)

**Decision needed:** Block pattern with responsive CSS layout vs separate desktop/mobile markup hidden at breakpoints.

---

## 5. Mobile drill-down navigation model

**Challenge:** Mobile uses a **panel stack** (root → category → possibly item) with BACK and × controls. Desktop uses **hover mega panels**. Same data, different interaction models.

**Friction points:**
- Core Navigation responsive overlay uses expand/collapse accordion — not the Figma drill-down with “Explore Chairs” title and red section headings
- Chevrons on every Chairs sub-item in mobile imply further drill-down or category links — third level not designed
- “Office Furniture” (mobile) vs “Office” (desktop) — label inconsistency to resolve with content team

**Decision needed:** Custom mobile menu JS component vs heavy restyling of Navigation responsive container.

---

## 6. Thumbnail images for menu items

**Challenge:** Pattern B requires a square image per link. WordPress Navigation menu items support title + URL + CSS class but **not** featured images.

**Options:**
- ACF fields on `nav_menu_item` posts (common pattern)
- Menu item custom block (full site editing nav plugin pattern)
- Hard-coded mapping in theme PHP from category term thumbnails
- Auto-pull WooCommerce category thumbnail where URL matches a product category

**Decision needed:** Editor workflow for assigning/updating thumbnails.

---

## 7. Header height and sticky behaviour

**Challenge:** Current theme variables (`$header-height-desktop: 96px`, `$header-height-mobile: 72px`) do not match the Figma header, which has:
- Desktop: announcement + primary + nav ≈ much taller than 96px
- Mobile: announcement + primary + search ≈ taller than 72px

The `mobileMenuMinimumHeight` mixin subtracts `$header-height-mobile` from viewport — wrong values will break drawer height.

**Action:** Re-measure from Figma tokens and update Sass variables after implementation approach is chosen.

---

## 8. Announcement bar

**Challenge:** Not part of current header pattern. Needs:
- Editable content (likely ACF theme option or a simple block in the header template part)
- Optional link wrapping the message
- Different text wrapping desktop vs mobile (may be pure CSS or separate mobile copy)

---

## 9. Utility cluster and WooCommerce dynamics

**Challenge:** Four desktop utilities + reduced mobile set. WooCommerce adds:
- Cart fragment refresh (count badge)
- Account endpoint URL
- Possibly quote plugin integration (custom URL)

“Get a Quote” label on mobile vs “Quotes” on desktop — same destination, different copy.

---

## 10. Accessibility on touch devices

**Challenge:** Desktop mega menus triggered by hover **fail on touch** unless click/tap also opens them.

**Requirements:**
- First tap opens mega menu; second tap follows link (or explicit separate link/button)
- Or: top-level items with mega menus are buttons, not links
- Keyboard: arrow keys between items, Escape closes
- Mobile: focus trap in drawer, `inert` on background

---

## 11. Performance

**Challenge:** Pattern B loads many small thumbnail images in the mega menu DOM.

**Mitigations:**
- Lazy-load images below fold / on first open
- Use WordPress attachment sizes (`thumbnail` or custom `menu-thumb` size)
- Consider rendering mega menu panels on first interaction vs all in DOM at once

---

## 12. Content migration from live site

**Challenge:** The rebuild retires Elementor/Woodmart. Existing category structure and menu assignments live in the current WordPress nav menus and WooCommerce product categories.

**Action:** Inventory live menu structure (`wp_nav_menu`, product categories) and map to the new grouped/thumbnail model before implementation.

---

## 13. Unknown mega menu layouts

**Challenge:** Only Chairs and Tables & Bench Seating mega menus are documented. Remaining top-level items (Stools, Table Tops & Bases, Outdoor Furniture, Office, Storage, Shop by Space) have unknown layouts.

**Action:** Request additional Figma frames or confirm default fallback (Pattern A grouped, Pattern B thumbnails, or simple link list).

---

## 14. Approach evaluation criteria

**Decision made:** ACF Menu + full PHP header. See doc 04 and plan.

---

## 16. Suggested phasing

Superseded by [`context/plans/header-mega-menu-plan.md`](../plans/header-mega-menu-plan.md) (7 phases).
