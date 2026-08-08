# Showroom locator blocks — inventory & card approach

**Captured:** 8 Aug 2026

Review of `chairforce/showroom-locator`, `chairforce/showroom-locator-full`, showroom
ACF/taxonomy data, and whether a **`chairforce/showroom-card`** block (Product Card
pattern) is warranted.

**Related:**

- `context/notes/product-card-block-migration.md` — Product Card pattern reference
- `acf-json/group_showrooms_fields.json` — ACF field group source
- `lib/class-showroom-locator.php` — shared data + card markup helpers
- `lib/class-showroom-locator-full.php` — full layout shell

---

## 1. Current blocks

| Block | Purpose | Attributes | Frontend JS |
|---|---|---|---|
| `chairforce/showroom-locator` | Compact locator: state filter tabs + one active card + SVG map markers | `defaultLocation` (default `brisbane`), `showCta` | Tab/marker switching (`view.js`) |
| `chairforce/showroom-locator-full` | Full page layout: map + featured card + grid of remaining cards | Same | Marker click swaps featured ↔ grid (`view.js`) |

Both blocks:

- Are **dynamic** (SSR via `render.php` → PHP classes)
- Use **ServerSideRender** in the editor (disabled interaction)
- Query **all published `showrooms` posts** internally — not loop/context driven
- Share data via `Showroom_Locator::get_showrooms()` and card helpers on the same class

### Architecture (today)

```
showroom-locator          → Showroom_Locator::render()
showroom-locator-full     → Showroom_Locator_Full::render()
                              └─ Showroom_Locator::get_showrooms()
                              └─ Showroom_Locator::render_map_markup()
                              └─ Showroom_Locator::render_full_card()
                              └─ Showroom_Locator::render_card_body()
                              └─ Showroom_Locator::render_card_image()
```

Card HTML is **string-built in PHP** on `Showroom_Locator`, with BEM prefix passed in
(`cf-showroom-locator` vs `cf-showroom-locator-full`) so one markup API serves both layouts.

### Supported location keys (hard-coded)

Posts must map to one of: `sydney`, `brisbane`, `melbourne`, `adelaide`, `perth`,
`hobart`, `auckland` (via `id` meta or post slug). Marker coordinates and filter
labels come from `Showroom_Locator::get_locations_config()`.

---

## 2. Showroom content type

| Item | Value |
|---|---|
| Post type | `showrooms` (`lib/class-content-types.php`) |
| Taxonomy | `showroom-locations` (hierarchical; used for filter label fallback) |
| Published posts | **7** (one per supported city) |
| FSE single/archive templates | **None** in theme yet |
| Block usage in patterns/templates | **None found** — blocks exist but are not wired into theme templates yet |

---

## 3. ACF field inventory

**Field group:** `group_showrooms_fields` → `acf-json/group_showrooms_fields.json`  
**Location rule:** post type `showrooms`

| Field label | Meta key | ACF type | Return / notes | Used by locator blocks? |
|---|---|---|---|---|
| Warehouse | `warehouse` | text | string | **Yes** — card title (fallback: post title) |
| Time | `time` | text | string; may contain `<br>` | **Yes** — hours row |
| Phone | `phone` | text | string | **Yes** — contact row + `tel:` link |
| Email | `email` | text | string | **Yes** — contact row + `mailto:` |
| Address | `address` | text | string; may contain `<br>`, `<b>` | **Yes** — address row |
| Description | `_description` | textarea | string | **No** — not rendered in locator cards (single-page content) |
| Map | `map` | image | attachment ID | **Fallback only** — used if gallery empty; **empty on all 7 posts**; ACF label says "check for delete in the future" |
| Showroom Gallery | `showroom_gallery` | gallery | array of attachment IDs | **Yes** — first image → card hero |
| Location | `location` | select | AU/NZ state name | **No** in card markup (duplicates taxonomy / `state`) |
| Id | `id` | text | machine slug (`sydney`, etc.) | **Yes** — maps post → locator key |
| State | `state` | text | legacy string | **No** — kept for pickup-selector compatibility per ACF instructions |

### Other meta on showroom posts (not ACF group)

| Meta key | Notes |
|---|---|
| `_thumbnail_id` / featured image | Used as **last-resort** card image if gallery + map empty |
| `_wp_page_template` | Elementor/default template remnants |
| `_elementor_*` | Legacy Elementor data on migrated posts |
| `rank_math_*` | SEO plugin |
| `_pys_head_footer` | Pixel plugin |

ACF stores both raw keys (`warehouse`, `phone`, …) and underscore-prefixed copies
(`_warehouse`, `_phone`, …) — standard ACF behaviour.

---

## 4. Live data shape (7 published showrooms)

Queried via DDEV, Aug 2026.

