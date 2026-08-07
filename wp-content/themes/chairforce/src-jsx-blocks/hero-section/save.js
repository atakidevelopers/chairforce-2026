import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

import { getBackgroundStyle, normalizeFocalPoint } from './background-style';

export default function save({ attributes }) {
	const { backgroundImageUrl, focalPoint } = attributes;

	const blockProps = useBlockProps.save({
		style: getBackgroundStyle(
			backgroundImageUrl,
			normalizeFocalPoint(focalPoint)
		),
	});

	return (
		<section {...blockProps}>
			<div className="cf-hero-section__inner">
				<InnerBlocks.Content />
			</div>
		</section>
	);
}
