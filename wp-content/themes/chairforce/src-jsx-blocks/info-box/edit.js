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
	buildModifierClasses,
	getAlignmentOptions,
	getLayoutFromIconPosition,
	INNER_BLOCKS_TEMPLATE,
	sanitizeAlignment,
	sanitizeIconPosition,
} from './constants';

import './editor.scss';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { iconPosition, alignment, layout } = attributes;

	const safeIconPosition = sanitizeIconPosition( iconPosition );
	const safeAlignment    = sanitizeAlignment( alignment );

	const hasInnerBlocks = useSelect(
		( select ) =>
			( select( blockEditorStore ).getBlock( clientId )?.innerBlocks
				?.length ?? 0 ) > 0,
		[ clientId ]
	);

	// Keep the layout attribute in sync with iconPosition so that WP's
	// block toolbar shows the correct orientation label.
	useEffect( () => {
		const next = getLayoutFromIconPosition( safeIconPosition );
		if ( layout?.orientation === next.orientation ) {
			return;
		}
		setAttributes( { layout: next } );
	}, [ safeIconPosition, layout, setAttributes ] );

	const blockProps = useBlockProps( {
		className: buildModifierClasses( {
			iconPosition: safeIconPosition,
			alignment: safeAlignment,
		} ),
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: hasInnerBlocks ? undefined : INNER_BLOCKS_TEMPLATE,
		templateLock: false,
	} );

	const alignmentOptions = getAlignmentOptions( safeIconPosition );

	return (
		<>
			<BlockControls group="block">
				<ToolbarGroup label={ __( 'Icon position', 'chairforce' ) }>
					<ToolbarButton
						icon="align-pull-left"
						label={ __( 'Icon left', 'chairforce' ) }
						isPressed={ safeIconPosition === 'left' }
						onClick={ () =>
							setAttributes( {
								iconPosition: 'left',
								alignment: 'center',
							} )
						}
					/>
					<ToolbarButton
						icon="align-pull-right"
						label={ __( 'Icon right', 'chairforce' ) }
						isPressed={ safeIconPosition === 'right' }
						onClick={ () =>
							setAttributes( {
								iconPosition: 'right',
								alignment: 'center',
							} )
						}
					/>
					<ToolbarButton
						icon="align-center"
						label={ __( 'Icon top', 'chairforce' ) }
						isPressed={ safeIconPosition === 'top' }
						onClick={ () =>
							setAttributes( {
								iconPosition: 'top',
								alignment: 'start',
							} )
						}
					/>
				</ToolbarGroup>
			</BlockControls>

		<InspectorControls>
			<PanelBody title={ __( 'Icon Box', 'chairforce' ) } initialOpen>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Alignment', 'chairforce' ) }
					value={ safeAlignment }
					options={ alignmentOptions }
					onChange={ ( next ) =>
						setAttributes( { alignment: next } )
					}
				/>
			</PanelBody>
		</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}
