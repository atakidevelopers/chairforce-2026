export const TAG_ELEMENTS = [
	{
		value: 'span',
		label: 'Span',
	},
	{
		value: 'p',
		label: 'P',
	},
	{
		value: 'div',
		label: 'Div',
	},
	{
		value: 'h2',
		label: 'H2',
	},
	{
		value: 'h3',
		label: 'H3',
	},
	{
		value: 'h4',
		label: 'H4',
	},
	{
		value: 'h5',
		label: 'H5',
	},
	{
		value: 'h6',
		label: 'H6',
	},
];

export const ALLOWED_TAG_NAMES = TAG_ELEMENTS.map(
	( element ) => element.value
);

export const TAG_LABEL_CLASS = 'cf-tag__label';

export function sanitizeTagName( tagName ) {
	return ALLOWED_TAG_NAMES.includes( tagName ) ? tagName : 'span';
}
