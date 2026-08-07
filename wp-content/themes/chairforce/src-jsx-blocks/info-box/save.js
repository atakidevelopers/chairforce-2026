import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { buildModifierClasses } from './constants';

export default function save( { attributes } ) {
	const { iconPosition, iconStyle, alignment } = attributes;

	const blockProps = useBlockProps.save( {
		className: buildModifierClasses( { iconPosition, iconStyle, alignment } ),
	} );

	return <div { ...useInnerBlocksProps.save( blockProps ) } />;
}
