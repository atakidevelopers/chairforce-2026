<?php
/**
 * Shop breadcrumb — list markup aligned with core/breadcrumbs.
 *
 * Overrides WooCommerce's flat delimiter output for classic
 * `woocommerce_breadcrumb()` calls (non-block contexts).
 *
 * @package WooCommerce\Templates
 * @version 2.3.0
 * @see woocommerce_breadcrumb()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $breadcrumb ) ) {
	return;
}

if ( ! function_exists( 'chairforce_render_breadcrumb_list_html' ) ) {
	// Fallback to flat output if helpers are unavailable.
	echo $wrap_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	foreach ( $breadcrumb as $key => $crumb ) {
		echo $before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $crumb[1] ) && count( $breadcrumb ) !== $key + 1 ) {
			echo '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>';
		} else {
			echo esc_html( $crumb[0] );
		}

		echo $after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( count( $breadcrumb ) !== $key + 1 ) {
			echo $delimiter; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	echo $wrap_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$list_nav = chairforce_render_breadcrumb_list_html(
	$breadcrumb,
	[
		'nav_class' => 'woocommerce-breadcrumb',
		'label'     => __( 'Breadcrumb', 'woocommerce' ),
	]
);

if ( '' === $list_nav ) {
	return;
}

echo $list_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
