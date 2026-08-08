---
name: chairforce-patterns
description: Create or fix WordPress block pattern PHP files for the Chairforce FSE theme. Use when asked to create a new pattern, update an existing pattern PHP file, extract patterns from a test page, or clean up editor-generated markup in a pattern file. Triggers on: block pattern, patterns/, info-box pattern, section pattern, FSE pattern, fix pattern.
---

# Chairforce Block Patterns

## Pattern file location & PHP header

All patterns live in `wp-content/themes/chairforce/patterns/{slug}.php`.

```php
<?php
/**
 * Title: Human-readable name
 * Slug: chairforce/slug-here
 * Description: One sentence describing the pattern and its style.
 * Categories: chairforce, section
 * Keywords: keyword1, keyword2, ...
 */
?>
<!-- block markup -->
```

**Available categories**: `chairforce`, `section`, `elements`, `hero`, `banner`, `content`

---

## Rules before writing any pattern

### 1. `metadata` — root block vs inner blocks

- **Root block**: keep `metadata.name`. Add `metadata.patternName` matching the PHP `Slug:`. Strip `metadata.categories`.
- **All inner blocks**: strip `metadata` entirely — including any stale `patternName` left from when a sub-pattern was inserted.

```
<!-- wp:group {"tagName":"section","metadata":{"name":"Split — Features","patternName":"chairforce/section-split-features"},...} -->  ← root
<!-- wp:group {"metadata":{"categories":["chairforce"],"patternName":"chairforce/info-box-bar-navy","name":"Info Box — Bar"},...} -->  ← inner → strip entirely
```

### 2. Root group — `tagName:"section"` for section patterns

Section-level patterns must use `"tagName":"section"` on the root `core/group` and a matching `<section>` closing tag. Do **not** leave the default `<div>`.

```
<!-- wp:group {"tagName":"section","metadata":{...},"align":"full",...} -->
<section class="wp-block-group ...">...</section>
<!-- /wp:group -->
```

### 3. Images — use theme assets, never upload URLs

Never leave `https://chairforce-2026.test/wp-content/uploads/...` in a pattern.

Available placeholder images in `assets/images/`:
- `placeholder.png` (square-ish)
- `placeholder-3x4.png` (portrait)
- `placeholder-4x3.png` (landscape)
- `placeholder-16x9.png` (wide)

```php
<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder.png' ) ); ?>" alt="placeholder"/>
```

Also remove `wp-image-{ID}` class and `class="size-full"` from pattern placeholder `<img>` tags.

### 4. `wp:media-text` — keep `mediaId:0`, strip real IDs

```
<!-- wp:media-text {"align":"wide","mediaPosition":"right","mediaId":0,"mediaLink":"","linkDestination":"none","mediaType":"image","imageFill":false} -->
```

### 5. Reuse existing patterns with `wp:pattern`

When a section contains an info-box grid or any other named sub-pattern, reference it — never duplicate markup inline:

```
<!-- wp:pattern {"slug":"chairforce/info-box-bar-navy"} /-->
```

Check `patterns/` for existing slugs before inlining block markup.

### 6. Placeholder text standard

| Element | Placeholder |
|---------|-------------|
| Heading | `Change me please` |
| Paragraph / description | `Change this please as well.` |
| Eyebrow | `Change me please` |
| Button label | `Change Me` |

Section/wrapper patterns may use real sample content to communicate their purpose.

### 7. Dead class cleanup

Remove `is-icon-style-*` from any `className` attrs or rendered `<div class="...">`. This was a removed attribute from `chairforce/info-box`.

---

## `chairforce/stat-counter` in patterns

| Counter display | `number` | `suffix` | Notes |
|-----------------|----------|----------|-------|
| `50,000+` | `50000` | `+` (default) | comma-formatted |
| `15k+` | `15` | `k+` | "k" goes in suffix, not in number |
| `98%` | `98` | `%` | |
| `7` (no plus) | `7` | `""` | **must** set `"suffix":""` to override the default `+` |
| `2,500+` | `2500` | `+` (default) | comma-formatted |

Default suffix is `+`. Set `"suffix":""` explicitly whenever the number should stand alone.

---

## Info-box locking technique (canonical)

When a `chairforce/info-box` contains an `outermost/icon-block` and a `core/group`:

```
<!-- wp:outermost/icon-block {"iconName":"...","hasNoIconFill":true,"lock":{"remove":true,"move":true},"className":"is-style-style-N"} -->
...SVG inline...
<!-- /wp:outermost/icon-block -->

<!-- wp:group {"lock":{"remove":true},...} -->
...heading + paragraph...
<!-- /wp:group -->
```

- **Icon block**: locked for removal AND movement
- **Content group**: locked for removal only (user can edit content freely)
- Always include the SVG inline (not a self-closing empty icon block)

---

## Extracting patterns from the test page

The test page (post 1514776) holds live-edited patterns separated by `h3` headings and `<!-- wp:separator -->`.

```bash
ddev wp post get 1514776 --field=post_content
```

When extracting a pattern from this content:
1. Find the block between its `h3` label and the next separator
2. Strip the outer `h3` heading and separator — they are navigation markers, not part of the pattern
3. Apply all cleanup rules above before writing the PHP file

---

## Fix checklist for existing pattern files

- [ ] PHP header present: `Title`, `Slug`, `Categories`, `Description`
- [ ] Root block: `tagName:"section"`, `metadata.name` kept, `metadata.patternName` added, `metadata.categories` stripped
- [ ] All inner blocks: `metadata` stripped entirely (including stale `patternName`)
- [ ] No `https://chairforce-2026.test/wp-content/uploads/` URLs
- [ ] No `is-icon-style-*` dead classes
- [ ] `wp:media-text`: `mediaId:0`, `mediaLink:""`
- [ ] No `wp-image-{ID}` or `class="size-full"` on `<img>` tags
- [ ] Existing sub-patterns referenced via `wp:pattern` slug, not inlined
- [ ] Icon blocks have SVG inline (not self-closing)
- [ ] Locking present on icon block and content group inside info-box
- [ ] `stat-counter` blocks: `"suffix":""` set explicitly for numbers with no suffix
