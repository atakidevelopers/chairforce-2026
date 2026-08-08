import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { buildModifierClasses } from './constants';

export default function save( { attributes } ) {
	const { iconPosition, alignment } = attributes;

	const blockProps = useBlockProps.save( {
		className: buildModifierClasses( { iconPosition, alignment } ),
	} );

	return <div { ...useInnerBlocksProps.save( blockProps ) } />;
}
