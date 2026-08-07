import { __ } from '@wordpress/i18n';

export const ICON_POSITIONS = ['left', 'right', 'top'];

export const ALIGNMENTS = ['start', 'center', 'end'];

export const DEFAULT_ICON_POSITION = 'left';
export const DEFAULT_ALIGNMENT = 'center';

export const ALLOWED_BLOCKS = ['outermost/icon-block', 'core/group'];

export const INNER_BLOCKS_TEMPLATE = [
	[
		'outermost/icon-block',
		{
			hasNoIconFill: true,
			lock: {
				remove: true,
				move: true,
			},
		},
	],
	[
		'core/group',
		{
			lock: {
				remove: true,
			},
		},
		[
			[
				'core/heading',
				{
					level: 3,
					content: __('Add feature heading', 'chairforce'),
				},
			],
			[
				'core/paragraph',
				{
					content: __('Add a short description.', 'chairforce'),
				},
			],
		],
	],
];

/**
 * @param {string} value Raw icon position.
 * @return {'left'|'right'|'top'} Sanitized icon position.
 */
export function sanitizeIconPosition(value) {
	return ICON_POSITIONS.includes(value) ? value : DEFAULT_ICON_POSITION;
}

/**
 * @param {string} value Raw alignment.
 * @return {'start'|'center'|'end'} Sanitized alignment.
 */
export function sanitizeAlignment(value) {
	return ALIGNMENTS.includes(value) ? value : DEFAULT_ALIGNMENT;
}

/**
 * @param {'left'|'right'|'top'} iconPosition Current icon position.
 * @return {{ type: 'flex', orientation: 'horizontal'|'vertical' }} Layout attributes.
 */
export function getLayoutFromIconPosition(iconPosition) {
	return {
		type: 'flex',
		orientation: iconPosition === 'top' ? 'vertical' : 'horizontal',
	};
}

/**
 * @param {{ iconPosition?: string, alignment?: string }} attributes Block attributes.
 * @return {string} Modifier class names for the root wrapper.
 */
export function getIconBoxModifierClasses(attributes) {
	const iconPosition = sanitizeIconPosition(attributes?.iconPosition);
	const alignment = sanitizeAlignment(attributes?.alignment);

	const positionClass = {
		left: 'is-icon-left',
		right: 'is-icon-right',
		top: 'is-icon-top',
	}[iconPosition];

	return `${positionClass} is-align-${alignment}`;
}

/**
 * @param {'left'|'right'|'top'} iconPosition Current icon position.
 * @return {Array<{ label: string, value: string }>} Alignment options for controls.
 */
export function getAlignmentOptions(iconPosition) {
	if (iconPosition === 'top') {
		return [
			{ label: __('Left', 'chairforce'), value: 'start' },
			{ label: __('Center', 'chairforce'), value: 'center' },
			{ label: __('Right', 'chairforce'), value: 'end' },
		];
	}

	return [
		{ label: __('Top', 'chairforce'), value: 'start' },
		{ label: __('Center', 'chairforce'), value: 'center' },
		{ label: __('Bottom', 'chairforce'), value: 'end' },
	];
}
