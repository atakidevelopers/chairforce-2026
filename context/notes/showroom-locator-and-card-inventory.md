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

Queried via DDEV, 8 Aug 2026 (full field dump).

### 4.1 Post identity — slug vs `id` meta

| ID | post_title | post_slug | `id` meta | `id` === slug? | Permalink |
|---|---|---|---|---|---|
| 1448 | NSW | `sydney` | `sydney` | **Yes** | `/showrooms/sydney/` |
| 1465 | QLD | `brisbane` | `brisbane` | **Yes** | `/showrooms/brisbane/` |
| 1472 | VIC | `melbourne` | `melbourne` | **Yes** | `/showrooms/melbourne/` |
| 1480 | SA | `adelaide` | `adelaide` | **Yes** | `/showrooms/adelaide/` |
| 1481 | WA | `perth` | `perth` | **Yes** | `/showrooms/perth/` |
| 1479 | TAS | `hobart` | `hobart` | **Yes** | `/showrooms/hobart/` |
| 1482 | AUK | `auckland` | `auckland` | **Yes** | `/showrooms/auckland/` |

**Why does `id` exist?** Legacy JetEngine / pickup-selector key. Theme code in
`Showroom_Locator::resolve_location_key()` uses **`id` meta first, post slug second** —
but on every live post they are **identical**, so **`post_slug` alone would work today**.

**Recommendation:** Treat **`post_slug` as canonical** machine key (`data-showroom-location`,
map lookup, default location attr). Deprecate ACF `id` after confirming no other plugin reads it.

`post_title` is **not** the city name — it is a legacy state/region code (NSW, QLD, AUK).
Display title always comes from **`warehouse`**.

---

### 4.2 Region / state — triplicated data

Same geographic concept stored in **four places**:

| ID | post_title | ACF `location` | ACF `state` | Taxonomy `showroom-locations` | Taxonomy slug |
|---|---|---|---|---|---|
| 1448 | NSW | New South Wales | New South Wales | New South Wales | `new-south-wales` |
| 1465 | QLD | Queensland | Queensland | Queensland | `queensland` |
| 1472 | VIC | Victoria | Victoria | Victoria | `victoria` |
| 1480 | SA | South Australia | South Australia | South Australia | `south-australia` |
| 1481 | WA | Western Australia | Western Australia | Western Australia | `western-australia` |
| 1479 | TAS | Tasmania | Tasmania | Tasmania | `tasmania` |
| 1482 | AUK | New Zealand | *(empty)* | New Zealand | `new-zealand` |

| Field | Used by theme today? | Notes |
|---|---|---|
| ACF `location` (select) | **No** in locator/card render | Duplicates taxonomy on 7/7 posts |
| ACF `state` (text) | **No** | Empty on Auckland; legacy pickup-selector per ACF note |
| Taxonomy `showroom-locations` | **Yes** — filter tab label fallback | **Canonical** for region name |
| `post_title` | **No** for display | Legacy code (NSW, QLD…) — confusing in admin |

**Recommendation:** Single source = **`showroom-locations` taxonomy**. Remove ACF `location`
and `state` after migration audit. Filter tabs can use term name (or term meta for short
label e.g. “NSW” if desired).

---

### 4.3 Full ACF + card fields (all posts)

