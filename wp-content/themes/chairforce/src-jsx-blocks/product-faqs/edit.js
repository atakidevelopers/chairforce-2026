import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { initialVisibleCount = 5, includeFaqSchema = true } = attributes;

	const blockProps = useBlockProps( {
		className: 'cf-accordion cf-product-faqs-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'FAQ display', 'chairforce' ) } initialOpen={ true }>
					<RangeControl
						label={ __( 'FAQs shown before Load More', 'chairforce' ) }
						help={ __(
							'When a product has more FAQs than this number, the rest are hidden until Load More is clicked.',
							'chairforce'
						) }
						value={ initialVisibleCount }
						onChange={ ( value ) =>
							setAttributes( {
								initialVisibleCount: value ?? 5,
							} )
						}
						min={ 1 }
						max={ 20 }
					/>
					<ToggleControl
						label={ __( 'Include FAQ schema', 'chairforce' ) }
						help={ __(
							'Output FAQPage JSON-LD in the page footer when this block renders FAQs.',
							'chairforce'
						) }
						checked={ includeFaqSchema }
						onChange={ ( value ) =>
							setAttributes( { includeFaqSchema: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps } data-cf-accordion>
				<div className="cf-accordion__list" aria-hidden="true">
					<div className="cf-accordion__item is-open">
						<button
							type="button"
							className="cf-accordion__trigger"
							tabIndex={ -1 }
						>
							<span className="cf-accordion__label">
								{ __( 'How long does delivery take?', 'chairforce' ) }
							</span>
							<span className="cf-accordion__icon" aria-hidden="true" />
						</button>
						<div className="cf-accordion__panel" aria-hidden="false">
							<div className="cf-accordion__content">
								<p>
									{ __(
										'FAQ accordion renders from FAQ Configurations and product settings on the frontend.',
										'chairforce'
									) }
								</p>
							</div>
						</div>
					</div>
					<div className="cf-accordion__item">
						<button
							type="button"
							className="cf-accordion__trigger"
							tabIndex={ -1 }
						>
							<span className="cf-accordion__label">
								{ __( 'Is assembly required?', 'chairforce' ) }
							</span>
							<span className="cf-accordion__icon" aria-hidden="true" />
						</button>
						<div className="cf-accordion__panel" aria-hidden="true">
							<div className="cf-accordion__content">
								<p>{ __( 'Preview answer.', 'chairforce' ) }</p>
							</div>
						</div>
					</div>
				</div>
				<p className="cf-product-faqs-editor__note">
					{ __(
						'Resolves FAQ post IDs for the current product, then renders the shared accordion component.',
						'chairforce'
					) }
				</p>
				<p className="cf-product-faqs-editor__note">
					{ sprintf(
						/* translators: %d: number of FAQs shown before Load More */
						__(
							'Load More shows after %d FAQs on the frontend when more are assigned.',
							'chairforce'
						),
						initialVisibleCount
					) }
				</p>
			</div>
		</>
	);
}
