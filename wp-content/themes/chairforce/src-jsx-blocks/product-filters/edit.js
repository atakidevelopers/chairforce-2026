import { useBlockProps } from '@wordpress/block-editor';

import EditorPlaceholderNotice from '../editor-placeholder/notice';
import { getEditorPlaceholderLinks } from '../editor-placeholder/utils';

export default function Edit( { attributes } ) {
	const { title, description, links, part = 'chrome' } = attributes;
	const blockProps = useBlockProps( {
		className: 'cf-editor-placeholder',
	} );
	const displayLinks = getEditorPlaceholderLinks( links );
	const partLabel = 'sidebar' === part ? 'Filter sidebar' : 'Filter bar';

	return (
		<div { ...blockProps }>
			<EditorPlaceholderNotice
				title={ `${ title } (${ partLabel })` }
				description={ description }
				displayLinks={ displayLinks }
			/>
		</div>
	);
}
