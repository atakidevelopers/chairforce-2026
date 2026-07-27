# Chairforce Typography Token Mapping (Figma -> WordPress)

Source files reviewed:
- `context/figma/Chairforce Tokens/Desktop.tokens.json`
- `context/figma/Chairforce Tokens/Mobile.tokens.json`

## Assessment

Figma token names are semantic to component usage (`h3-card`, `price`, `label-nav`, `stat-number`) rather than WordPress preset naming (`x-small`..`xxxx-large`).  
That is fine, but in WordPress we should:

- Keep a **small global preset scale** in `theme.json` (`x-small` to `xxxx-large`)
- Map **special-purpose tokens** (price/stat/meta/badge) at block/component level, not as global presets

## Fluid Typography Strategy (Recommended)

WordPress `theme.json` supports fluid typography natively per font-size preset.  
Recommendation: use Figma Mobile value as `fluid.min`, Desktop value as `fluid.max`, and keep `size` as a normal size token (usually desktop/base).  
Do not manually hardcode `clamp(...)` in `size` when using WordPress fluid typography.

Also enable fluid typography globally:

```json
"settings": {
  "typography": {
    "fluid": true
  }
}
```

## Recommended Global Font Size Table (`theme.json` presets)

| `theme.json` slug | Suggested use | Desktop (Max) | Mobile (Min) | `fluid.max` | `fluid.min` | Recommended `size` |
|---|---|---:|---:|---:|---:|---|
| `x-small` | meta, badge, tiny helper text | 11 | 10 | 11px | 10px | `11px` |
| `small` | small body, eyebrow, nav label | 13 | 12 | 13px | 12px | `13px` |
| `medium` | default body copy | 15 | 15 | 15px | 15px | `15px` |
| `large` | lead / intro paragraph | 17 | 16 | 17px | 16px | `17px` |
| `x-large` | h4/h5/subheader | 19 | 16 | 19px | 16px | `19px` |
| `xx-large` | h3-card / section subheading | 22 | 19 | 22px | 19px | `22px` |
| `xxx-large` | h2-section / section heading | 38 | 26 | 38px | 26px | `38px` |
| `xxxx-large` | display / hero headline | 50 | 32 | 50px | 32px | `50px` |

## Figma Tokens Best Fit (and theme.json linkage)

| Figma token | Desktop (Max) | Mobile (Min) | Link in `theme.json` | `fluid.max` | `fluid.min` | Notes |
|---|---:|---:|---|---:|---:|---|
| `display` | 50 | 32 | `font-size: xxxx-large` | 50px | 32px | Global preset (hero-capable) |
| `h1` | 42 | 30 | component-controlled `h1` typography | 42px | 30px | Keep `h1` at component/template level for exact match |
| `h2-section` | 38 | 26 | `font-size: xxx-large` | 38px | 26px | Exact match |
| `h3-card` | 22 | 19 | `font-size: xx-large` | 22px | 19px | Exact match |
| `h4` | 16 | 16 | `font-size: large` or `x-large` | 17/19px | 16px | Prefer `x-large` for heading hierarchy |
| `h5` | 16 | 16 | `font-size: large` or `x-large` | 17/19px | 16px | Same as `h4` |
| `lead` | 17 | 16 | `font-size: large` | 17px | 16px | Exact match |
| `body` | 15 | 15 | `font-size: medium` | 15px | 15px | Exact match |
| `Small Heading` | 15 | 14 | `font-size: medium` | 15px | 15px | Optional component override for 14 min |
| `label-nav` | 13 | 13 | `font-size: small` | 13px | 12px | Slightly larger mobile min in Figma; override if needed |
| `small` | 12 | 12 | `font-size: small` | 13px | 12px | Lower edge of `small` |
| `badge` | 11 | 10 | `font-size: x-small` | 11px | 10px | Exact match |
| `subheader` | 19 | 16 | `font-size: x-large` | 19px | 16px | Exact match |
| `button` | 15 | 15 | component style (`font-size: medium`) | 15px | 15px | Keep component-controlled |
| `eyebrow` | 12 | 11 | `font-size: small` or `x-small` | 13/11px | 12/10px | Choose per component density |
| `section` | 32 | 26 | `font-size: xxx-large` | 38px | 26px | Use component override if strict 32 max needed |
| `price` | 24 | 20 | component style (no global slug) | 24px | 20px | Keep role-specific |
| `stat` | 54 | 32 | component style (no global slug) | 54px | 32px | Keep role-specific |
| `stat-number` | 34 | 24 | component style (no global slug) | 34px | 24px | Keep role-specific |
| `caption` | 13.5 | 13 | `font-size: small` | 13px | 12px | Use component override if 13.5 is required |
| `meta` | 10.5 | 10.5 | `font-size: x-small` | 11px | 10px | Use component override if strict 10.5 required |

## Tokens that should NOT be forced into global presets

These are better controlled in block/component styles (`styles.blocks` or SCSS classes), because they are role-specific and can distort a global scale:

- `price`
- `stat`
- `stat-number`
- `button` (if visual style differs by block context)
- possibly `display` (if only used in hero variants)

## Spacing Policy (Recommended)

- Keep core spacing presets tokenized and mostly fixed for consistency.
- Use `clamp()` only on major layout wrappers (hero/section paddings), not every spacing token.
- This keeps rhythm predictable while still improving responsiveness.

## Practical next step

1. Enable `settings.typography.fluid` in `theme.json` (if not already enabled).
2. Update `settings.typography.fontSizes` to match the table above (`fluid.min/max` + `size`).
3. Keep heading/body assignment via existing typography rules.
4. Apply `price/stat/meta` token sizes in block-specific styles instead of adding extra global slugs.

