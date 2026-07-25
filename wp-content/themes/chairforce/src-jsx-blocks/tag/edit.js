import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';

import './editor.scss';

import {
	TAG_ELEMENTS,
	TAG_LABEL_CLASS,
	sanitizeTagName,
} from './constants';

export default function Edit( { attributes, setAttributes } ) {
	const { content, tagName, align } = attributes;
	const safeTagName = sanitizeTagName( tagName );
	const blockProps = useBlockProps();

	return (
		<>
			<BlockControls group="block">
				<AlignmentToolbar
					value={ align }
					onChange={ ( nextAlign ) =>
						setAttributes( { align: nextAlign } )
					}
				/>
				<ToolbarGroup
					label={ __( 'HTML element', 'chairforce' ) }
					className="cf-tag-element-toolbar"
				>
					{ TAG_ELEMENTS.map( ( element ) => (
						<ToolbarButton
							key={ element.value }
							isPressed={ safeTagName === element.value }
							onClick={ () =>
								setAttributes( {
									tagName: sanitizeTagName( element.value ),
								} )
							}
							label={ element.label }
							text={ element.label }
							showTooltip
						/>
					) ) }
				</ToolbarGroup>
			</BlockControls>
			<div { ...blockProps }>
				<RichText
					identifier="content"
					tagName={ safeTagName }
					className={ TAG_LABEL_CLASS }
					value={ content }
					onChange={ ( nextContent ) =>
						setAttributes( { content: nextContent } )
					}
					placeholder={ __( 'Tag label…', 'chairforce' ) }
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
