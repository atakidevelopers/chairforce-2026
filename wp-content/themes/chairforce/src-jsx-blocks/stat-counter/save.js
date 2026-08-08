import { useBlockProps, RichText } from '@wordpress/block-editor';

import { formatNumber } from './utils';

export default function save( { attributes } ) {
	const { prefix, number, numberSeparator, suffix, label, animationDuration } =
		attributes;

	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<div className="wp-block-chairforce-stat-counter__number-row">
				{ prefix && (
					<RichText.Content
						tagName="span"
						className="wp-block-chairforce-stat-counter__prefix"
						value={ prefix }
					/>
				) }
				<span
					className="wp-block-chairforce-stat-counter__number"
					data-counter-target={ number }
					data-counter-separator={ numberSeparator }
					data-counter-duration={ animationDuration }
				>
					{ formatNumber( number, numberSeparator ) }
				</span>
				{ suffix && (
					<RichText.Content
						tagName="span"
						className="wp-block-chairforce-stat-counter__suffix"
						value={ suffix }
					/>
				) }
			</div>
			{ label && (
				<RichText.Content
					tagName="p"
					className="wp-block-chairforce-stat-counter__label"
					value={ label }
				/>
			) }
		</div>
	);
}
