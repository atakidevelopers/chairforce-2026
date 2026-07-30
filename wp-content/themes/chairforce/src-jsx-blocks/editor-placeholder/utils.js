/**
 * Resolve admin links saved on the block.
 *
 * @param {Array} links Links from block attributes.
 * @return {Array}
 */
export function getEditorPlaceholderLinks( links = [] ) {
	if ( ! Array.isArray( links ) ) {
		return [];
	}

	return links.filter( ( link ) => link?.label && link?.url );
}
