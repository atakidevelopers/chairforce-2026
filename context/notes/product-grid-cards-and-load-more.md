# Product grid cards & Product Collection overrides — discussion notes

Captured: 30 Jul 2026. **Resolved: 31 Jul 2026** — shared
`parts/product-card.html` template part (`2a455c9`). **Superseded Aug 2026** by
locked **`chairforce/product-card`** block — see
`context/notes/product-card-block-migration.md` and
`context/implementation/product-grid.md` for current wiring.
Block Hooks plan (§3.2) was spiked but not shipped; template-part approach
chosen initially, then migrated to the locked block.

How to get the **same product card markup** on every Product Collection grid
(shop, related, upsells, etc.) — swatches, quick view, and template strategy.

**Load More** is documented separately:
`context/notes/load-more-findings.md`.

Related existing docs:

- `context/existing-functionality/03-product-card-grid-swatches.md` — legacy
  Woodmart behaviour (source of truth for parity).
- `context/plans/3b-3d-event-pattern-and-grid-swatches-plan.md` — grid swatches
  block + archive template decision.
- `context/plans/3h-quick-view-plan.md` — quick view implementation + verification.

---

## 1. Current state — product card composition

### 1.1 Blocks & PHP

| Piece | Block / class | Render path |
|---|---|---|
| Grid swatches | `chairforce/product-swatches` | `src-jsx-blocks/product-swatches/render.php` → `Product_Swatches::render_grid_swatches()` |
| Quick view trigger | `chairforce/quick-view-button` (optional in template) **or** runtime injection | `render.php` / `chairforce_get_quick_view_button_html()` |
| Quick view content | REST `chairforce/v1/quick-view/{id}` | `includes/rest-api/quick-view.php` — server-rendered popup markup |
| Grid image swap | `src/js/single-product-swatches.js` (delegated hover/click) | Works on any grid node matching selectors |
| Quick view shell | `src/js/quick-view.js` | Delegated click on `.cf-quick-view-trigger` |

Both card blocks declare `"ancestor": ["woocommerce/product-template"]` and
use `"usesContext": ["postId"]`.

### 1.2 Where cards are customised today

**Shop / category / attribute archives**

- Theme override: `templates/archive-product.html`.
- Inserts `chairforce/product-swatches` inside `cf-wrapp-swatches`, under
  `cf-card-media` + `woocommerce/product-image` (Woodmart DOM order).
- Does **not** insert `chairforce/quick-view-button` in the template file;
  quick view is added at runtime (see below).
- Live dev site may use a **DB-customised** copy of this template (post ID
  1514388 per `3h-quick-view-plan.md`) — theme file ≠ necessarily what WP
  renders until synced in Site Editor.

**Related products, “You may also like…”, upsells, other Product Collection grids**

- Use WooCommerce defaults, e.g. pattern `woocommerce-blocks/related-products`
  (`wp-content/plugins/woocommerce/patterns/related-products.php`).
- Default `woocommerce/product-template` inner blocks: image → title → price →
  button — **no** `chairforce/product-swatches`, **no** wrapper groups
  (`cf-card-media`, `cf-wrapp-swatches`).
- Referenced from blockified single product template via
  `<!-- wp:pattern {"slug":"woocommerce-blocks/related-products"} /-->`.

### 1.3 Asymmetric workaround — quick view only

`Chairforce\WooCommerce_Archive::inject_quick_view_button()` hooks
`render_block` on `woocommerce/product-image` when
`isDescendentOfQueryLoop` is true:

- Injects the eye trigger into **any** query-loop product image (archives,
  related, upsells) even when the block is absent from the template.
- Explicitly documented in class comment as covering related/upsell grids.

**Swatches have no equivalent runtime injection.** They only render where
`chairforce/product-swatches` exists in the saved block tree.

### 1.4 Limitation (why this note exists)

> Manual template markup in `archive-product.html` only affects the **main
> archive Product Collection** (`inherit: true`). It does **not** propagate to
> other Product Collection instances (related, upsells, hand-picked grids, future
> home-page best sellers, etc.).

Any feature that reuses archive product-template markup (including Load More
partial render — see `load-more-findings.md`) needs a **global canonical card**
 definition first.

---

## 2. Approaches considered (card composition)

### 2.1 Manual block insertion per template / pattern (current for swatches)

- **How:** Edit each FSE template or WC pattern that contains
  `woocommerce/product-template` and insert Chairforce blocks + layout groups.
- **Pros:** Full layout control (`cf-card-media`, `cf-wrapp-swatches`); explicit;
  matches 3d plan.
