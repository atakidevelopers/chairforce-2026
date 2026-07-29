/**
 * Curated list of Lucide icons available in the Button block's icon
 * picker (see src/js-admin/button-icons.js). Intentionally a curated
 * subset - not the full Lucide library (~2,000 icons) - to match the
 * project's Figma design system.
 *
 * Rendering itself is handled purely via CSS - see
 * assets/css/button-icon-font.css (enqueued globally by
 * Chairforce\Lucide_Icons in lib/class-lucide-icons.php) - so this file
 * only needs to expose the slug/label list, not any icon components.
 *
 * To add an icon: add `{ slug, label }` here, then add the matching
 * `.cf-icon-{slug}` rule to assets/css/button-icon-font.css (see that
 * file's header comment for how to look up its codepoint).
 */
export const CHAIRFORCE_LUCIDE_ICON_OPTIONS = [
	{ slug: 'search', label: 'Search' },
	{ slug: 'shopping-cart', label: 'Shopping Cart' },
	{ slug: 'file-text', label: 'File Text' },
	{ slug: 'user', label: 'User' },
	{ slug: 'map-pin', label: 'Map Pin' },
	{ slug: 'phone', label: 'Phone' },
	{ slug: 'heart', label: 'Heart' },
	{ slug: 'plus', label: 'Plus' },
	{ slug: 'minus', label: 'Minus' },
	{ slug: 'trash-2', label: 'Trash 2' },
	{ slug: 'chevron-left', label: 'Chevron Left' },
	{ slug: 'chevron-right', label: 'Chevron Right' },
	{ slug: 'chevron-down', label: 'Chevron Down' },
	{ slug: 'arrow-right', label: 'Arrow Right' },
	{ slug: 'x', label: 'X' },
	{ slug: 'menu', label: 'Menu' },
	{ slug: 'truck', label: 'Truck' },
	{ slug: 'zap', label: 'Zap' },
	{ slug: 'shield-check', label: 'Shield Check' },
	{ slug: 'smile', label: 'Smile' },
	{ slug: 'package', label: 'Package' },
	{ slug: 'star', label: 'Star' },
	{ slug: 'clock', label: 'Clock' },
	{ slug: 'mail', label: 'Mail' },
	{ slug: 'check', label: 'Check' },
	{ slug: 'check-circle', label: 'Check Circle' },
	{ slug: 'tag', label: 'Tag' },
	{ slug: 'filter', label: 'Filter' },
	{ slug: 'sliders-horizontal', label: 'Sliders Horizontal' },
	{ slug: 'grid-2x2', label: 'Grid' },
	{ slug: 'eye', label: 'Eye' }
];
