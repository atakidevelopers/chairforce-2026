import { useBlockProps } from '@wordpress/block-editor';

import EditorPlaceholderNotice from '../editor-placeholder/notice';
import { getEditorPlaceholderLinks } from '../editor-placeholder/utils';

export default function Edit( { attributes } ) {
	const { title, description, links } = attributes;
	const blockProps = useBlockProps( {
		className: 'cf-editor-placeholder',
	} );
	const displayLinks = getEditorPlaceholderLinks( links );

	return (
		<div { ...blockProps }>
			<EditorPlaceholderNotice
				title={ title }
				description={ description }
				displayLinks={ displayLinks }
			/>
		</div>
	);
}
