# Icon Block Plugin Integration Plan

## Goal

Register the theme's existing curated Lucide icon set with **The Icon Block**
plugin (`outermost/icon-block`, v2.0.0, by Nick Diego — already installed and
**active** on this site) so editors can insert standalone icons via that
block's inserter/search modal, grouped under a **"ChairForce"** category,
using the exact same icon set/labels already available in the Button block's
icon picker.

This is a JS-only integration using the plugin's documented `iconBlock.icons`
extensibility filter. No changes to the Button block icon system are needed.

---

## Background

### Why not WordPress core's native `core/icon` block?

Already investigated and reported separately: WordPress core ships a native
`core/icon` block backed by `WP_Icons_Registry`. On the WP version currently
installed (7.0.2), that registry is explicitly closed to third-party icons —
no public `register()`, no filters, nothing to hook into. (WP 7.1 introduces
public `wp_register_icon_collection()` / `wp_register_icon()` APIs; revisit
adding a "chairforce" collection there once core is upgraded — see
[Icon System](../../.cursor/rules/16-icon-system.mdc) for that note.)

### Why the Icon Block plugin works

Unlike core's registry, **The Icon Block** plugin exposes a genuine,
documented JS filter for third-party icon registration:

- Filter: `iconBlock.icons` (confirmed present in
  `wp-content/plugins/icon-block/build/index.js` v2.0.0 via
  `grep -o "iconBlock\.[a-zA-Z]*"`).
- Docs: [Adding Custom Icons to the Icon Block](https://nickdiego.com/adding-custom-icons-to-the-icon-block)
  (written for v1.2.0; filter/shape unchanged in v2.0.0 per source inspection).
- A second filter, `iconBlock.enableCustomIcons`, controls whether editors can
  paste ad hoc custom SVGs (defaults to `true`) — not something we need to
  touch.

### Reference implementation (this codebase)

A sibling theme already does exactly this and can be used as a direct
reference for wiring/build pattern (**do not copy its icon set** — Font
Awesome-style, fill-based icons — only its *mechanics*):

- `wp-content/themes/bjm-briks/src/js-admin/register-custom-icons.js` — defines
  `customIcons`, `customIconCategories`, `customIconType`, then
  `addFilter('iconBlock.icons', 'briks/icon-block-custom-icons', briksAddCustomIcons)`.
  All icons are hand-inlined raw SVG strings with `categories: ['briks']`.
  Enqueued indirectly by being `import`-ed into
  `wp-content/themes/bjm-briks/src/js-admin/index.js`, which is bundled into
  `index.js` and enqueued via that theme's `class-blocks-jsx.php` on
  `enqueue_block_editor_assets`.

Chairforce's `class-blocks-jsx.php` already enqueues `build/index.js` (built
from `src/js-admin/index.js`) via `enqueue_block_editor_assets` — **no PHP
changes are required**, only a new JS module + one new import line.

### Key technical difference from the reference: stroke vs. fill icons

Lucide icons are **stroke-based** (`stroke="currentColor"`, `fill="none"`,
`stroke-width="2"`), whereas the plugin's default icon set (and the Briks
reference) is fill-based (`fill="currentColor"`, no stroke). The plugin
supports this via a documented per-icon flag:

- `hasNoIconFill: true` — "Set to `true` for icons that use `stroke`
  attributes and/or should not have a color fill applied."

**Every Chairforce/Lucide icon registered must set `hasNoIconFill: true`**, or
the plugin's color control will likely try to apply a `fill` that fights with
`fill="none"` and produce a solid-filled icon instead of an outline icon.
This must be visually verified during implementation (Phase 3 below).

---

## Scope

### In Scope

- Register the same ~30-icon curated set from
  `src/js-admin/lucide-icon-options.js` (`CHAIRFORCE_LUCIDE_ICON_OPTIONS`) as
  a new icon type in the Icon Block plugin's library.
- Category/type label: **"ChairForce"** (type slug: `chairforce`).
- Source raw SVG markup from the `lucide-static` npm package (already a theme
  dependency), not hand-typed strings.
- Strip presentational attributes (`width`, `height`, `class`) from each SVG
  before registering, matching the reference pattern — the plugin controls
  sizing/color itself.
- Set `hasNoIconFill: true` on every registered icon.
- Single source of truth: reuse existing `CHAIRFORCE_LUCIDE_ICON_OPTIONS`
  slugs + labels — do not maintain a second, separately-curated icon list.
- Manual verification pass in the editor (search, insert, color, size,
  frontend render).

### Out of Scope (for this phase)

- Any changes to the Button block's icon system (font/CSS approach stays as-is).
- Registering icons with WordPress core's native `core/icon` block/registry
  (blocked until WP 7.1+, see Background).
