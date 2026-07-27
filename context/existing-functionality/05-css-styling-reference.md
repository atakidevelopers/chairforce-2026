# 05 — CSS / Styling Reference

> **QA finding — confirmed live configuration on this site.** The classes
> actually observed on both the product-card and single-product swatch
> wrappers (`wd-bg-style-3 wd-text-style-3 wd-dis-style-3 wd-size-m
> wd-shape-round`) resolve to exactly **one** combination out of the full
> matrix documented below: active style **3** (plain 1px border, shifts to
> gray-500 on hover/active), disabled style **3** (40% opacity + one gray
> diagonal line, top-left→bottom-right only, not a full X), size **M**,
> shape **round** (fully rounded, `border-radius:1em`). Per file `09` §3,
> the rebuild only needs to implement this single combination as a fixed
> Sass mixin/variant — not the full 4×3×3×6 configurable matrix Woodmart
> supports for every attribute.

Swatch CSS ships as small, conditionally-enqueued "parts" (each only loaded
inline on pages that actually render a swatch of that specific style/shape/
disabled-style, via `woodmart_enqueue_inline_style()`), assembled into the
compiled `style.css` / `style-elementor.css` at build time. Source parts
live in `wp-content/themes/woodmart/css/parts/`:

| Part file | Enqueued when |
|---|---|
| `woo-mod-swatches-base.min.css` | Any time swatches render at all (base layout: sizing, shape variables, `.wd-swatch`/`.wd-swatch-bg`/`.wd-swatch-text` structure). |
| `woo-mod-swatches-style-{1..4}.min.css` | Per-attribute, based on that attribute's `swatch_style` setting (file `02` §2). |
| `woo-mod-swatches-dis-style-{1..3}.min.css` | Per-attribute, based on `swatch_dis_style`. |
| `woo-mod-swatches-filter.min.css` | Swatches rendered inside a WP Grid Builder attribute **filter** widget (not covered elsewhere in this research — a separate, adjacent swatch UI for shop filtering, using class `wd-swatches-filter`). |
| `woo-opt-limit-swatches.min.css` | When "Limit swatches" is active for a given attribute list. |

## Structural classes (from `woo-mod-swatches-base.min.css`)

- `.wd-swatch` — the individual swatch button. `display:inline-flex`,
  square-ish sizing driven entirely by CSS custom properties.
- `.wd-swatch-bg` — inner wrapper for color/image content (`background-color`
  inline style **or** an `<img>`/`<picture>`).
- `.wd-swatch-text` — the term-name label; hidden (`display:none`) whenever
  `.wd-bg` is present (i.e. color/image swatches show no text at all —
  text is purely for `.wd-text`-only swatches or exposed via `title=`
  tooltip / `.wd-tooltip`).
- `.wd-bg` vs `.wd-text` — mutually exclusive modifiers picking which of
  the two above rendering modes is active for this specific swatch.
- `[class*="wd-swatches"]` (matches `wd-swatches-grid`, `wd-swatches-product`,
  `wd-swatches-single`, `wd-swatches-filter`, `wd-swatches-attr`) — the
  **wrapper** div; defines the CSS custom properties consumed by children:
  ```css
  --wd-swatch-size: 25px;      /* base glyph/text size, size-variant-dependent */
  --wd-swatch-w: 1em;
  --wd-swatch-h: 1em;
  --wd-swatch-text-size: 16px;
  --wd-swatch-h-sp: 15px;      /* horizontal gap between swatches */
  --wd-swatch-v-sp: 10px;      /* vertical gap (wrapping rows) */
  --wd-swatch-inn-sp: .001px;  /* inner padding, used by style 2/3 border variants */
  --wd-swatch-brd-color: var(--brdcolor-gray-200);
  ```
- **Size variants** (`.wd-size-{xs|default|m|large|xlarge|xxl}`) override
  `--wd-swatch-size`/`--wd-swatch-text-size`. Grid swatches
  (`.wd-swatches-grid`) use **smaller absolute values** than single-product
  swatches for the same named size (e.g. `default`/`xs` → `15px` on the
  grid vs. `25px` base on single product) — a deliberate density difference
  worth preserving in a rebuild's own size scale.
- **Shape variants**: `.wd-shape-round` → `border-radius:1em` (fully round);
  `.wd-shape-rounded` → `5px`; `.wd-shape-square` → (no radius override,
  i.e. sharp corners — default browser/box behavior).
- Dark-mode border fix: on dark color scheme, a **white or unset-background**
  color swatch gets a synthetic 1px border injected via `:before` so it
  doesn't disappear against a dark background.

## The 4 "active/enabled" swatch styles (`wd-bg-style-N` / `wd-text-style-N`)

Applied to the **wrapper**, not the individual swatch, and affect both
color-swatches (`.wd-bg` selector) and text-only swatches
(`.wd-text` selector) via matching `wd-text-style-N` classes added
alongside `wd-bg-style-N` (both are always added together — see
`swatches.php` / `variable.php`: `$wrapper_class .= ' wd-bg-style-' .
$swatch_style; $wrapper_class .= ' wd-text-style-' . $swatch_style;`).

| Style | Selected/hover appearance |
|---|---|
| **1** (default) | A `2px` bottom underline (`:after` pseudo-element), animated in via `opacity`. On hover *or* `.wd-active`, opacity → 1. |
| **2** | A `1px` inset `box-shadow` "ring" around the swatch that darkens to `--color-gray-900` on hover/active (and thickens to `2px` for the *active* state specifically). No background border on the color circle itself (`.wd-swatch-bg:before{border:none}` overrides the base dark-mode border-fix). |
| **3** | A plain `1px solid` border around the swatch, color shifting to `--color-gray-500` on hover/active. |
| **4** | Color swatches: a semi-transparent black overlay with a centered chevron-down glyph (icon font codepoint `\f107`, `woodmart-font`) fading in on hover/active. Text swatches: filled `background-color:--color-gray-900` + white text on hover/active (like a solid button, not just a border). |

## The 3 "disabled" swatch styles (`wd-dis-style-N`)

Applied when a swatch has class `.wd-disabled` (out-of-stock / invalid
combination given other selected attributes):

| Style | Appearance |
|---|---|
| **1** (default) | Simply `opacity:0.4` + `cursor:default`. No visual "strike-through" — just dimmed. |
| **2** | `opacity:0.7` **plus** a red (`#CF000F`) diagonal "X" (two crossing `linear-gradient`s) drawn via a `:before` overlay. |
| **3** | `opacity:0.4` plus a single **gray** diagonal line (`var(--brdcolor-gray-500)`), only from top-left to bottom-right (one gradient, not a full X). |

## Other size-scale/gap notes for the rebuild

- Swatch groups **wrap** (`flex-wrap:wrap`) with independent horizontal/
  vertical gaps, `justify-content` driven by a `--text-align` variable
  (presumably wired to whatever text-alignment context surrounds the
  swatches, e.g. centered product cards vs. left-aligned single-product
  table cells).
- `.wd-swatches-product + select { display:none }` is the single rule that
  hides WooCommerce's native `<select>` once swatches take over — a
  rebuild must replicate this exact "adjacent sibling, hide the real
  select" relationship (swatches div immediately followed by the select in
  markup order) or write an equivalent hiding rule.
- Swatch images/color-fills clip to the shape via `border-radius:inherit`
  on the inner `<img>`/`<picture>` and `.wd-swatch-bg`.
