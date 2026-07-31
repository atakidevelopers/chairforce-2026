import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { context } = attributes;

	const blockProps = useBlockProps( {
		className:
			context === 'summary'
				? 'cf-wishlist-button cf-wishlist-button--summary cf-wishlist-button-editor'
				: 'cf-wishlist-button cf-wishlist-button-editor',
	} );

	const label =
		context === 'summary'
			? __( 'Add to wishlist', 'chairforce' )
			: __( 'Wishlist', 'chairforce' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Wishlist', 'chairforce' ) }>
					<SelectControl
						label={ __( 'Display context', 'chairforce' ) }
						value={ context }
						options={ [
							{
								label: __( 'Product card', 'chairforce' ),
								value: 'card',
							},
							{
								label: __( 'Single product summary', 'chairforce' ),
								value: 'summary',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { context: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<button
					type="button"
					className="cf-wishlist-trigger"
					disabled
					aria-pressed="false"
				>
					{ context === 'summary' ? (
						<span className="cf-wishlist-trigger__label">
							{ label }
						</span>
					) : (
						<span className="screen-reader-text">{ label }</span>
					) }
				</button>
			</div>
		</>
	);
}