- Expanding the curated icon list beyond the current ~30 (that's a separate,
  content/design decision, not a plumbing change).
- Making the Icon Block plugin a hard theme dependency (e.g. via TGMPA) — the
  plugin is already installed/active on this site; formal dependency
  declaration can be a follow-up if desired.

---

## Technical Architecture

### A) Icon Source: Generate, Don't Hand-Copy 30 SVG Strings

The Briks reference hand-inlines every SVG string directly in the JS file.
For our curated list, prefer a **generated** data file instead of manually
copy-pasting 30 SVGs (error-prone, drifts from `lucide-icon-options.js` over
time, hard to keep in sync when icons are added/removed):

1. Add a small Node script, e.g.
   `bin/generate-icon-block-icons.js` (or under an existing `bin/`-style
   location if one exists), run via a new `npm run icons:generate` script.
2. The script:
   - Imports `CHAIRFORCE_LUCIDE_ICON_OPTIONS` (or a shared JSON/JS list) for
     the slug/label pairs.
   - Reads each `node_modules/lucide-static/icons/{slug}.svg`.
   - Strips `width`, `height`, and `class` attributes from the root `<svg>`
     tag (keep `xmlns`, `viewBox`, `fill="none"`, `stroke="currentColor"`,
     `stroke-width`, `stroke-linecap`, `stroke-linejoin`, and all child
     elements untouched).
   - Emits a generated JS file (e.g.
     `src/js-admin/chairforce-icon-block-icons.generated.js`) exporting a
     ready-to-use `CHAIRFORCE_ICON_BLOCK_ICONS` array (see shape below).
3. Commit the generated file (simple, reviewable diff) rather than generating
   it at webpack build time — avoids needing a custom webpack loader/config,
   which this theme intentionally doesn't have (default `wp-scripts` only).

This mirrors the same "repeatable generator script" pattern already agreed on
for the icon system in an earlier planning discussion, applied here to a
second (but related) problem.

**Simpler alternative** (note, not recommended as primary): hand-copy all 30
SVGs directly into `register-custom-icons.js` like the Briks reference does.
Faster to ship, but creates permanent drift risk against
`lucide-icon-options.js`. Flag this trade-off explicitly if timeline pressure
makes the generator script not worth it.

### B) Filter Registration Module

New file: `src/js-admin/register-custom-icons.js` (same filename as the Briks
reference, for consistency across the theme family):

```js
import { CHAIRFORCE_ICON_BLOCK_ICONS } from './chairforce-icon-block-icons.generated';

wp.domReady( () => {
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;

	function chairforceAddCustomIcons( icons ) {
		const customIconCategories = [
			{ name: 'chairforce', title: __( 'ChairForce', 'chairforce' ) },
		];

		const customIconType = [
			{
				isDefault: true,
				type: 'chairforce',
				title: __( 'ChairForce', 'chairforce' ),
				icons: CHAIRFORCE_ICON_BLOCK_ICONS,
				categories: customIconCategories,
			},
		];

		return [].concat( icons, customIconType );
	}

	addFilter(
		'iconBlock.icons',
		'chairforce/icon-block-custom-icons',
		chairforceAddCustomIcons
	);
} );
```

Each entry in `CHAIRFORCE_ICON_BLOCK_ICONS` follows the plugin's documented
shape:

```js
{
	name: 'shopping-cart',       // matches CHAIRFORCE_LUCIDE_ICON_OPTIONS slug
	title: 'Shopping Cart',      // matches existing label
	icon: '<svg xmlns="..." viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">...</svg>',
	categories: [ 'chairforce' ],
	hasNoIconFill: true,
}
```

Note: the plugin auto-prefixes icon `name` with `{type}-` on registration
(`chairforce-shopping-cart`) if not already prefixed — leave `name` as the
bare slug and let the plugin handle prefixing, matching the reference
implementation's behavior.

### C) Wiring In

Add one import line to the existing entry point (no other JS or PHP changes):

```js
// src/js-admin/index.js
import './block-styles';
import './editor-curation';
import './button-icons';
import './register-custom-icons'; // new
```

`enqueue_block_editor_assets` in `lib/class-blocks-jsx.php` already enqueues
the compiled `build/index.js`, so the new filter registration ships for free
once built.

---

## Implementation Phases

### Phase 1 — Icon Data Generation

1. Decide generator-script vs. hand-copy (see Section A trade-off).
2. If generator script: implement `bin/generate-icon-block-icons.js`, wire up
   `npm run icons:generate`, run it, commit the generated output.
3. Verify generated SVGs render correctly as standalone markup (spot-check a
   handful, e.g. `search`, `shopping-cart`, `chevron-right`).

