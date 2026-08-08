import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

import { formatNumber } from './utils';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { prefix, number, numberSeparator, suffix, label, animationDuration } =
		attributes;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Counter', 'chairforce' ) }
					initialOpen
				>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Number', 'chairforce' ) }
						value={ number }
						onChange={ ( val ) =>
							setAttributes( {
								number: parseFloat( val ) || 0,
							} )
						}
						step={ 1 }
						min={ 0 }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Format with commas (e.g. 50,000)', 'chairforce' ) }
						checked={ numberSeparator === ',' }
						onChange={ ( checked ) =>
							setAttributes( {
								numberSeparator: checked ? ',' : '',
							} )
						}
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Animation duration (ms)', 'chairforce' ) }
						value={ animationDuration }
						onChange={ ( val ) =>
							setAttributes( { animationDuration: val } )
						}
						min={ 500 }
						max={ 5000 }
						step={ 100 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="wp-block-chairforce-stat-counter__number-row">
					<RichText
						tagName="span"
						className="wp-block-chairforce-stat-counter__prefix"
						value={ prefix }
						onChange={ ( val ) =>
							setAttributes( { prefix: val } )
						}
						allowedFormats={ [] }
						placeholder={ __( '$', 'chairforce' ) }
					/>
					<span className="wp-block-chairforce-stat-counter__number">
						{ formatNumber( number, numberSeparator ) }
					</span>
					<RichText
						tagName="span"
						className="wp-block-chairforce-stat-counter__suffix"
						value={ suffix }
						onChange={ ( val ) =>
							setAttributes( { suffix: val } )
						}
						allowedFormats={ [] }
						placeholder={ __( '+', 'chairforce' ) }
					/>
				</div>
				<RichText
					tagName="p"
					className="wp-block-chairforce-stat-counter__label"
					value={ label }
					onChange={ ( val ) => setAttributes( { label: val } ) }
					placeholder={ __( 'Label', 'chairforce' ) }
				/>
			</div>
		</>
	);
}
