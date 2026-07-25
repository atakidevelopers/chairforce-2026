import { CHAIRFORCE_LUCIDE_ICON_OPTIONS } from './lucide-icon-options';
/**
 * The icon picker only ever needs to add a `cf-icon-{slug}` class (plus
 * `cf-has-icon`/`cf-icon-left`/`cf-icon-right`) to the Button block - both
 * the editor preview (incl. inside its `<iframe name="editor-canvas">`)
 * and the front end render the glyph purely from CSS via that class,
 * using the Lucide icon font. No DOM/element injection is needed on
 * either side. See assets/css/button-icon-font.css (enqueued globally by
 * Chairforce\Lucide_Icons in lib/class-lucide-icons.php) for the font
 * and the `::before` rules that do the actual rendering.
 *
 * All classes added here use a `cf-` prefix (rather than generic names
 * like `has-icon`/`icon-left`) to avoid colliding with classes from other
 * plugins/themes that use similarly-generic icon-related class names.
 *
 * The picker itself (below) reuses that same font/CSS via a generic
 * `.cf-icon-preview.cf-icon-{slug}` class to show real glyph previews -
 * both for the currently selected icon and for each option in the
 * ComboboxControl's dropdown (via `__experimentalRenderItem`).
 */

const { __ } = wp.i18n;
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { Fragment, createElement } = wp.element;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { PanelBody, SelectControl, ComboboxControl } = wp.components;

const isCoreButtonBlock = ( blockName ) => blockName === 'core/button';

const getIconClassNames = ( attributes = {} ) => {
	if ( ! attributes.chairforceIcon ) {
		return '';
	}

	const iconPosition = attributes.chairforceIconPosition === 'right' ? 'cf-icon-right' : 'cf-icon-left';

	return [ 'cf-has-icon', iconPosition, `cf-icon-${ attributes.chairforceIcon }` ].join( ' ' );
};

const renderIconPreviewSwatch = ( iconSlug ) => {
	if ( ! iconSlug ) {
		return createElement( 'span', { className: 'cf-icon-picker__preview-empty' } );
	}

	return createElement( 'span', {
		className: `cf-icon-preview cf-icon-${ iconSlug }`,
		'aria-hidden': 'true'
	} );
};

const renderIconComboboxOption = ( { item } ) => createElement(
	'span',
	{ className: 'cf-icon-picker__option' },
	renderIconPreviewSwatch( item.value ),
	createElement( 'span', null, item.label )
);

addFilter(
	'blocks.registerBlockType',
	'chairforce/button-icon-attributes',
	( settings, blockName ) => {
		if ( ! isCoreButtonBlock( blockName ) ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				chairforceIcon: {
					type: 'string',
					default: ''
				},
				chairforceIconPosition: {
					type: 'string',
					default: 'left'
				}
			}
		};
	}
);

const withChairforceButtonIconControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! isCoreButtonBlock( props.name ) ) {
			return createElement( BlockEdit, props );
		}

		const { attributes, setAttributes } = props;
		const iconOptions = CHAIRFORCE_LUCIDE_ICON_OPTIONS.map( ( icon ) => ( {
			value: icon.slug,
			label: icon.label
		} ) );

		return createElement(
			Fragment,
			null,
			createElement( BlockEdit, props ),
			createElement(
				InspectorControls,
				null,
				createElement(
					PanelBody,
					{
						title: __( 'Button Icon', 'chairforce' ),
						initialOpen: false
					},
					createElement(
						'div',
						{ className: 'cf-icon-picker' },
						createElement(
							'span',
							{ className: 'cf-icon-picker__preview' },
							renderIconPreviewSwatch( attributes.chairforceIcon )
						),
						createElement( ComboboxControl, {
							__next40pxDefaultSize: true,
							className: 'cf-icon-picker__combobox',
							label: __( 'Icon', 'chairforce' ),
							placeholder: __( 'Search icons…', 'chairforce' ),
							value: attributes.chairforceIcon || null,
							options: iconOptions,
							__experimentalRenderItem: renderIconComboboxOption,
							onChange: ( iconSlug ) => {
								const nextAttributes = { chairforceIcon: iconSlug || '' };

								if ( iconSlug && ! attributes.chairforceIconPosition ) {
									nextAttributes.chairforceIconPosition = 'left';
								}

								setAttributes( nextAttributes );
							}
						} )
					),
					createElement( SelectControl, {
						label: __( 'Icon Position', 'chairforce' ),
						value: attributes.chairforceIconPosition || 'left',
						help: __( 'Default icon position is left.', 'chairforce' ),
						options: [
							{ value: 'left', label: __( 'Left', 'chairforce' ) },
							{ value: 'right', label: __( 'Right', 'chairforce' ) }
						],
						disabled: ! attributes.chairforceIcon,
						onChange: ( nextPosition ) => {
							setAttributes( {
								chairforceIconPosition: nextPosition || 'left'
							} );
						}
					} )
				)
			)
		);
	};
}, 'withChairforceButtonIconControls' );

addFilter(
	'editor.BlockEdit',
	'chairforce/button-icon-controls',
	withChairforceButtonIconControls
);

const withChairforceButtonBlockListClasses = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		if ( ! isCoreButtonBlock( props.name ) ) {
			return createElement( BlockListBlock, props );
		}

		const iconClassNames = getIconClassNames( props.attributes );
		const className = [ props.className, iconClassNames ].filter( Boolean ).join( ' ' );

		return createElement( BlockListBlock, { ...props, className } );
	};
}, 'withChairforceButtonBlockListClasses' );

addFilter(
	'editor.BlockListBlock',
	'chairforce/button-icon-editor-classes',
	withChairforceButtonBlockListClasses
);

addFilter(
	'blocks.getSaveContent.extraProps',
	'chairforce/button-icon-save-classes',
	( extraProps, blockType, attributes ) => {
		if ( ! isCoreButtonBlock( blockType.name ) ) {
			return extraProps;
		}

		const iconClassNames = getIconClassNames( attributes );

		if ( ! iconClassNames ) {
			return extraProps;
		}

		return {
			...extraProps,
			className: [ extraProps.className, iconClassNames ].filter( Boolean ).join( ' ' )
		};
	}
);