| ID | post_title | slug | id meta | warehouse (display title) | phone | email | gallery IDs | location / taxonomy | map | _description |
|---|---|---|---|---|---|---|---|---|---|---|
| 1448 | NSW | sydney | sydney | Sydney Showroom / Warehouse | (02) 9648 0799 | sydney@chairforce.test | 5 images | New South Wales | empty | has text |
| 1465 | QLD | brisbane | brisbane | Brisbane Showroom / Warehouse | 07 3256 6593 | brisbane@chairforce.test | 5 images | Queensland | empty | empty |
| 1480 | SA | adelaide | adelaide | Adelaide Showroom / Warehouse | (08) 8120 2190 | adelaide@chairforce.test | 5 images | South Australia | empty | empty |
| 1479 | TAS | hobart | hobart | Hobart Showroom / Warehouse | (03) 6105 0529 | hobart@chairforce.test | 2 images | Tasmania | empty | has text |
| 1472 | VIC | melbourne | melbourne | Melbourne Showroom / Warehouse | (03) 9040 1500 | melbourne@chairforce.test | 5 images | Victoria | empty | empty |
| 1481 | WA | perth | perth | WA Showroom / Warehouse | (08) 9204 1133 | perth@chairforce.test | 6 images | Western Australia | empty | empty |
| 1482 | AUK | auckland | auckland | New Zealand Showroom / Warehouse | (09) 271 5000 | sales@chairforce.co.nz | **empty** | New Zealand | empty | empty |

**Mapped runtime object** (`Showroom_Locator::get_showrooms()` per location key):

```php
[
  'key'          => 'sydney',           // machine slug
  'post_id'      => 1448,
  'filter_label' => 'New South Wales',  // from showroom-locations term, else city label
  'label'        => 'Sydney',           // from hard-coded locations config
  'marker_x'     => 75.17,              // SVG map %
  'marker_y'     => 69.16,
  'order'        => 1,
  'warehouse'    => 'Sydney Showroom / Warehouse',
  'image_id'     => 1501457,            // gallery[0] → map → featured image
  'time'         => 'Open 9am to 5pm…',
  'phone'        => '(02) 9648 0799',
  'phone_href'   => '0296480799',
  'email'        => 'sydney@chairforce.test',
  'address'      => 'Warehouse 1, 161 Manchester Road…',
  'permalink'    => 'https://…/showrooms/sydney/',
]
```

**Data quirks:**

- Post titles are **state codes** (NSW, QLD) — display copy comes from `warehouse`.
- `location`, `state`, and taxonomy **triplicate** the same concept; cards use taxonomy
  only for filter tab label.
- Auckland has **no gallery** — card falls back to placeholder image.
- `map` field is unused legacy; safe candidate for removal after confirming no other
  consumers.

---

## 5. Product Card vs Showroom — comparison

| Aspect | Product Card | Showroom locators |
|---|---|---|
| Primary use | Row inside **Product Collection** loop | **Self-contained widgets** (map + filters + cards) |
| Parent context | `ancestor: woocommerce/product-template`, `usesContext: postId` | None — queries all showrooms internally |
| Markup source | `chairforce_get_product_card_blocks_markup()` + `do_blocks()` | PHP string methods on `Showroom_Locator` |
| Editor risk | Template part was editable/breakable | Blocks are opaque SSR widgets |
| Multiple surfaces | Shop, related, upsells, search, Load More | Two layout variants of same data |
| Interactivity | WC blocks + theme hooks | Custom `view.js` (tabs / featured swap) |
| Card reuse outside parent | Block is the card | Card only exists inside locator shells |

---

## 6. Recommendation — should we create `chairforce/showroom-card`?

### Short answer: **Not yet — not the same problem Product Card solved.**

Product Card replaced an **editable template part inside a Query Loop** where markup
had to stay identical across shop, Load More, related products, etc. Showroom cards
today are **sub-components of two monolithic locator blocks**, already sharing PHP
helpers on `Showroom_Locator`. There is no Query Loop, no template part, and no
second render pipeline to unify.

### What to do instead (now)

1. **Keep** `showroom-locator` and `showroom-locator-full` as layout shells.
2. **Optional refactor (Product Card parity at the helper layer only):** extract
   `chairforce_get_showroom_card_markup( $showroom, $args )` into
   `includes/showroom-card-functions.php` and thin the class methods to call it —
   same *single source of markup* idea, without a new block registration.
3. **Wire blocks** into the relevant page template/pattern when the showroom page is
   built (neither block is referenced in theme templates yet).
4. **Data cleanup (separate task):** deprecate `map`, reconcile `location` / `state` /
   taxonomy, confirm `_description` belongs on single showroom template only.

### When a Showroom Card **block** would make sense

Create `chairforce/showroom-card` if any of these land:

- FSE **Query Loop** over `showrooms` (archive, “more locations”, footer list)
- Single showroom template reusing the same card chrome
- Editor need to insert a **standalone** showroom card on arbitrary pages
- A third consumer that must stay in sync with locator card design

If built, mirror Product Card structurally:

```text
ancestor / usesContext → core/query or showrooms loop (TBD)
render.php → chairforce_get_showroom_card_markup()
attributes → titleTag, showCta, variant (compact | full), etc.
```

But **do not** split card out of the locator blocks until a second consumer exists —
otherwise we add block registration + editor SSR overhead with no new surface.

---

## 7. Decision log

| Date | Decision |
|---|---|
| 8 Aug 2026 | Inventory captured; **defer integrating `showroom-card` into locators** until standalone block is QA'd. |
| 8 Aug 2026 | **`chairforce/showroom-card` block created** — shared card CSS, block styles for CTA (`directions` vs `learn-more`), toggles for image/address/time/contact/CTA. Get Directions now links to Google Maps from `address` field (not showroom permalink). |
