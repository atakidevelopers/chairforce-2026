# Chairforce WooCommerce Meta Keys

WC-related storage contracts. **Same keys and formats** — normalize on save only.

Full ground truth: `context/existing-functionality/02-data-model-and-storage.md`

## Product post meta

| Meta key | Format | Admin UI |
|----------|--------|----------|
| `parts` | Serialized array of product ID strings | `WooCommerce_Admin` |
| `dimensions` | WYSIWYG HTML | ACF `group_product_additional_information` |
| `care` | WYSIWYG HTML | ACF |
| `additional_information` | WYSIWYG HTML | ACF |
| `_woodmart_swatches_attribute` | Taxonomy slug | No UI; cheap read fallback only |

## Variation post meta

| Meta key | Format | Admin UI |
|----------|--------|----------|
| `wd_additional_variation_images_data` | CSV attachment IDs (no spaces) | `WooCommerce_Admin` |

POST key: `wd_additional_variation_images[{variation_id}]`.

**Not on this site:** `woodmart_variation_gallery_data` on parent product (old method).

## Term meta (swatches — Phase 3 frontend)

On `pa_*` terms. Data persists after Woodmart removal; only `pa_colour` has populated swatch data on this site.

| Meta key | Format |
|----------|--------|
| `color` | CSS color, often `rgb(r,g,b)` |
| `image` | Array with `id` or URL string |
| `not_dropdown` | `'1'` or empty |
| `pa_term_hint` | Text/HTML |

## Woodmart options → rebuild (decision only)

Do not call Woodmart option APIs. Hardcode equivalents:

| Legacy | Rebuild |
|--------|---------|
| `grid_swatches_attribute` | `pa_colour` |
| `woodmart_pa_colour_swatch_*` | style=3, dis_style=3, shape=round, size=m |

Detail: `context/existing-functionality/02-data-model-and-storage.md` §2–3.

## Verification commands

```bash
ddev wp post meta get 1000438 wd_additional_variation_images_data
ddev wp post meta get <product_id> parts --format=json
```
