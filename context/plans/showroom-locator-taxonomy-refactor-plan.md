# Showroom locator — taxonomy-first refactor plan

## Status: 🚧 In progress (8 Aug 2026)

Refactor `chairforce/showroom-locator` and `chairforce/showroom-locator-full` so the
**map is state-driven** (`showroom-locations` taxonomy) while **tabs are post-driven**
(one tab per showroom), delegate cards to **`chairforce/showroom-card`**, and simplify
showroom CPT data (ACF JSON only — **never delete post meta**).

**Related docs:**

- `context/notes/showroom-locator-and-card-inventory.md` — live data tables, duplication audit
- `acf-json/group_showrooms_fields.json` — showroom field group
- `lib/class-showroom-locator.php` — current SSR + hard-coded city config
- `lib/class-showroom-locator-full.php` — full layout shell
- `includes/showroom-card-functions.php` — shared card block helpers
- `src-jsx-blocks/showroom-card/` — canonical card (Query Loop + `ctaType`)

---

## Goal

1. **Map markers** keyed by **`showroom-locations` taxonomy term** (state/region), not hard-coded city slugs.
2. **Tabs** — **one tab per published showroom post**; label = **`post_title`**.
3. **Tab click** → show **one** showroom card (that post only).
4. **Marker click** → show **all posts** assigned to that state taxonomy; **highlight** the tab(s) for those posts; **Swiper** when N > 1.
5. **Cards** rendered via **`chairforce/showroom-card`** with **`ctaType: directions`** (compact) / **`learn-more`** (full).
6. **Card display title** — keep **`warehouse` ACF** for now (no switch to `post_title` in card markup yet).
7. **ACF cleanup** — remove redundant fields from field group JSON only; legacy meta stays in DB.
8. **Marker positions** — **internal PHP config** bound to **state term slugs**.

**Out of scope for v1:** BatchPress title migration (deferred to end; not blocking locator build).

---

## Locked decisions

### Interaction model (compact locator)

Two controls, two scopes:

| Control | Scope | On click |
|---|---|---|
| **Tab** | One **showroom post** | Show **one** card for that post |
| **Map marker** | One **state taxonomy term** | Show **all posts** in that state; highlight matching tab(s); Swiper if N > 1 |

```text
[ Sydney ] [ Brisbane ] [ Melbourne ] …   ← tabs = all posts; label = post_title
┌─────────────────────────┐
│  one card OR swiper     │               ← tab → 1 card; marker → N cards
└─────────────────────────┘
        + SVG map markers per STATE (taxonomy)
```

**Examples (current 7 posts):**

- Click **Brisbane** tab → Brisbane card only; Queensland marker active.
- Click **Queensland** marker → if only Brisbane in QLD, one card + Brisbane tab highlighted; if two QLD showrooms later → Swiper + both tabs highlighted.

### Data — canonical sources

| Concern | Canonical source | Notes |
|---|---|---|
| Map / state grouping | **`showroom-locations` taxonomy** | Markers, marker click filter |
| Tab label | **`post_title`** | One tab per published showroom |
| Tab / card identity | **`post_slug`** (or post ID in `data-*`) | JS wiring |
| Card heading (for now) | **ACF `warehouse`** | Keep until a later pass; BatchPress does not touch this |
| Contact, hours, address, gallery | **ACF** (`phone`, `email`, `time`, `address`, `showroom_gallery`) | Unchanged |
| Map marker x/y | **Theme config** keyed by **taxonomy term slug** | e.g. `queensland`, `new-south-wales` |
| Machine key `id` meta | **Deprecated** — use slug | Remove from ACF JSON; **do not delete meta** |
| ACF `location`, `state` | **Removed from ACF JSON** ✅ | Meta retained |
| ACF `warehouse` | **Keep in ACF JSON for now** | Card title source; remove from JSON later only |
| ACF `map` | **Remove from ACF JSON** (planned) | Empty on all posts; meta retained |

### UX — full locator (`showroom-locator-full`)

- Same **state-driven map** + **post-driven tabs** interaction model as compact (unless Figma dictates a grid-only variant).
- Cards via **`showroom-card`** with **`ctaType: learn-more`** for featured/grid variant; compact uses **directions**.

### Map markers — internal config (not ACF)

States are stable; marker positions stay in theme code, keyed by **term slug**:

```php
// lib/class-showroom-locator.php (or includes/showroom-locator-config.php)
[
  'queensland'        => [ 'marker_x' => 81.25, 'marker_y' => 43.80, 'order' => 2 ],
  'new-south-wales'   => [ … ],
  // act, northern-territory — defined but no marker rendered until a showroom uses the term
]
```

**Rules:**

- Render marker **only if** ≥1 published showroom is assigned to that term.
- Marker click sets **active state**; card area shows posts for that term; tabs for those posts get **active/highlight** class.

### Tab ↔ state linkage