**Deliverable**: `chairforce-icon-block-icons.generated.js` (or equivalent)
with 30 icon objects, each with `name`, `title`, `icon`, `categories`,
`hasNoIconFill: true`.

### Phase 2 — Filter Registration

1. Create `src/js-admin/register-custom-icons.js` per Section B.
2. Add the import to `src/js-admin/index.js`.
3. `npm run build:assets` (theme directory).

**Deliverable**: Build succeeds, no lint errors.

### Phase 3 — Manual Verification (Editor)

1. Insert an Icon Block; open the icon library/search modal.
2. Confirm a **"ChairForce"** category/section appears with all ~30 icons,
   correctly labeled.
3. Search by label (e.g. "cart") and confirm it surfaces the right icon.
4. Insert 2–3 icons and confirm:
   - Icon renders as an **outline** (not solid-filled) — this is the
     `hasNoIconFill` check.
   - Color control (text color / custom color) actually recolors the stroke.
   - Size control resizes correctly, no stray `width`/`height` conflicts from
     the original SVG.
5. Check the frontend render of a page containing inserted icons — confirm
   markup and visual parity with the editor.

**Deliverable**: Icon Block usable end-to-end with ChairForce icons, visually
correct in both editor and frontend.

### Phase 4 — Documentation

1. Update [Icon System](../../.cursor/rules/16-icon-system.mdc) with a new
   section documenting this second icon consumer (Icon Block plugin), its
   file locations, and the "regenerate via `npm run icons:generate`" workflow
   — so both icon surfaces (Button block + Icon Block) are documented in one
   place.
2. Cross-reference this plan from the existing
   `context/plans/lucide-icon-system-task-list.md` "Deferred" section
   ("Register shared icon set with Icon Block plugin"), since this plan
   completes that deferred item.

---

## File-Level Plan (Expected Touch Points)

- `wp-content/themes/chairforce/bin/generate-icon-block-icons.js` — new,
  generator script (if generator approach chosen).
- `wp-content/themes/chairforce/src/js-admin/chairforce-icon-block-icons.generated.js` —
  new, generated icon data.
- `wp-content/themes/chairforce/src/js-admin/register-custom-icons.js` — new,
  `iconBlock.icons` filter registration.
- `wp-content/themes/chairforce/src/js-admin/index.js` — modified, add one
  import.
- `wp-content/themes/chairforce/package.json` — modified, add
  `icons:generate` script (if generator approach chosen).
- `.cursor/rules/16-icon-system.mdc` — modified, document the second icon
  consumer.
- `context/plans/lucide-icon-system-task-list.md` — modified, close out the
  deferred "Register shared icon set with Icon Block plugin" item.

No PHP changes. No Sass changes (the plugin provides its own editor/frontend
styling).

---

## Acceptance Criteria

1. Icon Block's inserter/search modal shows a "ChairForce" icon category with
   all icons from `CHAIRFORCE_LUCIDE_ICON_OPTIONS`, same labels as the Button
   block picker.
2. Inserted icons render as outlines (stroke), not filled shapes.
3. Color and size controls work correctly on ChairForce icons.
4. No regressions to the plugin's own default icon library (WordPress icons,
   social logos, media icons) — those still appear alongside ours.
5. No regressions to the existing Button block icon picker (separate system,
   untouched).
6. Build passes (`npm run build:assets`), no new lint errors.

---

## Risks and Mitigations

- **Risk**: `hasNoIconFill` doesn't fully prevent unwanted fill styling in
  all cases (plugin internals not exhaustively verified from static
  analysis alone).
  - *Mitigation*: Phase 3 visual verification is mandatory before calling
    this done; if fill issues persist, inspect the plugin's `save.js`/color
    application logic directly (not just filter registration) and consider
    baking `fill="none"` more defensively into the source SVG.
- **Risk**: Icon list drift between `lucide-icon-options.js` (Button block)
  and the Icon Block data file if hand-copied.
  - *Mitigation*: generator script derives the Icon Block data from the same
    source list — regenerate whenever the curated list changes.
- **Risk**: Icon Block plugin update (future) changes the `iconBlock.icons`
  filter shape or removes it.
  - *Mitigation*: filter usage is isolated to one small file
    (`register-custom-icons.js`); low blast radius if it needs updating.
- **Risk**: Icon Block plugin is deactivated/removed on some environment.
  - *Mitigation*: registration code only matters while the plugin is active;
    it's a no-op filter registration otherwise (WordPress simply won't call
    a filter that's never applied by an inactive plugin) — no fatal errors.

---

## Recommended Execution Order

1. Phase 1 (icon data generation) — get the data right first.
2. Phase 2 (filter registration + build).
3. Phase 3 (manual verification) — do not skip, especially the
   `hasNoIconFill` stroke/fill check.
4. Phase 4 (documentation/cross-linking).