- **Cons:** Must touch every template/pattern; easy to miss one; DB vs file drift;
  canonical markup must stay in sync wherever cards are rendered.

### 2.2 Block Hooks API (`blockHooks` in `block.json`)

- **How:** Auto-insert a block relative to an anchor, e.g.
  `"woocommerce/product-button": "before"` ([WP 6.5 Block Hooks](https://developer.wordpress.org/news/2024/03/exploring-the-block-hooks-api-in-wordpress-6-5/)).
- **Pros:** Editors don’t have to remember to add swatches everywhere; works
  across templates that share the anchor block.
- **Cons for Chairforce:**
  - `before product-button` places swatches **after title + price**, not under
    the image inside `cf-wrapp-swatches` — conflicts with designed card order.
  - Hooks cannot insert *into* a Group wrapper; only before/after/firstChild/
    lastChild on the anchor.
  - Targets **every** `woocommerce/product-button` unless gated with
    `hooked_block_types` / `hooked_block_{name}` filters ([Make WP 6.5 hooks update](https://make.wordpress.org/core/2024/03/04/updates-to-block-hooks-in-6-5/)).
  - Editors can remove hooked blocks; WP remembers dismissal unless locked.
- **Verdict (superseded 30 Jul 2026 — see §3.2b–e):** originally "not a
  drop-in," on the assumption swatches needed to be nested inside a
  `cf-wrapp-swatches` Group for correct layout. That assumption turned out
  to be wrong — WooCommerce's own `.wc-block-product` per-card wrapper
  already gives every card a positioning context, and swatches only need
  correct *sibling order* (via a `core/post-title`-anchored hook), not a
  specific wrapper. Client direction is now to use this as the *primary*
  mechanism.

### 2.3 Runtime `render_block` injection (current for quick view)

- **How:** Filter rendered block HTML when block name + context match; append
  markup without template edits.
- **Pros:** Works on all grids that render `woocommerce/product-image` with
  `isDescendentOfQueryLoop`; already proven for quick view.
- **Cons:** Imperative; harder to preview in editor unless paired with a
  placeholder block; swatches need a sensible anchor (image? price? button?) and
  wrapper structure is awkward to inject as raw HTML strings.

### 2.4 Shared block pattern for product-template innards

- **How:** Theme registers e.g. `chairforce/product-card-template` pattern
  (image + wrappers + swatches + title + price + button) and replaces WC
  patterns / template sections to use it.
- **Pros:** Single source of truth; editor-visible; WC-native FSE pattern.
- **Cons:** Still must reference the pattern in each Product Collection context
  (archive template, related pattern, upsells, etc.) unless combined with Block
  Hooks.

---

## 3. Recommended direction — Product Collection & card parity

### 3.1 Goal

One **canonical product card** used everywhere `woocommerce/product-template`
renders, not only on `archive-product.html`.

### 3.2 Right WooCommerce / FSE approach (layered)

**Superseded 30 Jul 2026** — client direction: use the WordPress/WooCommerce
**Block Hooks API** as the *primary* mechanism, not "manually edit every
template/pattern" (§3.2 as originally written above, kept for context/
history — this is what replaces it). §2.2's original objection (*"before
product-button places swatches after title+price, not under the image"*) is
resolved below — it assumed hooked blocks need a `cf-wrapp-swatches`/
`cf-card-media` **Group wrapper** for layout to work, which turned out to be
unnecessary once the actual WooCommerce per-card DOM was inspected.

### 3.2b The finding that unblocks this: `.wc-block-product`

Every product card WooCommerce's `product-template` block renders — on
`archive-product.html`, the built-in `woocommerce-blocks/related-products`
pattern, upsells, anywhere — is wrapped in the **same stable WooCommerce-owned
class**, confirmed by fetching live HTML:

```html
<li class="wc-block-product post-1000290 product type-product status-publish …">
```

`.wc-block-product` is not template-specific — it's WooCommerce core's own
per-item wrapper, always present regardless of which template/pattern renders
the card and regardless of which inner blocks that template chose to include.
That means:

- **One global CSS rule** — `.wc-block-product { position: relative; }` —
  gives every product card a positioning context, for free, everywhere.
  `.cf-card-media`'s `position: relative` (currently a per-template Group
  wrapper we have to remember to add) becomes redundant; the quick-view
  trigger's `position: absolute; top; right;` already works off *any*
  positioned ancestor, not specifically `.cf-card-media` — confirmed current
  Sass (`src/sass/quick-view/_card-media.scss`) already independently added a
  broader `.wc-block-product-template .wc-block-components-product-image`
  selector for the same reason during 3h's "polish" commit, without yet
  connecting it to *removing* the wrapper Group.
- A hooked block **does not need to be physically nested inside** a specific
  wrapper to be positioned correctly against the card — it only needs to be
  *somewhere* inside `.wc-block-product`, which Block Hooks guarantees
  (hooked blocks become genuine siblings within `product-template`'s
  children, i.e. always inside the `<li class="wc-block-product …">`).

This is also the answer to the other-agent discussion's "add the wrapper
group markup in our block render" idea: we don't need our block to render its
own wrapper at all, because the positioning context (`.wc-block-product`)
already exists on every card automatically. That technique (a block owning
its own container markup) is still worth remembering for a future case where
a hooked block genuinely needs *more* structure than "be a sibling inside the
card" — just not needed for quick view or swatches.

### 3.2c Anchors — split by block, not one shared `before product-button` hook

Swatches need to stay **in normal flow** between image and title (visual
order matters); the quick-view trigger is **absolutely positioned** (visual
order doesn't matter, only DOM/tab order for a11y). Two different anchors
avoid any ambiguity about which hooked block renders first at a shared
anchor+position:

| Block | Anchor | Position | Why |
|---|---|---|---|
| `chairforce/quick-view-button` | `woocommerce/product-image` | `after` | Sits absolutely over the image regardless of DOM order; "after image" is the sensible a11y/DOM reading order. |
| `chairforce/product-swatches` | `core/post-title` (the actual block name behind `<!-- wp:post-title -->`, despite the `__woocommerceNamespace` attribute cosmetic label) | `before` | Lands between image and title in normal flow — matches current design without needing `cf-wrapp-swatches`. |

Declared in each block's own `block.json` via the `blockHooks` property (see
the pasted reference discussion for the exact shape) — this is the *simple*
form of Block Hooks, appropriate here because both anchors
(`woocommerce/product-image`, `core/post-title`) only ever appear inside
`woocommerce/product-template` in this codebase; there's no other context
where an unconditional hook on them would misfire. (The `hooked_block_types`
filter form exists for cases needing conditional targeting — e.g.
WooCommerce core's own only real precedent, `BlockHooksTrait.php`, uses it to
target *header template parts specifically*, not a bare block-name anchor —
not needed here since our anchors are already narrow enough.)

`.cf-wrapp-swatches`'s only actual CSS (`display:flex; flex-direction:column;
align-items:center;`) moves to target the swatches block's own auto-generated
wrapper class (`.wp-block-chairforce-product-swatches`) directly — one less
Group block, one less thing to remember to add per template.

### 3.2d What this removes from every template/pattern

- `cf-card-media` Group wrapper — gone (positioning now comes from
  `.wc-block-product`, global).
- `cf-wrapp-swatches` Group wrapper — gone (styling moves to the block's own
  class).
- The explicit `<!-- wp:chairforce/quick-view-button /-->` line — already
  gone since 3h's "polish" commit (superseded by the `render_block` filter,
  which itself would become removable once Block Hooks does the same job
  through the standard API instead of an imperative filter).
- `WooCommerce_Archive::inject_quick_view_button()` — removable once Block
  Hooks is confirmed working end-to-end (§3.2e); keep it until then as a
  fallback, not both permanently (two mechanisms doing the same insertion is
  its own maintenance hazard — see the dead-code TODO already logged in
  `context/PROGRESS.md` for the *block* half of this same problem).
- `archive-product.html`'s `woocommerce/product-template` innards shrink to
  just: `product-image` → `post-title` → `product-price` → `product-button`
  (identical to WooCommerce's own default related-products pattern) — Block
  Hooks fills in swatches + quick view automatically, so this template
  finally *doesn't* need to differ from WooCommerce's stock card markup at
  all, which is the actual "parity everywhere" goal from §1.4.

### 3.2e Verification spike — DONE, passed (30 Jul 2026)

Ran a disposable spike (not the real feature) to de-risk the two open
questions below before touching the real Quick View/swatches code: a
`hooked_block_types` + `hooked_block_core/paragraph` filter pair in
`functions.php`, anchored to `woocommerce/product-image`/`after`, inserting
a literal visible `<p>` (see git history for the exact throwaway snippet —
removed once the spike below was confirmed).

**Result: it fires everywhere needed, including the one case that looked
risky on paper.**

- ✅ `archive-product.html` (`/product-category/chairs/cafe-chairs/`) — the
  paragraph appeared once per product card (12/12).
- ✅ Single-product page's "Related products" section
  (`/product/breeze-chair/`) — appeared once per related-product card,
  correctly positioned inside `.wc-block-components-product-image__inner-container`'s
  sibling content, right after our existing `cf-quick-view-button` and before
  `post-title` — i.e. **the exact anchor point §3.2c needs, on a
  plugin-owned pattern file we don't control.**

**Why this worked despite the theoretical concern in the old version of this
section** (kept below for the record, since it was a reasonable thing to
worry about and the resolution is worth knowing): `render_block_core_pattern()`
(`wp-includes/blocks/pattern.php`) itself just calls `do_blocks()` on the raw
pattern content with no hook-insertion step — so hooking anchors *inside* a
pattern looked like it should be invisible to Block Hooks. The actual
mechanism is one level up: `WP_Block_Patterns_Registry::register()` (and
`get_all_registered()`) calls `apply_block_hooks_to_content()` on **every**
registered pattern's `content` at registration/normalization time
(`wp-includes/class-wp-block-patterns-registry.php:211` and `:237`) — so by
the time `render_block_core_pattern()` fetches `$pattern['content']`, any
hooks anchored to blocks inside that pattern are already baked in as real
serialized block markup. This applies equally to WooCommerce's own
`woocommerce-blocks/related-products` pattern file, since it goes through
the same core registry. Net effect: **Block Hooks apply to templates,
*and* to any registered pattern (ours or a plugin's), transparently** — no
special-casing needed for "the pattern is plugin-owned."

Remaining lower-risk items from the original spike list, not re-verified
individually but no longer blocking (the `postId`-context concern is
subsumed by the successful related-products test above, since that pattern
correctly showed a different product per card, which wouldn't be possible if
context weren't flowing):

- `ignoredHookedBlocks` dismiss-behaviour note (§ below) — still just an
  editor-training note, not a code concern.
- Block editor round-trip (inserter, moving/removing a hooked block) — not
  yet spiked; do this as part of the real implementation, not before it,
  since it doesn't block a go/no-go decision the way the two items above did.

<details>
<summary>Original pre-spike concerns (for the record)</summary>

1. **No WooCommerce-native precedent for this exact anchor pair.**
   `src/Blocks/Utils/BlockHooksTrait.php` is WooCommerce's only real internal
   use of Block Hooks, and it targets **header template-part areas** (Mini
   Cart, Customer Account) via `hooked_block_types` + an `area` check — not
   `product-template`/`product-image`/`product-button`. A wordpress.org
   support thread shows a *community* example hooking `product-summary`/
   `product-stock-indicator` onto `product-template` itself (different
   anchor — the container, not an inner block), which surfaced a real gotcha
   worth knowing about even though it's a different anchor: WooCommerce
   auto-writes an `ignoredHookedBlocks` attribute onto `product-template`
   once an editor's session has evaluated hooks for it, to avoid
   re-inserting a hook the editor already dismissed. Confirmed via grep that
   **no** current theme template/pattern has this attribute yet — but once
   we ship the hook and someone removes it once in the Site Editor, it comes
   back permanently for that template/pattern (WP's documented "hooked
   blocks can be dismissed" behaviour) — worth a short note in editor
   training, not a code concern.
2. **Confirm `postId` context still flows to a *hooked* (not statically
   placed) child.** WooCommerce's own `ProductTemplate::render_content()`
   builds a fresh `WP_Block` per product with `postId` in
   `available_context`; a hooked block becomes a genuine member of
   `innerBlocks` before that loop runs, so it *should* receive `postId`
   identically to a manually-placed block (unlike the separate, narrower bug
   in `woocommerce/woocommerce#54381`/`#54382`, which is about *custom*
   context keys added via `render_block_context`, not the native `postId`
   `product-template` already provides — that bug doesn't block us). Still,
   confirm empirically rather than trusting this chain of reasoning blind.
3. **Confirm it actually fires on the built-in `woocommerce-blocks/
   related-products` pattern** (a WooCommerce-plugin-owned pattern file, not
   a theme file) — Block Hooks are documented to apply to "templates,
   template parts, and patterns" generically, but this project has zero
   prior test of hooking into a *plugin-owned* pattern specifically.
4. **Spike scope:** implement the two `blockHooks` declarations, verify on
   (a) `archive-product.html`, (b) a single-product page's "Related
   products" section, (c) the block editor (does the inserter still work,
   does moving/removing behave sanely), before touching any existing
   `render_block`-filter code or deleting any Group wrappers.

</details>

### 3.3 What *not* to rely on

- Editing only `archive-product.html` and expecting related/upsell grids to
  follow (the original problem this whole note exists to solve).
- A shared `hooked_block_types` filter keyed only to `product-button`/
  `product-image` block *names* with no anchor-context narrowing — fine here
  since both anchors are WC-block-template-only in this codebase, but don't
  copy this reasoning onto a future hook targeting a genuinely multi-context
  block (e.g. `core/post-title` is used in template contexts too, but not
  ones we ship — recheck if that ever changes).
- Block Hooks `before product-button` without accepting layout change (the
  original, now-resolved objection from §2.2).

---

## 4. Quick View — same global parity problem & approach

### 4.1 Current split model

| Surface | Quick view trigger | Quick view content |
|---|---|---|
| Archive (template may include block) | Runtime inject on `product-image` **or** block | REST fetch |
| Related / upsell grids | Runtime inject only | REST fetch |
| After future Load More append | Delegated handler (designed to work) | REST fetch |

Quick view **content** is already global — REST returns the same popup markup
regardless of which grid opened it.

Quick view **trigger placement** is global today thanks to
`WooCommerce_Archive::inject_quick_view_button()`.

### 4.2 Align quick view with the card composition strategy

**Superseded 30 Jul 2026** — with §3.2c's direction, this becomes simpler
than the original "decide one trigger strategy" framing:

1. Move the trigger to a `blockHooks` declaration in
   `chairforce/quick-view-button/block.json` (anchor `woocommerce/product-image`,
   position `after` — §3.2c), and remove
   `WooCommerce_Archive::inject_quick_view_button()` **once the spike (§3.2e)
   confirms it fires everywhere the filter currently does** — don't remove
   the filter first and hope the hook covers the same ground; run them in
   parallel with the existing `str_contains(…'cf-quick-view-trigger'…)` guard
   during the verification window, then delete the filter.
2. This also resolves the dead-code TODO already logged in
   `context/PROGRESS.md`: once the block is the thing Block Hooks inserts,
   its `render.php` becomes live again (no more parallel
   `chairforce_get_quick_view_button_html()` helper needed).
3. Related/upsell patterns and any future grid need **zero template edits**
   for either swatches or quick view once both hooks are in place — that's
   the whole point of moving off "edit every template/pattern."
4. **Verify** on: shop, category archive, single-product related pattern, and
   post–Load More append (see `load-more-findings.md`).

---

## 5. Action checklist

**Superseded 31 Jul 2026** — Block Hooks plan replaced by shared template part:

- [x] Verification spike (§3.2e): Block Hooks firing confirmed on archive +
      related-products pattern. **Passed 30 Jul 2026.**
- [x] **Shipped instead:** `parts/product-card.html` shared partial with explicit
      `chairforce/quick-view-button` + `chairforce/product-swatches` blocks
      (`2a455c9`). Referenced from `product-collection`, `product-related`, and
      `product-upsells` template parts.
- [x] Remove `WooCommerce_Archive::inject_quick_view_button()` (`2a455c9`).
- [x] Load More renders the same partial via REST (`d7d7acc`).
- [x] Update `context/PROGRESS.md` + related docs (31 Jul 2026).

**Not done (Block Hooks path — dropped):**

- ~~Add `blockHooks` declarations to swatches/quick-view blocks~~
- ~~Remove `cf-card-media`/`cf-wrapp-swatches` wrappers~~ — kept; layout unchanged

---

## 6. Decision log

| Date | Decision |
|---|---|
| 30 Jul 2026 | Card composition and Load More are separate workstreams (see `load-more-findings.md`). |
| 30 Jul 2026 | `archive-product.html`-only swatches insufficient for related/upsell grids. |
| 30 Jul 2026 | Quick view already cross-grid via `render_block`; swatches need the same *outcome* via pattern/template strategy (not necessarily the same mechanism). |
| 30 Jul 2026 | Block Hooks `before product-button` rejected for current layout (swatches belong under image in `cf-wrapp-swatches`). |
| 30 Jul 2026 | **Superseded the above** — client direction: use Block Hooks as the primary mechanism, not manual template/pattern edits. Unblocked by discovering WooCommerce's own `.wc-block-product` per-card wrapper class removes the need for a `cf-wrapp-swatches`/`cf-card-media` Group wrapper entirely (§3.2b) — split into two narrow anchors (`product-image`/`after` for quick view, `post-title`/`before` for swatches) instead of one shared `product-button` hook (§3.2c). Not yet implemented — needs the §3.2e verification spike first (no first-party WooCommerce precedent for hooking into `product-template`'s inner blocks specifically, only header areas). |
| 30 Jul 2026 | **Spike passed** (§3.2e) — Block Hooks confirmed viable on archive + related-products pattern. |
| 31 Jul 2026 | **Block Hooks not shipped** — chose shared `parts/product-card.html` template part instead (`2a455c9`). Same parity outcome; Load More reads the same partial (`d7d7acc`). |
