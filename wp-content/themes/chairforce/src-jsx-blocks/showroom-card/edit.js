import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	ButtonGroup,
	Disabled,
	PanelBody,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

import './editor.scss';

export default function Edit( { attributes, setAttributes, context } ) {
	const { ctaType, showImage, showAddress, showTime, showContact } =
		attributes;

	const blockProps = useBlockProps( {
		className: 'cf-showroom-card-editor',
	} );

	// ServerSideRender REST preview does not forward block context reliably.
	// Pass the loop showroom post explicitly when available.
	const urlQueryArgs = context?.postId
		? { post_id: Number( context.postId ) }
		: undefined;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Card content', 'chairforce' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Show image', 'chairforce' ) }
						checked={ showImage }
						onChange={ ( value ) =>
							setAttributes( { showImage: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show address', 'chairforce' ) }
						checked={ showAddress }
						onChange={ ( value ) =>
							setAttributes( { showAddress: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show opening hours', 'chairforce' ) }
						checked={ showTime }
						onChange={ ( value ) =>
							setAttributes( { showTime: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show phone and email', 'chairforce' ) }
						checked={ showContact }
						onChange={ ( value ) =>
							setAttributes( { showContact: value } )
						}
					/>
					<BaseControl
						className="cf-showroom-card-editor__cta-control"
						label={ __( 'CTA button', 'chairforce' ) }
						id="cf-showroom-card-cta-type"
						help={ __(
							'Get Directions opens Google Maps for this showroom address. Learn More links to the showroom page.',
							'chairforce'
						) }
					>
						<ButtonGroup id="cf-showroom-card-cta-type">
							<Button
								variant={
									ctaType === 'directions'
										? 'primary'
										: 'secondary'
								}
								onClick={ () =>
									setAttributes( { ctaType: 'directions' } )
								}
							>
								{ __( 'Get Directions', 'chairforce' ) }
							</Button>
							<Button
								variant={
									ctaType === 'learn-more'
										? 'primary'
										: 'secondary'
								}
								onClick={ () =>
									setAttributes( { ctaType: 'learn-more' } )
								}
							>
								{ __( 'Learn More', 'chairforce' ) }
							</Button>
						</ButtonGroup>
					</BaseControl>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
						urlQueryArgs={ urlQueryArgs }
						LoadingResponsePlaceholder={ () => (
							<div className="cf-showroom-card-editor__placeholder">
								<Spinner />
								<p>
									{ __(
										'Loading showroom card preview…',
										'chairforce'
									) }
								</p>
							</div>
						) }
						ErrorResponsePlaceholder={ ( { response } ) => (
							<div className="cf-showroom-card-editor__placeholder cf-showroom-card-editor__placeholder--error">
								<p>
									{ __(
										'Unable to load the showroom card preview.',
										'chairforce'
									) }
								</p>
								{ response?.message ? (
									<p>{ response.message }</p>
								) : null }
							</div>
						) }
						EmptyResponsePlaceholder={ () => (
							<div className="cf-showroom-card-editor__placeholder">
								<p>
									{ __(
										'No showroom card output was returned.',
										'chairforce'
									) }
								</p>
							</div>
						) }
					/>
				</Disabled>
			</div>
		</>
	);
}
