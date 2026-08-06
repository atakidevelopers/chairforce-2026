import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { reviewsPerPage, displayReviewsSummary } = attributes;

	const blockProps = useBlockProps( {
		className: 'cf-product-reviews cf-product-reviews-editor',
	} );

	const listColumn = (
		<div className="cf-product-reviews__inner">
			<h2 className="cf-product-reviews__title">
				{ __( '203 Reviews', 'chairforce' ) }
			</h2>
			<ol className="cf-product-reviews__list" aria-hidden="true">
				<li className="cf-product-reviews__item">
					<div className="cf-product-reviews__item-header">
						<span className="cf-product-reviews__author-name">
							{ __( 'Sarah M.', 'chairforce' ) }
						</span>
						<span className="cf-product-reviews__date">
							{ __( 'March 15, 2026', 'chairforce' ) }
						</span>
					</div>
					<p className="cf-product-reviews__content">
						{ __(
							'Review cards render from WooCommerce product reviews on the frontend.',
							'chairforce'
						) }
					</p>
				</li>
			</ol>
			{ ! displayReviewsSummary && (
				<p className="cf-product-reviews-editor__cta-note">
					{ __( 'Write a Review button renders here when summary is off.', 'chairforce' ) }
				</p>
			) }
		</div>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Reviews', 'chairforce' ) }>
					<ToggleControl
						label={ __( 'Display reviews summary', 'chairforce' ) }
						help={ __(
							'When on, shows average rating and histogram beside the list. When off, a Write a Review button appears in this column instead.',
							'chairforce'
						) }
						checked={ displayReviewsSummary }
						onChange={ ( value ) =>
							setAttributes( { displayReviewsSummary: value } )
						}
					/>
					<RangeControl
						label={ __( 'Reviews per page', 'chairforce' ) }
						value={ reviewsPerPage }
						onChange={ ( value ) =>
							setAttributes( { reviewsPerPage: value } )
						}
						min={ 1 }
						max={ 20 }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps } id="reviews">
				{ displayReviewsSummary ? (
					<div className="wp-block-columns alignwide">
						<div className="wp-block-column" style={ { flexBasis: '25%' } }>
							<p className="cf-product-reviews-summary-editor">
								{ __( 'Summary column (average + histogram + Write a Review)', 'chairforce' ) }
							</p>
						</div>
						<div className="wp-block-column">{ listColumn }</div>
					</div>
				) : (
					<div className="wp-block-columns alignwide">
						<div className="wp-block-column">{ listColumn }</div>
					</div>
				) }
			</div>
		</>
	);
}
