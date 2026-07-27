# Lucide Icon System Task List

## Phase 0 - Planning to Tasks

- [x] Convert architecture plan into actionable checklist.
- [x] Limit first implementation pass to Gutenberg button icons only.

## Phase 1 - Shared Icon Registry (For Button Flow)

- [x] Create shared icon slug list used by editor controls and runtime renderer.
- [x] Create curated Lucide icon map (slug -> Lucide icon export).
- [x] Add shared renderer utility to hydrate placeholder nodes into SVGs.

## Phase 2 - Gutenberg `core/button` Icon Support

- [x] Extend `core/button` block attributes:
  - [x] `chairforceIcon` (slug)
  - [x] `chairforceIconPosition` (`left` default, `right`)
- [x] Add Inspector controls in editor:
  - [x] icon dropdown
  - [x] position dropdown
- [x] Add consistent class hooks:
  - [x] `has-icon`
  - [x] `icon-left` / `icon-right`
  - [x] `icon-{slug}`
- [x] Inject icon placeholder into saved button markup:
  - [x] prepend for left
  - [x] append for right
- [x] Hydrate placeholders into Lucide SVG in editor context.
- [x] Hydrate placeholders into Lucide SVG on frontend.

## Phase 3 - Styling for Button Icons

- [x] Add button icon spacing/alignment styles in `src/sass/blocks/_button.scss`.
- [x] Ensure icon inherits text color (`currentColor`) on all button variants.
- [x] Ensure right-position icons render in correct order.

## Phase 4 - Verification

- [x] Build assets successfully.
- [x] Confirm no linter errors in changed files.
- [ ] Manual check in editor:
  - [ ] icon selectable
  - [ ] default is left
  - [ ] right position works
- [ ] Manual check on frontend:
  - [ ] SVG is rendered
  - [ ] classes are present
  - [ ] color/size align with button styles

---

## Deferred (Next Passes)

- [x] Lucide static font/CSS fallback for plugin/CSS-only contexts. Implemented
  as Sass mixins (`src/sass/icon-font/`) rather than the icon-font *class*
  system originally envisioned - see
  [Icon System](../../.cursor/rules/16-icon-system.mdc) "Surface 3". Example
  usage against WooCommerce's Add to Cart button in
  `src/sass/woocommerce/_buttons.scss`.
- [x] Register shared icon set with Icon Block plugin. See
  `context/plans/icon-block-plugin-integration-plan.md` and
  [Icon System](../../.cursor/rules/16-icon-system.mdc) "Surface 2"
  (`src/js-admin/register-custom-icons.js` +
  `src/js-admin/chairforce-icon-block-icons.js`).