| ID | Slug | warehouse | time | phone | email | address (plain) | _description | map | gallery (count → IDs) | featured img | card `image_id` |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1448 | sydney | Sydney Showroom / Warehouse | Open 9am–5pm Mon–Fri `<br>` | (02) 9648 0799 | sydney@chairforce.test | Warehouse 1, 161 Manchester Road, Auburn | Has text (entry directions) | empty | 5 → 1501457, 1501456, 4480, 1501455, 4482 | 1501602 | **1501457** (gallery[0]) |
| 1465 | brisbane | Brisbane Showroom / Warehouse | Open 9am–5pm Mon–Fri `<br>` | 07 3256 6593 | brisbane@chairforce.test | Unit 1A, 405 Newman Road, Geebung | empty | empty | 5 → 1501614, 1501618, 1501106, 1501617, 4566 | 1501615 | **1501614** |
| 1472 | melbourne | Melbourne Showroom / Warehouse | Open 9am–5pm Mon–Fri | (03) 9040 1500 | melbourne@chairforce.test | 95 South Gippsland Highway, Dandenong South – Warehouse 4 | empty | empty | 5 → 4502, 4499, 1501458, 4500, 1501459 | 1501620 | **4502** |
| 1480 | adelaide | Adelaide Showroom / Warehouse | Open 9am–5pm Mon–Fri `<br>` | (08) 8120 2190 | adelaide@chairforce.test | **WE HAVE MOVED TO:** Warehouse 1, 21-31 Sheffield Street, Woodville North *(HTML)* | empty | empty | 5 → 1501694, 1501460, 1501702, 1501701, 1501437 | 1501694 | **1501694** |
| 1481 | perth | WA Showroom / Warehouse | Open 9am–5pm Mon–Fri `<br>` | (08) 9204 1133 | perth@chairforce.test | 33 Cleaver Terrace, Rivervale | empty | empty | 6 → 1501445, 1501444, 4471, 4473, 1501621, 4472 | 1501622 | **1501445** |
| 1479 | hobart | Hobart Showroom / Warehouse | Video/call hours (non-standard copy) | (03) 6105 0529 | hobart@chairforce.test | Long copy — no physical branch; subsidised shipping from Melbourne | Has text (live video shopping) | empty | 2 → 1501643, 1501645 | 1501642 | **1501643** |
| 1482 | auckland | New Zealand Showroom / Warehouse | Open 9am–5pm Mon–Fri `<br>` | (09) 271 5000 | sales@chairforce.co.nz | 9 Nandina Avenue, East Tamaki, AUCKLAND | empty | empty | **0** (empty) | **0** | **0** → placeholder |

**Card image resolution chain** (locator + `showroom-card`):

`showroom_gallery[0]` → `map` (attachment ID) → featured image → theme placeholder

**Field usage in cards:**

| Field | showroom-card | showroom-locator |
|---|---|---|
| `warehouse` | Card title | Same (via card after refactor) |
| `address` | Detail row + Google Maps query | Same |
| `time` | Detail row | Same |
| `phone`, `email` | Contact row | Same |
| `showroom_gallery` | Hero image | Same |
| `_description` | **Not rendered** | **Not rendered** — single-page body only |
| `map` | Unused (empty all posts) | Unused — candidate for deletion |
| `location`, `state`, `id` | **Not rendered** | `id` only for machine key mapping |

---

### 4.4 `_description` full text (where populated)

| Slug | `_description` |
|---|---|
| sydney | (corner of Manchester & Chisholm Rd) Entry through the glass doors between the cafe and warehouse. Please call if you cannot find us. |
| hobart | Shop with a live expert. Shop from the comfort of your own home via a video link… Please contact us for a live link appointment. |
| *others* | empty |

---

### 4.5 Hard-coded theme config vs post data

Even with correct CPT/ACF data, **`Showroom_Locator::get_locations_config()`** still adds
fields **not on the post**:

| Runtime key | From post? | From hard-coded config? |
|---|---|---|
| `key` | slug / `id` meta | Whitelist — unknown slugs **dropped** |
| `label` (Sydney, Brisbane…) | **No** — not in ACF | **Yes** — city display name |
| `filter_label` | Taxonomy term name | Fallback: config `label` |
| `marker_x`, `marker_y` | **No** | **Yes** — SVG map % positions |
| `order` | **No** (`menu_order` all 0) | **Yes** — tab sort order |

This is separate from ACF duplication — map markers and city labels live only in PHP today.

---

### 4.6 Mapped runtime object (unchanged contract)

```php
[
  'key'          => 'sydney',           // id meta or post_slug
  'post_id'      => 1448,
  'filter_label' => 'New South Wales',  // taxonomy term name
  'label'        => 'Sydney',           // hard-coded config only
  'marker_x'     => 75.17,
  'marker_y'     => 69.16,
  'order'        => 1,
  'warehouse'    => 'Sydney Showroom / Warehouse',
  'image_id'     => 1501457,
  'time'         => 'Open 9am to 5pm…',
  'phone'        => '(02) 9648 0799',
  'phone_href'   => '0296480799',
  'email'        => 'sydney@chairforce.test',
  'address'      => 'Warehouse 1, 161 Manchester Road…',
  'permalink'    => 'https://…/showrooms/sydney/',
]
```

---

### 4.7 Data cleanup summary

