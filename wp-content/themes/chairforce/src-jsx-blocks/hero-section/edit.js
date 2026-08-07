import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	MediaReplaceFlow,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	ToolbarButton,
	FocalPointPicker,
} from '@wordpress/components';

import { getBackgroundStyle, normalizeFocalPoint } from './background-style';
import {
	ALLOWED_BLOCKS,
	ALLOWED_MEDIA_TYPES,
	DEFAULT_FOCAL_POINT,
	INNER_BLOCKS_TEMPLATE,
} from './constants';

export default function Edit({ attributes, setAttributes, clientId }) {
	const { backgroundImageId, backgroundImageUrl, focalPoint } = attributes;
	const normalizedFocalPoint = normalizeFocalPoint(focalPoint);

	const hasInnerBlocks = useSelect(
		(select) => {
			const block = select(blockEditorStore).getBlock(clientId);
			return (block?.innerBlocks?.length ?? 0) > 0;
		},
		[clientId]
	);

	const onSelectImage = (media) => {
		if (!media?.url) {
			return;
		}

		setAttributes({
			backgroundImageId: media.id ?? 0,
			backgroundImageUrl: media.url,
		});
	};

	const onRemoveImage = () => {
		setAttributes({
			backgroundImageId: 0,
			backgroundImageUrl: '',
			focalPoint: DEFAULT_FOCAL_POINT,
		});
	};

	const blockProps = useBlockProps({
		style: getBackgroundStyle(backgroundImageUrl, normalizedFocalPoint),
	});

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'cf-hero-section__inner',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: hasInnerBlocks ? undefined : INNER_BLOCKS_TEMPLATE,
			templateLock: 'insert',
		}
	);

	const hasBackgroundImage = Boolean(backgroundImageUrl);
	const mediaId = backgroundImageId > 0 ? backgroundImageId : undefined;

	return (
		<>
			<BlockControls group="other">
				{hasBackgroundImage ? (
					<MediaUploadCheck>
						<MediaReplaceFlow
							mediaId={mediaId}
							mediaURL={backgroundImageUrl}
							allowedTypes={ALLOWED_MEDIA_TYPES}
							onSelect={onSelectImage}
							name={__('Replace background image', 'chairforce')}
						/>
					</MediaUploadCheck>
				) : (
					<MediaUploadCheck>
						<MediaUpload
							onSelect={onSelectImage}
							allowedTypes={ALLOWED_MEDIA_TYPES}
							render={({ open }) => (
								<ToolbarButton
									icon="format-image"
									onClick={open}
									label={__(
										'Select background image',
										'chairforce'
									)}
								/>
							)}
						/>
					</MediaUploadCheck>
				)}
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={__('Background image', 'chairforce')}
					initialOpen
				>
					{hasBackgroundImage ? (
						<>
							<FocalPointPicker
								url={backgroundImageUrl}
								value={normalizedFocalPoint}
								onChange={(nextFocalPoint) =>
									setAttributes({
										focalPoint:
											normalizeFocalPoint(nextFocalPoint),
									})
								}
							/>
							<Button variant="secondary" onClick={onRemoveImage}>
								{__('Remove background image', 'chairforce')}
							</Button>
						</>
					) : (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectImage}
								allowedTypes={ALLOWED_MEDIA_TYPES}
								render={({ open }) => (
									<Button variant="primary" onClick={open}>
										{__(
											'Select background image',
											'chairforce'
										)}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					)}
				</PanelBody>
			</InspectorControls>

			<section {...blockProps}>
				<div {...innerBlocksProps} />
			</section>
		</>
	);
}
