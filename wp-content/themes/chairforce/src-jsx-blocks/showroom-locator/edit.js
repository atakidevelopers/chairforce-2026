import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	Disabled,
	PanelBody,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import { LOCATION_OPTIONS } from './constants';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const { defaultLocation } = attributes;

	const showroomOptions = useSelect((select) => {
		const posts = select(coreStore).getEntityRecords('postType', 'showrooms', {
			per_page: -1,
			status: 'publish',
			orderby: 'menu_order',
			order: 'asc',
		});

		if (!posts) {
			return null;
		}

		return posts.map((post) => ({
			label: post.title?.rendered || post.slug,
			value: post.slug,
		}));
	}, []);

	const options =
		showroomOptions && showroomOptions.length > 0
			? showroomOptions
			: LOCATION_OPTIONS;

	const blockProps = useBlockProps({
		className: 'cf-showroom-locator-editor',
	});

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Showroom Locator Settings', 'chairforce')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Default showroom', 'chairforce')}
						value={defaultLocation}
						options={options}
						onChange={(value) =>
							setAttributes({ defaultLocation: value })
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<Disabled>
					<ServerSideRender
						block={metadata.name}
						attributes={attributes}
						LoadingResponsePlaceholder={() => (
							<div className="cf-showroom-locator-editor__placeholder">
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
							<div className="cf-showroom-locator-editor__placeholder cf-showroom-locator-editor__placeholder--error">
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
							<div className="cf-showroom-locator-editor__placeholder">
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
