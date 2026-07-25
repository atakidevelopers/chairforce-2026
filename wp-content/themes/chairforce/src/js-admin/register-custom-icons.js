/**
 * Registers the theme's curated Lucide icon set with the Icon Block plugin
 * (outermost/icon-block), so editors can insert them via that block's own
 * inserter/search modal, grouped under a "ChairForce" category - in
 * addition to the plugin's own default icon library.
 *
 * This is a separate icon-picking surface from the Button block's icon
 * picker (see button-icons.js) - they intentionally share the same curated
 * icon set/labels (see chairforce-icon-block-icons.js) but are otherwise
 * independent integrations, since the Icon Block plugin owns its own
 * rendering/attributes and isn't related to `core/button`.
 *
 * @see https://nickdiego.com/adding-custom-icons-to-the-icon-block
 */

import { CHAIRFORCE_ICON_BLOCK_ICONS } from './chairforce-icon-block-icons';

wp.domReady( () => {
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;

	function chairforceAddCustomIcons( icons ) {
		const customIconCategories = [
			{
				name: 'chairforce',
				title: __( 'ChairForce', 'chairforce' )
			}
		];

		const customIconType = [
			{
				isDefault: true,
				type: 'chairforce',
				title: __( 'ChairForce', 'chairforce' ),
				icons: CHAIRFORCE_ICON_BLOCK_ICONS,
				categories: customIconCategories
			}
		];

		return [].concat( icons, customIconType );
	}

	addFilter(
		'iconBlock.icons',
		'chairforce/icon-block-custom-icons',
		chairforceAddCustomIcons
	);
} );
