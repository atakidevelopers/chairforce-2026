# Chairforce Theme Color Mapping

Source: `Section - 01 COLOUR.pdf` (Figma export)  
Rule applied: keep existing `theme.json` palette slugs; no new slugs added.  
Rule applied: ignore **Product finish swatches**.

## Linked Colors (Figma -> theme.json)

| Figma Color Name | Figma Token | Hex | theme.json Slug | theme.json Name |
|---|---|---|---|---|
| Navy | `--cf-navy` | `#053B7A` | `primary` | Primary |
| Red | `--cf-red` | `#B3001B` | `secondary` | Secondary |
| Ink Strong | `--cf-ink-2` | `#242424` | `heading` | Heading |
| Ink (Nordic) | `--nd-ink` | `#16181B` | `body` | Body |
| Grey 1 | `--nd-grey` | `#F5F6F7` | `background` | Background |
| Green | `--nd-green` | `#2E7D52` | `tertiary` | Tertiary |
| Stone | `--cf-stone` | `#E5E2D9` | `quaternary` | Quaternary |
| Surface Alt | `--cf-surface-2` | `#F7F7F7` | `surface` | Surface |
| Star Gold | (none in PDF) | `#E8A400` | `foreground` | Foreground |
| Nordic Muted | `--nd-muted` | `#5E6166` | `neutral` | Neutral |
| White | `--cf-white` | `#FFFFFF` | `white` | White |
| Black | `--cf-black` | `#000000` | `black` | Black |
| Line 2 | `--nd-line-2` | `#D6D9DD` | `outline` | Outline |

Notes:
- `outline` now matches `--nd-line-2` exactly (`#D6D9DD`).
- `transparent`, `currentColor`, and `inherit` remain utility values in `theme.json`.

## Colors Not Yet Linked to theme.json

| Figma Color Name | Figma Token | Hex |
|---|---|---|
| Ink | `--cf-ink` | `#333333` |
| Off-white | `--cf-bg` | `#FAFAFA` |
| Mist | `--cf-mist` | `#E7EFF1` |
| Muted | `--cf-muted` | `#767676` |
| Sand | `--cf-sand` | `#F1EEE7` |
| Taupe | `--cf-taupe` | `#B6A79C` |
| Grey 2 | `--nd-grey-2` | `#ECEEF0` |
| Grey 3 | `--nd-grey-3` | `#E2E5E8` |
| Previous Outline (pending review) | (former `outline`) | `#E3E5E8` |

