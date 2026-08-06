import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'cf-product-faqs cf-product-faqs-editor',
	} );

	return (
		<div { ...blockProps }>
			<div className="cf-product-faqs__list" aria-hidden="true">
				<div className="cf-product-faqs__item is-open">
					<button
						type="button"
						className="cf-product-faqs__trigger"
						tabIndex={ -1 }
					>
						<span className="cf-product-faqs__question">
							{ __( 'How long does delivery take?', 'chairforce' ) }
						</span>
						<span className="cf-product-faqs__icon" aria-hidden="true" />
					</button>
					<div className="cf-product-faqs__panel">
						<div className="cf-product-faqs__answer">
							<p>
								{ __(
									'FAQ accordion renders from FAQ Configurations and product settings on the frontend.',
									'chairforce'
								) }
							</p>
						</div>
					</div>
				</div>
				<div className="cf-product-faqs__item">
					<button
						type="button"
						className="cf-product-faqs__trigger"
						tabIndex={ -1 }
					>
						<span className="cf-product-faqs__question">
							{ __( 'Is assembly required?', 'chairforce' ) }
						</span>
						<span className="cf-product-faqs__icon" aria-hidden="true" />
					</button>
					<div className="cf-product-faqs__panel" hidden>
						<div className="cf-product-faqs__answer">
							<p>{ __( 'Preview answer.', 'chairforce' ) }</p>
						</div>
					</div>
				</div>
			</div>
			<p className="cf-product-faqs-editor__note">
				{ __(
					'Outputs accordion items or “No FAQs Found” only. Add section heading and wrapper styling in the template.',
					'chairforce'
				) }
			</p>
		</div>
	);
}