Each tab carries **`data-showroom-state`** (taxonomy term slug) so marker selection can highlight all tabs sharing that state:

```html
<button data-showroom-slug="brisbane" data-showroom-state="queensland">Brisbane</button>
```

### ACF / data migration policy

- **Never `delete_post_meta`** for deprecated fields in this workstream.
- **Only remove fields from `group_showrooms_fields.json`** so they disappear from the meta box.
- **`warehouse`** stays in ACF and in card reads **for this refactor**.

### BatchPress (Phase H — last)

- **Only** normalize **`post_title`** from **`post_slug`**: `sydney` → `Sydney`, `new-zealand` → `New Zealand`.
- **No** `" Showroom"` suffix.
- **Also set `menu_order`** on each showroom post to match the **legacy tab order** (left → right):
- **Does not** update `warehouse` meta.
- Tab labels benefit after BatchPress; card heading still uses `warehouse` until a future switch.

### Swiper

- Used when **marker click** yields **N > 1** posts for a state.
- **Tab click** always shows **one** card — no swiper.
- Reuse theme Swiper dependency (`src/js/quick-view-gallery.js` pattern).
- Init/destroy on marker-driven multi-card view; tear down when user picks a single tab.

---

## Current vs target architecture

### Current

```text
get_posts(showrooms)
  → filter by hard-coded city key (id meta + get_locations_config whitelist)
  → inline card HTML (duplicate CSS)
  → city tabs + city markers (same key)
```

### Target

```text
get_posts(showrooms) + get_terms(showroom-locations)
  → tabs = one per post (label post_title, data slug + state term slug)
  → markers = internal config[term_slug] for terms with ≥1 post
  → tab click  → one card (showroom-card, warehouse title, directions)
  → marker click → cards for all posts in term; highlight tabs; swiper if N > 1
```

---

## Implementation phases

### Phase A — ACF field group cleanup (JSON only) ✅ partial

- [x] **A1.** Remove `location`, `state` from `group_showrooms_fields.json`
- [ ] **A2.** Remove `id` from field group JSON
- [ ] **A3.** Remove `map` from field group JSON
- [ ] **A4.** **Keep `warehouse`** in field group JSON (card title for now)
- [ ] **A5.** Sync ACF in WP admin; verify meta box fields match JSON

### Phase B — Data layer: posts, taxonomy, slug

- [ ] **B1.** `get_all_showrooms()` — all published showrooms with taxonomy term slug(s) per post
- [ ] **B2.** `get_showrooms_by_state( $term_slug )` — posts assigned to a taxonomy term
- [ ] **B3.** Drop `resolve_location_key()` id-meta-first logic; use **`post_slug` only**
- [ ] **B4.** **Keep `warehouse`** reads in locator + `showroom-card-functions.php` (no `post_title` for card heading yet)
- [ ] **B5.** Update `context/notes/showroom-locator-and-card-inventory.md` runtime object shape

### Phase C — Internal state marker config

- [ ] **C1.** `get_state_markers_config()` keyed by **taxonomy term slug** (migrate coords from current city config → state slugs)
- [ ] **C2.** `get_active_marker_terms()` — terms with ≥1 published showroom
- [ ] **C3.** Remove city whitelist drop logic; posts drive tabs, terms drive markers
- [ ] **C4.** ACT / NT: config entry optional; marker omitted until showroom assigned

### Phase D — Integrate `showroom-card` block

- [ ] **D1.** Replace inline card HTML with `setup_postdata()` + `do_blocks( chairforce_get_showroom_card_blocks_markup() )`
- [ ] **D2.** Compact: `ctaType: 'directions'`; full: `ctaType: 'learn-more'`
- [ ] **D3.** Strip duplicated card rules from locator `style.scss` files
- [ ] **D4.** Card panels keyed by `data-showroom-slug`; single visible panel on tab click
- [ ] **D5.** Remove `showCta` attribute from locator blocks (card owns CTA)

### Phase E — Post tabs + state map + Swiper (compact)

- [ ] **E1.** `render_tabs()` — one tab per post; label = `post_title`; `data-showroom-slug` + `data-showroom-state`
- [ ] **E2.** `render_markers()` — one marker per active state term (internal config coords)
- [ ] **E3.** `view.js` — tab click: show one card, set active tab + active marker for post's state
- [ ] **E4.** `view.js` — marker click: show all cards for state, highlight all matching tabs, Swiper if N > 1
- [ ] **E5.** Sass for tab highlight states + swiper region
- [ ] **E6.** a11y: tab list + swiper keyboard/pagination when swiper visible

### Phase F — Full locator alignment

- [ ] **F1.** Same tab/marker interaction model as compact
- [ ] **F2.** Featured/grid layout for marker multi-select (or single featured + grid of rest)
- [ ] **F3.** Share marker config + data helpers with compact locator (single PHP source)

### Phase G — Editor + attributes

