import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { getIconBoxModifierClasses } from './constants';

export default function save({ attributes }) {
	const blockProps = useBlockProps.save({
		className: getIconBoxModifierClasses(attributes),
	});

	return <div {...useInnerBlocksProps.save(blockProps)} />;
}
