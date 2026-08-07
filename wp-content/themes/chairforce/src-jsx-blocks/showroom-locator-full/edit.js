import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import { LOCATION_OPTIONS } from './constants';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const { defaultLocation, showCta } = attributes;

	const blockProps = useBlockProps({
		className: 'cf-showroom-locator-full-editor',
	});

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Showroom Locator (full) Settings', 'chairforce')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Default showroom', 'chairforce')}
						value={defaultLocation}
						options={LOCATION_OPTIONS}
						onChange={(value) =>
							setAttributes({ defaultLocation: value })
						}
					/>
					<ToggleControl
						label={__('Show showroom CTA', 'chairforce')}
						checked={showCta}
						onChange={(value) => setAttributes({ showCta: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<Disabled>
					<ServerSideRender
						block={metadata.name}
						attributes={attributes}
						LoadingResponsePlaceholder={() => (
							<div className="cf-showroom-locator-full-editor__placeholder">
								<Spinner />
								<p>
									{__(
										'Loading showroom locator preview...',
										'chairforce'
									)}
								</p>
							</div>
						)}
						ErrorResponsePlaceholder={({ response }) => (
							<div className="cf-showroom-locator-full-editor__placeholder cf-showroom-locator-full-editor__placeholder--error">
								<p>
									{__(
										'Unable to load the showroom locator preview.',
										'chairforce'
									)}
								</p>
								{response?.message ? (
									<p>{response.message}</p>
								) : null}
							</div>
						)}
						EmptyResponsePlaceholder={() => (
							<div className="cf-showroom-locator-full-editor__placeholder">
								<p>
									{__(
										'No showroom locator output was returned.',
										'chairforce'
									)}
								</p>
							</div>
						)}
					/>
				</Disabled>
			</div>
		</>
	);
}
