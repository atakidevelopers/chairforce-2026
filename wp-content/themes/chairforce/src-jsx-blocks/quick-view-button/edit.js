import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'cf-quick-view-button-editor',
	} );

	return (
		<div { ...blockProps }>
			<button type="button" className="cf-quick-view-trigger" disabled>
				<span className="screen-reader-text">
					{ __( 'Quick view', 'chairforce' ) }
				</span>
			</button>
		</div>
	);
}
