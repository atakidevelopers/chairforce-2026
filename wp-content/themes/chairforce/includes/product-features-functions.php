<?php
/**
 * Product feature taxonomy helpers (Phase 3n).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature taxonomy slug.
 */
const CHAIRFORCE_FEATURE_TAXONOMY = 'feature';

/**
 * ACF field key for the feature term thumbnail (use key, not bare name — see PROGRESS 3c).
 */
const CHAIRFORCE_FEATURE_THUMBNAIL_FIELD_KEY = 'field_feature_thumbnail';

/**
 * Get assigned feature terms for a product, in term order.
 *
 * @param int $product_id Product post ID.
 * @return WP_Term[]
 */
function chairforce_get_product_feature_terms( int $product_id ): array {

	if ( $product_id <= 0 || ! taxonomy_exists( CHAIRFORCE_FEATURE_TAXONOMY ) ) {
		return [];
	}

	$terms = wp_get_post_terms(
		$product_id,
		CHAIRFORCE_FEATURE_TAXONOMY,
		[
			'orderby' => 'name',
			'order'   => 'ASC',
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	return $terms;
}

/**
 * Resolve a feature term's thumbnail attachment ID.
 *
 * @param int $term_id Feature term ID.
 * @return int Attachment ID, or 0 when unset.
 */
function chairforce_get_feature_term_thumbnail_id( int $term_id ): int {

	if ( $term_id <= 0 ) {
		return 0;
	}

	$attachment_id = 0;

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( CHAIRFORCE_FEATURE_THUMBNAIL_FIELD_KEY, CHAIRFORCE_FEATURE_TAXONOMY . '_' . $term_id );

		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );
		} elseif ( is_array( $value ) && isset( $value['ID'] ) ) {
			$attachment_id = absint( $value['ID'] );
		} elseif ( is_array( $value ) && isset( $value['id'] ) ) {
			$attachment_id = absint( $value['id'] );
		}
	}

	if ( $attachment_id ) {
		return $attachment_id;
	}

	$meta = get_term_meta( $term_id, 'thumbnail', true );

	if ( is_numeric( $meta ) ) {
		return absint( $meta );
	}

	if ( is_array( $meta ) ) {
		if ( isset( $meta['id'] ) ) {
			return absint( $meta['id'] );
		}
		if ( isset( $meta['ID'] ) ) {
			return absint( $meta['ID'] );
		}
	}

	return 0;
}

/**
 * Build HTML for a product's feature icon row.
 *
 * @param int $product_id Product post ID.
 * @return string Safe HTML, or empty string when no renderable features.
 */
function chairforce_get_product_features_html( int $product_id ): string {

	$terms = chairforce_get_product_feature_terms( $product_id );

	if ( empty( $terms ) ) {
		return '';
	}

	$items_html = '';

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$attachment_id = chairforce_get_feature_term_thumbnail_id( (int) $term->term_id );

		if ( ! $attachment_id ) {
			continue;
		}

		$label = $term->name;
		$image = wp_get_attachment_image(
			$attachment_id,
			'thumbnail',
			false,
			[
				'class'   => 'cf-product-features__image',
				'alt'     => $label,
				'loading' => 'lazy',
				'decoding'=> 'async',
			]
		);

		if ( '' === $image ) {
			continue;
		}

		$items_html .= sprintf(
			'<li class="cf-product-features__item"><span class="cf-product-features__icon" aria-hidden="true">%1$s</span><span class="cf-product-features__label">%2$s</span></li>',
			$image, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by wp_get_attachment_image().
			esc_html( $label )
		);
	}

	if ( '' === $items_html ) {
		return '';
	}

	return sprintf(
		'<ul class="cf-product-features__list">%s</ul>',
		$items_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped fragments.
	);
}

/**
 * Serialized block markup for the product features row (do_blocks / template parity).
 *
 * @return string
 */
function chairforce_get_product_features_blocks_markup(): string {

	return '<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:chairforce/product-features /--></div>
<!-- /wp:group -->';
}

/**
 * Render the product features block group after description tab content.
 *
 * @return void
 */
function chairforce_render_product_features_blocks(): void {

	if ( ! function_exists( 'do_blocks' ) ) {
		return;
	}

	$markup = chairforce_get_product_features_blocks_markup();

	if ( '' === trim( $markup ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
	echo do_blocks( $markup );
}
