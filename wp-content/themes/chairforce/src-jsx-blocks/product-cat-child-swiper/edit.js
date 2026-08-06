import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

import './editor.scss';

const PREVIEW_ITEMS = [
	{ label: __( 'Cafe Chairs', 'chairforce' ) },
	{ label: __( 'Office Chairs', 'chairforce' ) },
	{ label: __( 'Dining Chairs', 'chairforce' ) },
	{ label: __( 'Outdoor Chairs', 'chairforce' ) },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		showArrowsDesktop = true,
		showArrowsMobile = false,
		showProgressBar = true,
		showLabels = true,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'cf-category-swiper cf-product-cat-child-swiper-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Swiper display', 'chairforce' ) }
					initialOpen={ true }
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
				<div
					className="cf-category-swiper__viewport"
					data-cf-category-swiper
					aria-hidden="true"
				>
					<div className="cf-category-swiper__track">
						<div className="cf-category-swiper__swiper">
							<div className="cf-category-swiper__slides">
								{ PREVIEW_ITEMS.map( ( item ) => (
									<div
										key={ item.label }
										className="cf-category-swiper__slide cf-category-swiper__slide--preview"
									>
										<span className="cf-category-swiper__card">
											<span className="cf-category-swiper__media">
												<span className="cf-category-swiper__image cf-category-swiper__image--placeholder" />
											</span>
											{ showLabels && (
												<span className="cf-category-swiper__label">
													{ item.label }
												</span>
											) }
										</span>
									</div>
								) ) }
							</div>
							{ showProgressBar && (
								<div className="cf-category-swiper__scrollbar cf-category-swiper__scrollbar--preview" />
							) }
						</div>
						{ ( showArrowsDesktop || showArrowsMobile ) && (
							<div className="cf-category-swiper__nav">
								<button
									type="button"
									className="cf-category-swiper__arrow cf-category-swiper__arrow--prev"
									tabIndex={ -1 }
									aria-hidden="true"
								/>
								<button
									type="button"
									className="cf-category-swiper__arrow cf-category-swiper__arrow--next"
									tabIndex={ -1 }
									aria-hidden="true"
								/>
							</div>
						) }
					</div>
				</div>
				<p className="cf-product-cat-child-swiper-editor__note">
					{ __(
						'On product category archives, renders immediate child categories with images and links. Leaf categories output nothing.',
						'chairforce'
					) }
				</p>
			</div>
		</>
	);
}
