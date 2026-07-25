import { useBlockProps, RichText } from '@wordpress/block-editor';

import { TAG_LABEL_CLASS, sanitizeTagName } from './constants';

export default function save( { attributes } ) {
	const { content, tagName } = attributes;
	const safeTagName = sanitizeTagName( tagName );
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<RichText.Content
				tagName={ safeTagName }
				className={ TAG_LABEL_CLASS }
				value={ content }
			/>
		</div>
	);
}
