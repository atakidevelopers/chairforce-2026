<?php
/**
 * Product archive shell — PJAX-style partial HTML for filter/sort refresh.
 *
 * SSR and AJAX both render the same `parts/shop-archive-shell.html` template part
 * so Site Editor changes stay in sync after shell swap.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'includes/load-more-functions.php' );

/**
 * Query arg that requests the archive shell fragment instead of a full page.
 */
function chairforce_get_archive_shell_query_arg(): string {
	return '_cf_archive';
}

/**
 * Whether the current request wants the archive shell HTML fragment.
 */
function chairforce_is_archive_shell_request(): bool {

	if ( ! isset( $_GET[ chairforce_get_archive_shell_query_arg() ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	if ( 'shell' !== sanitize_key( wp_unslash( (string) $_GET[ chairforce_get_archive_shell_query_arg() ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	$requested_with = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		? strtolower( (string) $_SERVER['HTTP_X_REQUESTED_WITH'] )
		: '';

	return 'xmlhttprequest' === $requested_with;
}

/**
 * Block markup for the shop archive shell template part.
 *
 * Must stay in sync with `templates/archive-product.html`.
 *
 * @return string
 */
function chairforce_get_shop_archive_shell_block_markup(): string {

	return sprintf(
		'<!-- wp:template-part {"slug":"shop-archive-shell","theme":"%1$s","tagName":"div"} /-->',
		esc_attr( get_stylesheet() )
	);
}

/**
 * Render the archive shell (filters + toolbar + grid + load more).
 *
 * Uses the global main query so filters, counts, and pagination match SSR.
 *
 * @return string
 */
function chairforce_render_shop_archive_shell_html(): string {

	if ( ! function_exists( 'do_blocks' ) ) {
		return '';
	}

	$query_arg   = chairforce_get_archive_shell_query_arg();
	$had_shell   = isset( $_GET[ $query_arg ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$shell_value = $had_shell ? $_GET[ $query_arg ] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $had_shell ) {
		unset( $_GET[ $query_arg ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$html = do_blocks( chairforce_get_shop_archive_shell_block_markup() );

	if ( $had_shell ) {
		$_GET[ $query_arg ] = $shell_value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return $html;
}

/**
 * Output archive shell fragment and exit (Woodmart PJAX equivalent).
 */
function chairforce_maybe_render_archive_shell_fragment(): void {

	if ( ! chairforce_is_archive_shell_request() ) {
		return;
	}

	if ( ! chairforce_is_product_filter_archive() ) {
		status_header( 404 );
		exit;
	}

	global $wp_query;

	if ( $wp_query instanceof \WP_Query && $wp_query->is_paged() ) {
		$query_vars             = $wp_query->query_vars;
		$query_vars['paged']    = 1;
		$query_vars['page']     = 1;
		$query_vars['offset']   = 0;
		$query_vars['nopaging'] = false;

		$wp_query = new \WP_Query( $query_vars ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) );

	echo chairforce_render_shop_archive_shell_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
