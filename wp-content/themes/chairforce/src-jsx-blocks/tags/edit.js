import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

const ALLOWED_BLOCKS = [ 'chairforce/tag' ];

const TEMPLATE = [ [ 'chairforce/tag' ] ];

export default function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ TEMPLATE }
				templateLock={ false }
			/>
		</div>
	);
}