- [ ] **G1.** Replace hard-coded `LOCATION_OPTIONS` — default tab = post slug list from live showrooms
- [ ] **G2.** `defaultShowroom` attr (post slug) for initial tab; map default follows that post's state
- [ ] **G3.** SSR preview QA in Site Editor

### Phase H — BatchPress (optional, last)

- [ ] **H1.** Job: `class-normalise-showroom-locator-posts.php` in normalise plugin (`wp-content/plugins/chairforce-woodmart-data-normalise/jobs/`) — pattern: `class-normalise-showroom-gallery.php`
- [ ] **H2.** `post_title` ← slug only: `sydney` → `Sydney`, `auckland` → `Auckland` (ucwords / hyphen → space; **no suffix**)
- [ ] **H3.** `menu_order` ← fixed map by **`post_slug`** (locator tab order, legacy Figma):

| `menu_order` | `post_slug` | `post_title` (after job) |
|---:|---|---|
| 0 | `sydney` | Sydney |
| 1 | `brisbane` | Brisbane |
| 2 | `melbourne` | Melbourne |
| 3 | `adelaide` | Adelaide |
| 4 | `perth` | Perth |
| 5 | `hobart` | Hobart |
| 6 | `auckland` | Auckland |

- [ ] **H4.** Skip title update if `post_title` already matches derived value; always set `menu_order` when slug is in map (idempotent)
- [ ] **H5.** Report mode before apply (list current vs target title + order per post)
- [ ] **H6.** **Do not** modify `warehouse` meta
- [ ] **H7.** New showrooms not in map: leave `menu_order` unchanged; title rule still applies from slug

**Not blocking Phases B–G.** Locator already queries `menu_order ASC` (see `class-showroom-locator.php`).

---

## Verification matrix

| Case | Expected |
|---|---|
| Click Sydney tab | Sydney card only; NSW marker active; Sydney tab highlighted |
| Click Queensland marker (1 post) | Brisbane card; Brisbane tab highlighted |
| Click Queensland marker (2 posts, future) | Swiper with 2 cards; both QLD tabs highlighted |
| ACT / NT, no posts | No marker |
| New showroom + assign VIC | VIC marker appears; new tab with `post_title` |
| Card heading | **ACF `warehouse`** (e.g. "Brisbane Showroom / Warehouse") |
| Get Directions | Google Maps with address + site title |
| Learn More (full layout) | Permalink on showroom post |
| Auckland, no gallery | Placeholder image |
| After BatchPress | Tab order Sydney → Auckland; tab labels city names; card still uses `warehouse` |

---

## Key files (touch list)

| Area | Path |
|---|---|
| ACF | `acf-json/group_showrooms_fields.json` |
| Locator SSR | `lib/class-showroom-locator.php`, `lib/class-showroom-locator-full.php` |
| Card | `includes/showroom-card-functions.php`, `src-jsx-blocks/showroom-card/` |
| Locator JS | `src-jsx-blocks/showroom-locator/view.js`, `showroom-locator-full/view.js` |
| Locator SCSS | `src-jsx-blocks/showroom-locator/style.scss`, `showroom-locator-full/style.scss` |
| Swiper reference | `src/js/quick-view-gallery.js` |
| CPT / taxonomy | `lib/class-content-types.php` |
| Notes | `context/notes/showroom-locator-and-card-inventory.md` |
| BatchPress (later) | `wp-content/plugins/chairforce-woodmart-data-normalise/jobs/` |

---

## Risks / open questions

1. **Full locator** — featured vs grid when marker selects multiple posts (Swiper vs stacked grid).
2. **Hobart address** — marketing copy not a street address; Maps query quality (existing issue).
3. **Learn Our {X} Showroom** CTA — city string source while card uses `warehouse` for title (likely parse from slug or separate field).
4. **Multi-state assignment** — **one state per showroom only**; first assigned `showroom-locations` term is used. Editors must not assign multiple states to one post.

---

## Decision log

| Date | Decision |
|---|---|
| 8 Aug 2026 | Map state-driven (taxonomy); tabs post-driven (all showrooms) |
| 8 Aug 2026 | Tab label = `post_title`; tab click = one card; marker click = all posts in state + highlight tabs |
| 8 Aug 2026 | Multiple posts per state on marker click → Swiper |
| 8 Aug 2026 | Card heading keeps **ACF `warehouse`** for now |
| 8 Aug 2026 | BatchPress: slug → `post_title` (`sydney` → `Sydney`), no "Showroom" suffix; **also set `menu_order`** Sydney(0) → Auckland(6); does not touch `warehouse` |
| 8 Aug 2026 | Locator query uses `menu_order ASC`; CPT supports `page-attributes` |
| 8 Aug 2026 | ACF: remove fields from JSON only; never delete post meta |
| 8 Aug 2026 | Internal marker config by state term slug |
| 8 Aug 2026 | Cards via `showroom-card`; compact = directions, full = learn-more |
