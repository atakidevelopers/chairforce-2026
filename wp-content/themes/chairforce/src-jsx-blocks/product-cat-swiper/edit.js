import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, Placeholder, SelectControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import CategoryTermPicker from './term-picker';
import { isValidSelectedTerm, getProductCatSwiperOrderByOptions, getProductCatSwiperOrderOptions } from './term-utils';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const {
		terms = [],
		showArrowsDesktop = true,
		showArrowsMobile = false,
		showProgressBar = true,
		showLabels = true,
		orderBy = 'manual',
		order = 'asc',
	} = attributes;

	const selectedTerms = Array.isArray( terms )
		? terms.filter( isValidSelectedTerm )
		: [];

	const blockProps = useBlockProps( {
		className: 'cf-product-cat-swiper-editor',
	} );

	const previewAttributes = {
		terms: selectedTerms,
		showArrowsDesktop,
		showArrowsMobile,
		showProgressBar,
		showLabels,
		orderBy,
		order,
		previewMode: true,
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Categories', 'chairforce' ) }
					initialOpen={ true }
				>
					<CategoryTermPicker
						terms={ selectedTerms }
						onChange={ ( nextTerms ) =>
							setAttributes( { terms: nextTerms } )
						}
					/>
					<SelectControl
						label={ __( 'Order by', 'chairforce' ) }
						value={ orderBy }
						options={ getProductCatSwiperOrderByOptions() }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value || 'manual' } )
						}
						help={ __(
							'As selected preserves the token order above. Other options use WordPress term query ordering.',
							'chairforce'
						) }
					/>
					{ orderBy !== 'manual' && (
						<SelectControl
							label={ __( 'Order', 'chairforce' ) }
							value={ order }
							options={ getProductCatSwiperOrderOptions() }
							onChange={ ( value ) =>
								setAttributes( { order: value || 'asc' } )
							}
						/>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Swiper display', 'chairforce' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show arrows on desktop', 'chairforce' ) }
						checked={ showArrowsDesktop }
						onChange={ ( value ) =>
							setAttributes( { showArrowsDesktop: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show arrows on mobile', 'chairforce' ) }
						checked={ showArrowsMobile }
						onChange={ ( value ) =>
							setAttributes( { showArrowsMobile: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show progress bar', 'chairforce' ) }
						checked={ showProgressBar }
						onChange={ ( value ) =>
							setAttributes( { showProgressBar: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show category labels', 'chairforce' ) }
						checked={ showLabels }
						onChange={ ( value ) =>
							setAttributes( { showLabels: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ selectedTerms.length > 0 ? (
					<ServerSideRender
						block="chairforce/product-cat-swiper"
						attributes={ previewAttributes }
					/>
				) : (
					<Placeholder
						icon="slides"
						label={ __( 'Product Category Swiper', 'chairforce' ) }
						instructions={ __(
							'Select product categories in the block sidebar to build the swiper.',
							'chairforce'
						) }
					>
						<p className="cf-product-cat-swiper-editor__hint">
							{ __(
								'Use the Categories panel to search and add terms.',
								'chairforce'
							) }
						</p>
					</Placeholder>
				) }
			</div>
		</>
	);
}
