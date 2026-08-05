<?php
/**
 * Single-product tab content helpers (Phase 3g).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read formatted WYSIWYG product meta (ACF or raw post meta).
 *
 * @param int    $product_id Product post ID.
 * @param string $meta_key   Meta key (`dimensions`, `care`, `additional_information`).
 * @return string Safe HTML, or empty string when unset.
 */
function chairforce_get_product_wysiwyg_meta_html( int $product_id, string $meta_key ): string {

	if ( $product_id <= 0 || '' === $meta_key ) {
		return '';
	}

	$raw = '';

	if ( function_exists( 'get_field' ) ) {
		$field_value = get_field( $meta_key, $product_id );
		if ( is_string( $field_value ) ) {
			$raw = $field_value;
		}
	}

	if ( '' === trim( $raw ) ) {
		$raw = (string) get_post_meta( $product_id, $meta_key, true );
	}

	$raw = trim( $raw );

	if ( '' === $raw ) {
		return '';
	}

	if ( $raw === wp_strip_all_tags( $raw ) ) {
		return nl2br( esc_html( $raw ) );
	}

	return wp_kses_post( $raw );
}

/**
 * Read formatted product Dimensions meta.
 *
 * @param int $product_id Product post ID.
 * @return string Safe HTML, or empty string when unset.
 */
function chairforce_get_product_dimensions_html( int $product_id ): string {
	return chairforce_get_product_wysiwyg_meta_html( $product_id, 'dimensions' );
}

/**
 * Read formatted product Care meta.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_care_html( int $product_id ): string {
	return chairforce_get_product_wysiwyg_meta_html( $product_id, 'care' );
}

/**
 * Read formatted product Additional Information meta.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_additional_information_html( int $product_id ): string {
	return chairforce_get_product_wysiwyg_meta_html( $product_id, 'additional_information' );
}

/**
 * Global delivery copy from theme options.
 *
 * @return string Safe HTML, or empty string when unset.
 */
function chairforce_get_product_delivery_information_html(): string {

	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$raw = get_field( 'cf_product_delivery_information', 'option' );

	if ( ! is_string( $raw ) ) {
		return '';
	}

	return chairforce_get_product_wysiwyg_meta_html_from_raw( $raw );
}

/**
 * Global product info copy from theme options.
 *
 * @return string Safe HTML, or empty string when unset.
 */
function chairforce_get_product_info_html(): string {

	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$raw = get_field( 'cf_product_info', 'option' );

	if ( ! is_string( $raw ) ) {
		return '';
	}

	return chairforce_get_product_wysiwyg_meta_html_from_raw( $raw );
}

/**
 * Format a raw WYSIWYG string for frontend output.
 *
 * @param string $raw Raw field value.
 * @return string
 */
function chairforce_get_product_wysiwyg_meta_html_from_raw( string $raw ): string {

	$raw = trim( $raw );

	if ( '' === $raw ) {
		return '';
	}

	if ( $raw === wp_strip_all_tags( $raw ) ) {
		return nl2br( esc_html( $raw ) );
	}

	return wp_kses_post( $raw );
}

/**
 * Get linked spare-part product IDs from legacy `parts` post meta.
 *
 * @param int $product_id Product post ID.
 * @return int[]
 */
function chairforce_get_product_parts_ids( int $product_id ): array {

	if ( $product_id <= 0 ) {
		return [];
	}

	$value = get_post_meta( $product_id, 'parts', true );

	if ( ! is_array( $value ) ) {
		$value = maybe_unserialize( $value );
	}

	if ( ! is_array( $value ) ) {
		return [];
	}

	return array_values(
		array_filter(
			array_map( 'absint', $value )
		)
	);
}

/**
 * Render the Parts tab grid for a product.
 *
 * @param int $product_id Parent product post ID.
 * @return void
 */
function chairforce_render_product_parts_tab( int $product_id ): void {

	$part_ids = chairforce_get_product_parts_ids( $product_id );

	if ( empty( $part_ids ) ) {
		return;
	}

	$markup = chairforce_get_hand_picked_product_collection_blocks_markup(
		$part_ids,
		5,
		[
			'class_name' => 'cf-product-parts'
		]
	);

	if ( '' === trim( $markup ) ) {
		return;
	}

	ob_start();
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
	echo do_blocks( $markup );
	$html = (string) ob_get_clean();

	chairforce_render_product_tab_panel( 'parts', $html );
}

/**
 * Echo a product tab panel wrapper around pre-sanitized HTML.
 *
 * @param string $slug Tab slug used for BEM modifier classes.
 * @param string $html Tab body HTML.
 * @return void
 */
function chairforce_render_product_tab_panel( string $slug, string $html ): void {

	if ( '' === trim( $html ) ) {
		return;
	}

	printf(
		'<div class="cf-product-tab cf-product-tab--%1$s">%2$s</div>',
		esc_attr( sanitize_html_class( $slug ) ),
		$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source.
	);
}
