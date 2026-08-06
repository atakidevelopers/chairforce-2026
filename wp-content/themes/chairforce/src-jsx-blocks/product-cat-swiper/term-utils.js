import { __ } from '@wordpress/i18n';

/**
 * @return {Array<{ label: string, value: string }>}
 */
export function getProductCatSwiperOrderByOptions() {
	return [
		{
			label: __( 'As selected', 'chairforce' ),
			value: 'manual',
		},
		{
			label: __( 'Menu order', 'chairforce' ),
			value: 'menu_order',
		},
		{
			label: __( 'Name', 'chairforce' ),
			value: 'name',
		},
		{
			label: __( 'Slug', 'chairforce' ),
			value: 'slug',
		},
		{
			label: __( 'Term ID', 'chairforce' ),
			value: 'term_id',
		},
		{
			label: __( 'Count', 'chairforce' ),
			value: 'count',
		},
		{
			label: __( 'Description', 'chairforce' ),
			value: 'description',
		},
		{
			label: __( 'Term group', 'chairforce' ),
			value: 'term_group',
		},
	];
}

/**
 * @return {Array<{ label: string, value: string }>}
 */
export function getProductCatSwiperOrderOptions() {
	return [
		{
			label: __( 'Ascending', 'chairforce' ),
			value: 'asc',
		},
		{
			label: __( 'Descending', 'chairforce' ),
			value: 'desc',
		},
	];
}

/**
 * Build hierarchical option labels for product_cat terms.
 *
 * @param {Array<{ id: number, name: string, parent?: number }>} terms
 * @param {number} parent
 * @param {number} depth
 * @return {Array<{ id: number, name: string, label: string }>}
 */
export function buildHierarchicalTermOptions( terms, parent = 0, depth = 0 ) {
	if ( ! Array.isArray( terms ) || terms.length === 0 ) {
		return [];
	}

	return terms
		.filter( ( term ) => ( term.parent || 0 ) === parent )
		.flatMap( ( term ) => [
			{
				id: term.id,
				name: term.name,
				label: `${ '— '.repeat( depth ) }${ term.name }`,
			},
			...buildHierarchicalTermOptions( terms, term.id, depth + 1 ),
		] );
}

/**
 * @param {{ id: number, name: string, parent?: number }} term
 * @param {Map<number, object>} termsById
 * @return {number}
 */
export function getTermDepth( term, termsById ) {
	let depth = 0;
	let parentId = term.parent || 0;

	while ( parentId > 0 && termsById.has( parentId ) ) {
		depth += 1;
		parentId = termsById.get( parentId ).parent || 0;
	}

	return depth;
}

/**
 * @param {{ id: number, name: string, parent?: number }} term
 * @param {Map<number, object>} termsById
 * @return {string}
 */
export function getTermPickerLabel( term, termsById ) {
	const depth = getTermDepth( term, termsById );

	return `${ '— '.repeat( depth ) }${ term.name }`;
}

/**
 * @param {unknown} term
 * @return {term is { id: number, name: string }}
 */
export function isValidSelectedTerm( term ) {
	return (
		!! term &&
		typeof term === 'object' &&
		Number.isFinite( term.id ) &&
		term.id > 0 &&
		typeof term.name === 'string' &&
		term.name.length > 0
	);
}
