const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { useEffect, createElement } = wp.element;

const SPLIT_SECTION_CLASS = 'is-style-split-section';
const CONTENT_GAP_VAR = '--cf-split-section-content-gap';

/**
 * @param {string|Object|null|undefined} blockGap Block gap attribute.
 * @return {string|null}
 */
function resolveBlockGapCss( blockGap ) {
	if ( ! blockGap ) {
		return null;
	}

	const getGapCSSValue = wp.blockEditor?.__experimentalGetGapCSSValue;

	if ( getGapCSSValue ) {
		return getGapCSSValue( blockGap );
	}

	if (
		typeof blockGap === 'string' &&
		blockGap.includes( 'var:preset|spacing|' )
	) {
		const slug = blockGap.replace( 'var:preset|spacing|', '' );
		return `var(--wp--preset--spacing--${ slug })`;
	}

	return typeof blockGap === 'string' ? blockGap : null;
}

const withSplitSectionContentGap = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, clientId } = props;
		const isSplitSection =
			name === 'core/media-text' &&
			attributes?.className?.includes( SPLIT_SECTION_CLASS );
		const gapCss = resolveBlockGapCss(
			attributes?.style?.spacing?.blockGap
		);

		useEffect( () => {
			if ( ! isSplitSection ) {
				return undefined;
			}

			const root = document.querySelector(
				`[data-block="${ clientId }"]`
			);
			const content = root?.querySelector(
				'.wp-block-media-text__content'
			);

			if ( ! content ) {
				return undefined;
			}

			if ( gapCss ) {
				content.style.setProperty( CONTENT_GAP_VAR, gapCss );
			} else {
				content.style.removeProperty( CONTENT_GAP_VAR );
			}

			return () => {
				content.style.removeProperty( CONTENT_GAP_VAR );
			};
		}, [ isSplitSection, gapCss, clientId ] );

		return createElement( BlockEdit, props );
	};
}, 'withSplitSectionContentGap' );

addFilter(
	'editor.BlockEdit',
	'chairforce/media-text-split-section-content-gap',
	withSplitSectionContentGap
);
