import { __ } from '@wordpress/i18n';

export const ICON_POSITIONS = [ 'left', 'right', 'top' ];
export const ICON_STYLES    = [ 'plain', 'light-box', 'dark-box' ];
export const ALIGNMENTS     = [ 'start', 'center', 'end' ];

export const DEFAULT_ICON_POSITION = 'left';
export const DEFAULT_ICON_STYLE    = 'plain';
export const DEFAULT_ALIGNMENT     = 'center';

export const ICON_STYLE_OPTIONS = [
	{ label: __( 'Plain',      'chairforce' ), value: 'plain'     },
	{ label: __( 'Light box',  'chairforce' ), value: 'light-box' },
	{ label: __( 'Dark box',   'chairforce' ), value: 'dark-box'  },
];

export const ALLOWED_BLOCKS = [ 'outermost/icon-block', 'core/group' ];

export const INNER_BLOCKS_TEMPLATE = [
	[
		'outermost/icon-block',
		{
			hasNoIconFill: true,
			lock: { remove: true, move: true },
		},
	],
	[
		'core/group',
		{
			lock: { remove: true },
			style: { spacing: { blockGap: 'var:preset|spacing|x-small' } },
		},
		[
			[
				'core/heading',
				{
					level: 4,
					content: __( 'Add heading', 'chairforce' ),
				},
			],
			[
				'core/paragraph',
				{
					content: __( 'Add a short description.', 'chairforce' ),
				},
			],
		],
	],
];

export function sanitizeIconPosition( value ) {
	return ICON_POSITIONS.includes( value ) ? value : DEFAULT_ICON_POSITION;
}

export function sanitizeIconStyle( value ) {
	return ICON_STYLES.includes( value ) ? value : DEFAULT_ICON_STYLE;
}

export function sanitizeAlignment( value ) {
	return ALIGNMENTS.includes( value ) ? value : DEFAULT_ALIGNMENT;
}

/**
 * Return the layout object that corresponds to an icon position.
 *
 * @param {'left'|'right'|'top'} iconPosition
 * @return {{ type: 'flex', orientation: 'horizontal'|'vertical' }}
 */
export function getLayoutFromIconPosition( iconPosition ) {
	return {
		type: 'flex',
		orientation: iconPosition === 'top' ? 'vertical' : 'horizontal',
	};
}

/**
 * Alignment options differ depending on whether the layout is horizontal
 * (cross-axis = vertical) or vertical (cross-axis = horizontal).
 *
 * @param {'left'|'right'|'top'} iconPosition
 */
export function getAlignmentOptions( iconPosition ) {
	if ( iconPosition === 'top' ) {
		return [
			{ label: __( 'Left',   'chairforce' ), value: 'start'  },
			{ label: __( 'Center', 'chairforce' ), value: 'center' },
			{ label: __( 'Right',  'chairforce' ), value: 'end'    },
		];
	}
	return [
		{ label: __( 'Top',    'chairforce' ), value: 'start'  },
		{ label: __( 'Middle', 'chairforce' ), value: 'center' },
		{ label: __( 'Bottom', 'chairforce' ), value: 'end'    },
	];
}

/**
 * Build the CSS modifier classes for the root block wrapper.
 *
 * @param {{ iconPosition: string, iconStyle: string, alignment: string }} attrs
 * @return {string}
 */
export function buildModifierClasses( { iconPosition, iconStyle, alignment } ) {
	return [
		`is-icon-${ sanitizeIconPosition( iconPosition ) }`,
		`is-icon-style-${ sanitizeIconStyle( iconStyle ) }`,
		`is-align-${ sanitizeAlignment( alignment ) }`,
	].join( ' ' );
}