| Item | Verdict |
|---|---|
| ACF `id` | **Redundant** with `post_slug` on all 7 posts — safe to deprecate |
| ACF `location` | **Redundant** with taxonomy — remove after audit |
| ACF `state` | **Redundant** with taxonomy (except empty Auckland) — legacy pickup |
| ACF `map` | **Unused** — remove per ACF note |
| `post_title` | **Misleading** (NSW/QLD) — ignore for front-end; use `warehouse` |
| Taxonomy `showroom-locations` | **Keep** — canonical region |
| Hard-coded `get_locations_config()` | **Keep until** marker x/y (+ optional city label) move to ACF or term meta |

**Data quirks:**

- Auckland has **no gallery and no featured image** — card uses placeholder.
- Adelaide **address** contains HTML (`<b>`, `<br>`) — cards/maps strip or kses as today.
- Hobart **address** is marketing copy, not a street address — Maps query may be weak.
- All **`map`** fields empty; gallery drives all card images.

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

## 6. `chairforce/showroom-card` — status

**Created 8 Aug 2026.** Dynamic block for Query Loop (`ancestor: core/post-template`).

- Resolves showroom from loop `postId` / `get_the_ID()` — no manual location picker
- Inspector **CTA button group:** Get Directions | Learn More (`ctaType` attribute)
- Get Directions → Google Maps (`address` + site title); Learn More → showroom permalink
- Directions variant: `$color-background` card, auto-width primary button
- Locator blocks **not yet wired** to use this block (Phase 1 pending)

---

## 7. Decision log

| Date | Decision |
|---|---|
| 8 Aug 2026 | Inventory captured; **defer integrating `showroom-card` into locators** until standalone block is QA'd. |
| 8 Aug 2026 | **`chairforce/showroom-card` block created** — loop post resolution, CTA button group (`ctaType`), Google Maps directions with site title. |
| 8 Aug 2026 | Live data inventory expanded — **`id` meta redundant with slug**; **`location`/`state` redundant with taxonomy**. |
| 8 Aug 2026 | Removed ACF fields **`location`** and **`state`** from field group JSON (post meta retained). Planned: taxonomy-driven locator, `post_title` replaces `warehouse`. |

---

## 8. Planned data model — taxonomy-first (discussion, Aug 2026)

### Proposed direction

| Today | Target |
|---|---|
| Machine key = ACF `id` or post slug (city) | **post_slug** (showroom identity) |
| Display title = ACF `warehouse` | **`post_title`** e.g. slug `sydney` → "Sydney Showroom" (BatchPress job) |
| Region = triplicated (`location`, `state`, taxonomy) | **`showroom-locations` taxonomy only** |
| Map markers = hard-coded 7 cities in PHP | **Term meta on taxonomy** — marker shown when term has ≥1 showroom |
| Filter tab = one per city | **One per state/region term**; QLD tab shows all showrooms in Queensland |

### Why "derive city name" goes away

Hard-coded `get_locations_config()['label'] => 'Sydney'` exists because **`warehouse`** and **`post_title`** (NSW) are poor display sources. With BatchPress setting `post_title` from slug, cards use **`get_the_title()`** — no derived city label needed.

**Learn Our {X} Showroom** CTA can use post title or a trimmed version of it.

### Does state-based taxonomy + dynamic markers make sense?

**Yes**, with these design notes:

1. **ACT / NT** — no assigned showrooms → no marker. Add showroom + assign term → marker appears automatically once term meta exists.
2. **Multiple showrooms per state** (future) — compact locator must handle **N cards per state** (carousel, list, or first-showroom default). Full locator grid already supports N.
3. **Marker coordinates** move to **term meta** on `showroom-locations` (`marker_x`, `marker_y`, optional short label "QLD") keyed by **term slug** (`queensland`, `new-south-wales`), not city slug.
4. **New Zealand** — same pattern; one taxonomy term, potentially one or more showrooms.
5. **BatchPress job** (planned) — update `post_title` from slug; optionally remove `warehouse` from ACF after code uses title.
6. **Keep post meta** — removing fields from ACF JSON does not delete `_location`, `_state`, `_warehouse` in DB.

### ACF fields removed from editor (meta preserved)

- `location` — removed from `group_showrooms_fields.json`
- `state` — removed from `group_showrooms_fields.json`

Still to deprecate: `warehouse`, `id`, `map` (after code + migration jobs).

---
