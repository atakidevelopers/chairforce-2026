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

### 1. Strip `metadata` from every block

The editor adds `"metadata":{...}` to block JSON attrs. **Always remove it.**

```
<!-- wp:group {"metadata":{"categories":["chairforce"],"name":"..."},"align":"wide",...} -->
→
<!-- wp:group {"align":"wide",...} -->
```

### 2. Images — use theme assets, never upload URLs

Never leave `https://chairforce-2026.test/wp-content/uploads/...` in a pattern.

Available placeholder images in `assets/images/`:
- `placeholder.png` (square-ish)
- `placeholder-3x4.png` (portrait)
- `placeholder-4x3.png` (landscape)
- `placeholder-16x9.png` (wide)

Use `get_theme_file_uri()` in the img src:

```php
<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder.png' ) ); ?>" alt="placeholder" class="size-full"/>
```

For `wp:media-text` blocks, also strip the `mediaId` and `mediaLink` attrs from the block comment (they are site-specific attachment IDs).

### 3. Placeholder text standard

| Element | Placeholder |
|---------|-------------|
| Heading | `Change me please` |
| Paragraph / description | `Change this please as well.` |
| Eyebrow | `Change me please` |
| Button label | `Change Me` |

Use real content only when the pattern is demonstrating a specific structural concept (e.g. a numbered steps pattern with "1. Apply & Get Approved").

### 4. Dead class cleanup

Remove `is-icon-style-*` from any `className` attrs or rendered `<div class="...">`. This was a removed `iconStyle` attribute from the `chairforce/info-box` block.

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

- [ ] `metadata` removed from all block JSON attrs
- [ ] No `https://chairforce-2026.test/wp-content/uploads/` URLs
- [ ] No `is-icon-style-*` dead classes
- [ ] `mediaId` / `mediaLink` removed from `wp:media-text` attrs
- [ ] `wp-image-{ID}` class removed from `<img>` tags
- [ ] Icon blocks have SVG inline (not self-closing)
- [ ] Locking present on icon block and content group inside info-box
