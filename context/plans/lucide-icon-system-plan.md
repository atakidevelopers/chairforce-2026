# Lucide Icon System Plan

## Goal

Establish a reusable icon system for the Chairforce theme that supports:

1. Primary: injected SVG icons for Gutenberg `core/button`.
2. Fallback: Lucide static font/CSS classes for plugin/CSS-only contexts.
3. Shared naming: one icon slug source used by both approaches.
4. Icon Block plugin: register the same custom icon set so standalone icon blocks match button icons.

This plan keeps editor UX clean and preserves visual consistency across core blocks, plugin buttons, and standalone icons.

---

## Scope

### In Scope

- Extend `core/button` with optional icon selection.
- Add icon position control (`left` default, `right` optional).
- Add theme-level Lucide icon source-of-truth map.
- Add CSS fallback classes for contexts where markup cannot be changed.
- Register icon set for the Icon Block plugin.
- Add style and QA coverage for editor + frontend.

### Out of Scope (for this phase)

- Icon animation system.
- Per-icon color overrides in editor UI.
- Dynamic upload of custom SVGs by editors.
- Full migration of all plugin buttons in one pass.

---

## Technical Architecture

## A) Shared Icon Source (Single Source of Truth)

Create a canonical icon registry in theme assets and code:

- `assets/icons/lucide/` (self-hosted icon assets).
- `includes/lucide-icons.php` (or similar helper) with icon slug list and resolver.
- Slug convention: kebab-case matching Lucide names (example: `shopping-cart`, `file-text`, `truck`).

Use this registry in:

- Gutenberg button icon picker.
- SVG injection renderer.
- CSS fallback class mappings.
- Icon Block custom icon registration.

## B) Primary Flow: SVG Injection for `core/button`

Extend `core/button` in admin JS:

- New attributes:
  - `chairforceIcon` (string slug, optional)
  - `chairforceIconPosition` (`left` or `right`, default `left`)
- Inspector controls:
  - icon select
  - position toggle
  - clear icon

Render strategy:

- Add class hooks: `has-icon`, `icon-left`, `icon-right`, `icon-{slug}`.
- Inject real SVG markup inside `.wp-block-button__link` via wrapper:
  - `<span class="cf-btn__icon" aria-hidden="true">...</span>`
- Use `currentColor` so icons follow button text color tokens.

## C) Fallback Flow: Lucide Static Font/CSS for CSS-only Contexts

Use Lucide static font classes for plugin contexts where HTML injection is not possible.

- Enqueue Lucide static CSS/font assets from theme.
- Add utility classes:
  - `.has-icon-left`, `.has-icon-right`
  - `.icon-{slug}` (mapped to Lucide class or pseudo-element strategy)
- Theme SCSS controls spacing, size, alignment, and color inheritance.

Note: keep this as a fallback path only to avoid loading unnecessary icon glyphs where not needed.

## D) Icon Block Plugin Registration

Register custom icon set using the plugin extension API and docs:

- Provide same slug list as shared registry.
- Ensure editor search labels map cleanly to slugs.
- Verify Icon Block output and button icon set stay in sync.

---

## Implementation Phases

## Phase 1 - Foundation and Asset Registry

1. Add icon registry helper (PHP) and slug map.
2. Add theme constants/helpers for icon paths/URLs.
3. Add initial curated icon list (start with approved design set).
4. Define naming and validation rules for slugs.

Deliverable:

- Shared registry ready for JS, PHP rendering, and plugin registration.

## Phase 2 - Gutenberg Button Icon Support (Primary)

1. Add block extension JS for `core/button` attributes and controls.
2. Add button class naming hooks (`has-icon`, `icon-left`, `icon-right`).
3. Implement frontend/editor SVG rendering pipeline.
4. Add SCSS for icon spacing, alignment, and responsive behavior.

Deliverable:

- Any button style supports optional icon with left/right placement.

## Phase 3 - CSS Fallback for Plugin Buttons

1. Enqueue Lucide static font/CSS assets.
2. Add reusable SCSS utility classes and mixins for plugin buttons.
3. Add targeted integration examples for known plugin button selectors.
4. Ensure fallback icons inherit `currentColor` and button sizing tokens.

Deliverable:

- Plugin buttons can opt into icon classes without markup injection.

## Phase 4 - Icon Block Plugin Integration

1. Register custom icon set from shared registry.
2. Ensure slugs and labels match button picker options.
3. Verify editor insertion + frontend output + style alignment.

Deliverable:

- Standalone icon block uses the same icon system.

## Phase 5 - QA, Demo Content, and Hardening

1. Add/extend demo content to test:
   - button style variants with icons
   - left/right positions
   - dark/light background contexts
   - plugin fallback buttons
   - standalone icon block
2. Validate accessibility:
   - decorative icons use `aria-hidden="true"`
   - no duplicate spoken labels
3. Test caching/build and regression pass in editor/frontend.

Deliverable:

- Stable base ready for production iteration.

---

## File-Level Plan (Expected Touch Points)

- `src/js-admin/`:
  - button extension module
  - inspector controls and attribute filters
- `src/sass/blocks/_button.scss`:
  - icon placement/alignment classes
- `src/sass/_mixins.scss`:
  - optional icon utility mixins for reuse
- `includes/`:
  - icon registry helper
  - enqueue and plugin integration hooks
- `assets/icons/lucide/`:
  - curated icon assets
- `context/demo-content/all-ements.md`:
  - iconized button and icon block showcase

---

## Acceptance Criteria

1. Editors can optionally pick an icon for any `core/button`.
2. Default icon placement is left; right is selectable.
3. Existing button style system remains intact (`primary`, `secondary`, `ghost`, `light`, default).
4. Plugin/CSS-only buttons can render icons through class-based fallback.
5. Icon Block plugin exposes same icon set and slugs.
6. Icon color follows text color tokens via `currentColor`.
7. Build passes and no regression in existing button behavior.

---

## Risks and Mitigations

- Risk: visual mismatch between SVG primary path and font fallback path.
  - Mitigation: maintain curated list; keep fallback limited to constrained plugin contexts.
- Risk: icon list drift between systems.
  - Mitigation: enforce shared registry as the only source.
- Risk: Gutenberg filter conflicts with plugin blocks.
  - Mitigation: scope extension strictly to `core/button`.

---

## Recommended Execution Order

1. Phase 1 (registry)
2. Phase 2 (button SVG primary)
3. Phase 4 (Icon Block registration)
4. Phase 3 (plugin CSS fallback)
5. Phase 5 (QA + demo updates)

This order delivers high-value editor functionality early while keeping fallback integration modular.
