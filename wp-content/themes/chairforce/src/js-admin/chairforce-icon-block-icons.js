/**
 * SVG icon data for the "ChairForce" icon type registered with the Icon
 * Block plugin (outermost/icon-block) - see register-custom-icons.js.
 *
 * This is the *same* curated icon set as CHAIRFORCE_LUCIDE_ICON_OPTIONS
 * (see lucide-icon-options.js, used by the Button block's icon picker),
 * just in the shape the Icon Block plugin's `iconBlock.icons` filter
 * expects (`name`/`title`/`icon`/`categories`/`hasNoIconFill`).
 *
 * Each `icon` value is the raw SVG markup from the `lucide-static` package
 * (node_modules/lucide-static/icons/{slug}.svg), with the root <svg>'s
 * `width`, `height` and `class` attributes stripped - the Icon Block
 * plugin controls sizing itself, and the class attribute is irrelevant
 * once the markup is inlined into block content.
 *
 * `hasNoIconFill: true` is required on every icon here because Lucide
 * icons are stroke-based (`stroke="currentColor"`, `fill="none"`), unlike
 * the plugin's own default icon set, which is fill-based. Without this
 * flag the plugin's color control would try to apply a fill color that
 * fights with `fill="none"`.
 *
 * To add/remove an icon: update CHAIRFORCE_LUCIDE_ICON_OPTIONS in
 * lucide-icon-options.js first (single source of truth for slugs/labels),
 * then add/remove the matching entry below, copying the SVG body from
 * node_modules/lucide-static/icons/{slug}.svg.
 */

const { __ } = wp.i18n;

export const CHAIRFORCE_ICON_BLOCK_ICONS = [
	{
		name: 'search',
		title: __( 'Search', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'shopping-cart',
		title: __( 'Shopping Cart', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" /><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'file-text',
		title: __( 'File Text', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" /><path d="M14 2v5a1 1 0 0 0 1 1h5" /><path d="M10 9H8" /><path d="M16 13H8" /><path d="M16 17H8" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'user',
		title: __( 'User', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'map-pin',
		title: __( 'Map Pin', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /><circle cx="12" cy="10" r="3" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'phone',
		title: __( 'Phone', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'heart',
		title: __( 'Heart', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'plus',
		title: __( 'Plus', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /><path d="M12 5v14" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'minus',
		title: __( 'Minus', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'trash-2',
		title: __( 'Trash 2', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6" /><path d="M14 11v6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'chevron-left',
		title: __( 'Chevron Left', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'chevron-right',
		title: __( 'Chevron Right', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'chevron-down',
		title: __( 'Chevron Down', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'arrow-right',
		title: __( 'Arrow Right', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'x',
		title: __( 'X', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'menu',
		title: __( 'Menu', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16" /><path d="M4 12h16" /><path d="M4 19h16" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'truck',
		title: __( 'Truck', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'zap',
		title: __( 'Zap', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'shield-check',
		title: __( 'Shield Check', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /><path d="m9 12 2 2 4-4" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'smile',
		title: __( 'Smile', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 14s1.5 2 4 2 4-2 4-2" /><line x1="9" x2="9.01" y1="9" y2="9" /><line x1="15" x2="15.01" y1="9" y2="9" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'package',
		title: __( 'Package', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" /><path d="M12 22V12" /><polyline points="3.29 7 12 12 20.71 7" /><path d="m7.5 4.27 9 5.15" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'star',
		title: __( 'Star', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'clock',
		title: __( 'Clock', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'mail',
		title: __( 'Mail', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" /><rect x="2" y="4" width="20" height="16" rx="2" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'check',
		title: __( 'Check', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'check-circle',
		title: __( 'Check Circle', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335" /><path d="m9 11 3 3L22 4" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'badge-check',
		title: __( 'Badge Check', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><path d="m9 12 2 2 4-4" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'tag',
		title: __( 'Tag', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" /><circle cx="7.5" cy="7.5" r=".5" fill="currentColor" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'filter',
		title: __( 'Filter', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'sliders-horizontal',
		title: __( 'Sliders Horizontal', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5H3" /><path d="M12 19H3" /><path d="M14 3v4" /><path d="M16 17v4" /><path d="M21 12h-9" /><path d="M21 19h-5" /><path d="M21 5h-7" /><path d="M8 10v4" /><path d="M8 12H3" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	},
	{
		name: 'grid-2x2',
		title: __( 'Grid', 'chairforce' ),
		icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18" /><path d="M3 12h18" /><rect x="3" y="3" width="18" height="18" rx="2" /></svg>',
		categories: [ 'chairforce' ],
		hasNoIconFill: true
	}
];
