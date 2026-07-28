---
name: chairforce-woocommerce
description: Implements and extends WooCommerce integration in the Chairforce theme rebuild — ACF vs native WC admin UI decisions, legacy meta key preservation, WooCommerce_Admin hook patterns, and wp-admin verification. Use when working on WooCommerce products, variations, shop/archive, single product, Parts field, variation gallery, swatches, product meta, or lib/class-woocommerce*.php.
---

# Chairforce WooCommerce Integration

Project-specific WooCommerce guidance for `wp-content/themes/chairforce/`.

**Authority order:** `.cursor/rules/` → this skill → `context/existing-functionality/` → generic WordPress/WooCommerce skills.

When this skill and `.cursor/rules/` overlap, **rules win**. This skill adds WC-specific decisions rules do not cover.

## Architecture (WC only)

```
lib/class-woocommerce.php       # Entry — guards on class_exists('WooCommerce')
├── WooCommerce_Admin           # wp-admin product + variation UI
├── WooCommerce_Archive         # Shop/archive stub
└── WooCommerce_Single_Product  # Single product stub

woocommerce/                    # Template overrides ONLY
assets/js|css/                  # Classic wp-admin WC assets (not block editor build)
src/sass/woocommerce/           # Frontend WC styles → build/public.css
```

Registered from `lib/class-init.php` → `define_woocommerce_hooks()`. Class patterns: `.cursor/rules/07-php-architecture.mdc`.

## Core decision: ACF vs native WC hooks

| Situation | Approach |
|-----------|----------|
| Field on **`product`** with standard edit screen | ACF JSON in `acf-json/` |
| Field on **`product_variation`** or Variations AJAX panel | **Native WC hooks** in `WooCommerce_Admin` |
| Product meta in Product data tabs (Linked products, etc.) | Native WC hooks + WC UI (`wc-product-search`, etc.) |
| Legacy JetEngine/Woodmart storage format | **Same meta key**; normalize on save only — no migration unless requested |

### Critical: `product_variation` has no edit screen

`show_ui: false` — variations edit inside the parent product **Variations** tab.

**Bridge pattern (required):**
- Render: `woocommerce_variation_options`
- Save: `woocommerce_save_product_variation`
- Admin JS: re-init on `woocommerce_variations_loaded`

Do **not** use ACF location `post_type == product_variation`.

## Reference implementations

Read `lib/class-woocommerce-admin.php` as the canonical pattern. Summary:

| Feature | Meta key | Render | Save |
|---------|----------|--------|------|
| Parts | `parts` | `woocommerce_product_options_related` | `woocommerce_admin_process_product_object` |
| Variation gallery | `wd_additional_variation_images_data` | `woocommerce_variation_options` | `woocommerce_save_product_variation` |

Variation gallery extras:
- POST: `wd_additional_variation_images[{variation_id}]`
- Admin assets: `assets/js/variation-gallery-admin.js`, `assets/css/variation-gallery-admin.css`
- **Save safety:** if POST key absent, do not delete existing meta

Storage formats: [meta-keys.md](meta-keys.md).

## Adding a new WC field

1. ACF vs native (table above).
2. Place in `WooCommerce_Admin` (admin) or the appropriate stub class (frontend).
3. Private const for legacy meta key — never rename without approved migration.
4. Match legacy format ([meta-keys.md](meta-keys.md)).
5. Correct render + save hooks for that screen.
6. `current_user_can( 'edit_post', $id )`; sanitize/escape.
7. Admin assets: `admin_enqueue_scripts` on product edit screens only.
8. Universal verification (below).

**Admin assets:** WC product screens do not load `build/index.css`. Small wp-admin UI may use static `assets/js|css/` + `filemtime()` enqueue. Frontend shop styles → `src/sass/woocommerce/`.

## Phase 3 / not yet implemented

`WooCommerce_Archive` and `WooCommerce_Single_Product` are stubs — do not add admin UI there.

Frontend work (swatches, variation gallery JSON, Quick View): implement in those classes per `context/existing-functionality/02-data-model-and-storage.md` and related files. Do not duplicate specs here.

## Universal verification rule

Every admin field change:

1. **CLI:** read meta before/after on a record with known data.
2. **wp-admin:** open that record; confirm display, edit, save; sibling meta unchanged.

Sample variation gallery record: ID **1000438**. Commands: [meta-keys.md](meta-keys.md#verification-commands).

## Anti-patterns

- ❌ ACF on `product_variation` expecting wp-admin visibility
- ❌ Renaming legacy WC meta keys
- ❌ Hooks/classes in `woocommerce/` directory
- ❌ Standalone functions outside `Chairforce\` classes
- ❌ Bulk data migration for schema-only work
- ❌ Deleting variation gallery meta when POST key is entirely absent
- ❌ `woocommerce-backend-dev` conventions (WC plugin core — wrong project)

## Further reading

| Resource | Use for |
|----------|---------|
| [meta-keys.md](meta-keys.md) | WC product/variation/term meta + Woodmart hardcode summary |
| `context/existing-functionality/02-data-model-and-storage.md` | Full swatch/gallery data model |
| `context/plans/registration-and-acf-schema-plan.md` | Registration plan |
| `.cursor/rules/10-acf-integration.mdc` | ACF JSON conventions |
| `.cursor/rules/16-icon-system.mdc` | Frontend WC button icons (`cf-icon-button`) |
