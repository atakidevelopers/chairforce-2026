import { __ } from '@wordpress/i18n';

const HEADER_LINKS = [
	{
		label: __( 'Appearance → Menus', 'chairforce' ),
		url: '/wp-admin/nav-menus.php',
	},
	{
		label: __( 'Theme Options → Header', 'chairforce' ),
		url: '/wp-admin/admin.php?page=chairforce-theme-options',
	},
];

/**
 * Resolve admin links for a placeholder modifier.
 *
 * @param {string} modifier Block modifier slug.
 * @param {Array}  links    Links saved on the block, if any.
 * @return {Array}
 */
export function getEditorPlaceholderLinks( modifier, links = [] ) {
	if ( Array.isArray( links ) && links.length > 0 ) {
		return links.filter( ( link ) => link?.label && link?.url );
	}

	if ( modifier === 'header' ) {
		return HEADER_LINKS;
	}

	return [];
}
