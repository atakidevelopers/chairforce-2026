import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

const PLACEHOLDER_ITEMS = [
	__( 'Stackable', 'chairforce' ),
	__( 'UV Resistant', 'chairforce' ),
	__( 'Made in Australia', 'chairforce' ),
];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'cf-product-features cf-product-features-editor',
	} );

	return (
		<div { ...blockProps }>
			<ul className="cf-product-features__list" aria-hidden="true">
				{ PLACEHOLDER_ITEMS.map( ( label ) => (
					<li key={ label } className="cf-product-features__item">
						<span className="cf-product-features__icon">
							<span className="cf-product-features__placeholder" />
						</span>
						<span className="cf-product-features__label">
							{ label }
						</span>
					</li>
				) ) }
			</ul>
			<p className="cf-product-features-editor__note">
				{ __(
					'Feature icons render from the product’s assigned Features taxonomy on the frontend.',
					'chairforce'
				) }
			</p>
		</div>
	);
}
