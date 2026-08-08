import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const columnsWithTabletStack = createHigherOrderComponent(
	( BlockEdit ) =>
		( props ) => {
			if ( props.name !== 'core/columns' ) {
				return <BlockEdit { ...props } />;
			}

			const { attributes, setAttributes } = props;
			const { isStackedOnTablet } = attributes;

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody title={ __( 'Tablet layout', 'chairforce' ) }>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Stack on tablet', 'chairforce' ) }
								checked={ !! isStackedOnTablet }
								onChange={ ( val ) =>
									setAttributes( { isStackedOnTablet: val } )
								}
							/>
						</PanelBody>
					</InspectorControls>
				</>
			);
		},
	'columnsWithTabletStack'
);
