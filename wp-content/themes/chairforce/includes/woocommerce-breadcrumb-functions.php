<?php
/**
 * WooCommerce breadcrumb list markup helpers.
 *
 * Normalises Store Breadcrumbs / classic breadcrumb output to core-like <ol><li>
 * so theme breadcrumb Sass (chevron separators) applies everywhere.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the current WooCommerce breadcrumb trail.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function chairforce_get_wc_breadcrumb_crumbs(): array {
	if ( ! function_exists( 'WC' ) || ! WC()->breadcrumb instanceof \WC_Breadcrumb ) {
		return [];
	}

	$crumbs = WC()->breadcrumb->generate();

	return is_array( $crumbs ) ? $crumbs : [];
}

/**
 * Render breadcrumb crumbs as an accessible ordered list inside a nav element.
 *
 * @param array<int, array{0: string, 1: string}> $crumbs   Breadcrumb trail.
 * @param array<string, string>                   $args {
 *     @type string $nav_class Nav element class attribute value.
 *     @type string $label     aria-label for the nav.
 * }
 * @return string
 */
function chairforce_render_breadcrumb_list_html( array $crumbs, array $args = [] ): string {
	if ( empty( $crumbs ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		[
			'nav_class' => 'woocommerce-breadcrumb',
			'label'     => __( 'Breadcrumb', 'woocommerce' ),
		]
	);

	$items_html = [];
	$last_index = count( $crumbs ) - 1;

	foreach ( $crumbs as $index => $crumb ) {
		$label = isset( $crumb[0] ) ? wp_strip_all_tags( (string) $crumb[0] ) : '';
		$url   = isset( $crumb[1] ) ? (string) $crumb[1] : '';

		if ( '' === $label ) {
			continue;
		}

		$is_current = ( $index === $last_index ) || '' === $url;

		if ( $is_current ) {
			$items_html[] = sprintf(
				'<li><span aria-current="page">%s</span></li>',
				esc_html( $label )
			);
			continue;
		}

		$items_html[] = sprintf(
			'<li><a href="%s">%s</a></li>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	if ( empty( $items_html ) ) {
		return '';
	}

	return sprintf(
		'<nav class="%s" aria-label="%s"><ol>%s</ol></nav>',
		esc_attr( $args['nav_class'] ),
		esc_attr( $args['label'] ),
		implode( '', $items_html )
	);
}
