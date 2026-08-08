import { addFilter } from '@wordpress/hooks';
import { columnsWithTabletStack } from './edit';

// ── 1. Register the attribute on core/columns ─────────────────────────────────
const addColumnsAttributes = ( settings, name ) => {
	if ( name !== 'core/columns' ) return settings;

	return {
		...settings,
		attributes: {
			...settings.attributes,
			isStackedOnTablet: {
				type: 'boolean',
				default: false,
			},
		},
	};
};

// ── 2. Stamp the class onto the saved HTML ────────────────────────────────────
const addColumnsSaveProps = ( extraProps, blockType, attributes ) => {
	if ( blockType.name !== 'core/columns' ) return extraProps;

	if ( attributes.isStackedOnTablet ) {
		extraProps.className =
			( extraProps.className ? extraProps.className + ' ' : '' ) +
			'is-stacked-on-tablet';
	}

	return extraProps;
};

addFilter(
	'blocks.registerBlockType',
	'chairforce/columns/stack-on-tablet',
	addColumnsAttributes
);

addFilter(
	'blocks.getSaveContent.extraProps',
	'chairforce/columns/stack-on-tablet',
	addColumnsSaveProps
);

addFilter(
	'editor.BlockEdit',
	'chairforce/columns/stack-on-tablet',
	columnsWithTabletStack
);
