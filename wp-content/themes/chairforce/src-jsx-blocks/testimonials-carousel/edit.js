import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled, Notice, Spinner } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps({
		className: 'cf-testimonials-carousel-editor',
	});

	return (
		<div {...blockProps}>
			<Notice status="info" isDismissible={false}>
				{__(
					'Testimonials are managed from the Reviews post type.',
					'chairforce'
				)}
			</Notice>

			<Disabled>
				<ServerSideRender
					block={metadata.name}
					attributes={{}}
					LoadingResponsePlaceholder={() => (
						<div className="cf-testimonials-carousel-editor__placeholder">
							<Spinner />
							<p>
								{__(
									'Loading testimonials carousel preview...',
									'chairforce'
								)}
							</p>
						</div>
					)}
					ErrorResponsePlaceholder={({ response }) => (
						<div className="cf-testimonials-carousel-editor__placeholder cf-testimonials-carousel-editor__placeholder--error">
							<p>
								{__(
									'Unable to load the testimonials carousel preview.',
									'chairforce'
								)}
							</p>
							{response?.message ? (
								<p>{response.message}</p>
							) : null}
						</div>
					)}
					EmptyResponsePlaceholder={() => (
						<Notice status="warning" isDismissible={false}>
							{__(
								'No published testimonials with valid text were found. Add testimonials under Reviews in WordPress Admin.',
								'chairforce'
							)}
						</Notice>
					)}
				/>
			</Disabled>
		</div>
	);
}
