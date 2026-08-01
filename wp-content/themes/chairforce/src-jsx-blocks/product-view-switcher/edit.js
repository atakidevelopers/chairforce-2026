import { useBlockProps } from '@wordpress/block-editor';

import EditorPlaceholderNotice from '../editor-placeholder/notice';

export default function Edit( { attributes } ) {
	const { title, description } = attributes;
	const blockProps = useBlockProps( {
		className: 'cf-editor-placeholder',
	} );

	return (
		<div { ...blockProps }>
			<EditorPlaceholderNotice
				title={ title }
				description={ description }
				displayLinks={ [] }
			/>
		</div>
	);
}
