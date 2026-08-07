import './editor.scss';

import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';

import {
	ALLOWED_BLOCKS,
	getAlignmentOptions,
	getIconBoxModifierClasses,
	getLayoutFromIconPosition,
	INNER_BLOCKS_TEMPLATE,
	sanitizeAlignment,
	sanitizeIconPosition,
} from './constants';

export default function Edit({ attributes, setAttributes, clientId }) {
	const { iconPosition, alignment, layout } = attributes;
	const safeIconPosition = sanitizeIconPosition(iconPosition);
	const safeAlignment = sanitizeAlignment(alignment);
	const alignmentOptions = getAlignmentOptions(safeIconPosition);

	const hasInnerBlocks = useSelect(
		(select) => {
			const block = select(blockEditorStore).getBlock(clientId);
			return (block?.innerBlocks?.length ?? 0) > 0;
		},
		[clientId]
	);

	useEffect(() => {
		const nextLayout = getLayoutFromIconPosition(safeIconPosition);

		if (
			layout?.type === nextLayout.type &&
			layout?.orientation === nextLayout.orientation
		) {
			return;
		}

		setAttributes({ layout: nextLayout });
	}, [safeIconPosition, layout, setAttributes]);

	const blockProps = useBlockProps({
		className: getIconBoxModifierClasses(attributes),
	});

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: hasInnerBlocks ? undefined : INNER_BLOCKS_TEMPLATE,
		templateLock: 'all',
	});

	return (
		<>
			<BlockControls group="block">
				<ToolbarGroup
					label={__('Icon position', 'chairforce')}
					className="cf-icon-box-position-toolbar"
				>
					<ToolbarButton
						icon="align-pull-left"
						label={__('Icon left', 'chairforce')}
						isPressed={safeIconPosition === 'left'}
						onClick={() => setAttributes({ iconPosition: 'left' })}
					/>
					<ToolbarButton
						icon="align-pull-right"
						label={__('Icon right', 'chairforce')}
						isPressed={safeIconPosition === 'right'}
						onClick={() => setAttributes({ iconPosition: 'right' })}
					/>
					<ToolbarButton
						icon="align-center"
						label={__('Icon top', 'chairforce')}
						isPressed={safeIconPosition === 'top'}
						onClick={() => setAttributes({ iconPosition: 'top' })}
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={__('Icon Box Layout', 'chairforce')}
					initialOpen
				>
					<SelectControl
						label={__('Icon position', 'chairforce')}
						value={safeIconPosition}
						options={[
							{
								label: __('Left', 'chairforce'),
								value: 'left',
							},
							{
								label: __('Right', 'chairforce'),
								value: 'right',
							},
							{
								label: __('Top', 'chairforce'),
								value: 'top',
							},
						]}
						onChange={(nextPosition) =>
							setAttributes({ iconPosition: nextPosition })
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={__('Icon alignment', 'chairforce')}
						value={safeAlignment}
						options={alignmentOptions}
						onChange={(nextAlignment) =>
							setAttributes({ alignment: nextAlignment })
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div {...innerBlocksProps} />
		</>
	);
}
