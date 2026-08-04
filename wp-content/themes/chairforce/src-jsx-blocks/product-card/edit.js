import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

export default function Edit( { attributes, context } ) {
	const blockProps = useBlockProps();

	// ServerSideRender REST preview does not forward block context reliably.
	// It defaults post_id to the edited page; pass the loop product explicitly.
	const urlQueryArgs = context?.postId
		? { post_id: Number( context.postId ) }
		: undefined;

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="chairforce/product-card"
				attributes={ attributes }
				urlQueryArgs={ urlQueryArgs }
			/>
		</div>
	);
}
