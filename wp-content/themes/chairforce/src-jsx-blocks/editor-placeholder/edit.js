import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

import { getEditorPlaceholderLinks } from './utils';

export default function Edit( { attributes } ) {
	const { title, description, modifier, links } = attributes;
	const blockProps = useBlockProps( {
		className: 'cf-editor-placeholder',
	} );
	const displayLinks = getEditorPlaceholderLinks( modifier, links );

	return (
		<div { ...blockProps }>
			<Notice status="info" isDismissible={ false }>
				<p>
					{ title && <strong>{ title }</strong> }
					{ title && description && ' — ' }
					{ description }
					{ displayLinks.length > 0 && (
						<>
							{ ( title || description ) && ' ' }
							{ displayLinks.map( ( link, index ) => (
								<Fragment key={ link.url }>
									{ index > 0 && (
										<span aria-hidden="true"> · </span>
									) }
									<a href={ link.url }>{ link.label }</a>
								</Fragment>
							) ) }
						</>
					) }
				</p>
			</Notice>
		</div>
	);
}
