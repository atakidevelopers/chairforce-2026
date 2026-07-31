<?php
/**
 * Product archive shell — PJAX-style partial HTML for filter/sort refresh.
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
 * Block markup for the archive product-collection template part.
 *
 * Must stay in sync with `templates/archive-product.html`.
 *
 * @return string
 */
function chairforce_get_archive_product_collection_block_markup(): string {

	return sprintf(
		'<!-- wp:template-part {"slug":"product-collection","theme":"%1$s","tagName":"div","align":"wide"} /-->',
		esc_attr( get_stylesheet() )
	);
}

/**
 * Render toolbar row (results count + catalog sort block).
 *
 * @param int $total   Total matching products.
 * @param int $viewing Products visible on page 1.
 * @return string
 */
function chairforce_render_archive_toolbar_html( int $total, int $viewing ): string {

	$results_count = chairforce_render_product_results_count_html( $total, $viewing );

	$sort_markup = function_exists( 'do_blocks' )
		? do_blocks( '<!-- wp:woocommerce/catalog-sorting /-->' )
		: '';

	if ( '' === $results_count && '' === $sort_markup ) {
		return '';
	}

	return sprintf(
		'<div class="wp-block-group alignwide is-content-justification-space-between is-nowrap is-layout-flex wp-block-group-is-layout-flex cf-shop-archive-shell__toolbar">%1$s%2$s</div>',
		$results_count,
		$sort_markup
	);
}

/**
 * Render product grid + Load More for the current main query (page 1).
 *
 * Uses the same product-collection template part as SSR so block classes,
 * interactivity directives, and responsive grid CSS stay in sync after shell swap.
 *
 * @param \WP_Query $query Executed product query (global main query must match).
 * @return string
 */
function chairforce_render_archive_product_collection_html( \WP_Query $query ): string {

	unset( $query );

	if ( ! function_exists( 'do_blocks' ) ) {
		return '';
	}

	return do_blocks( chairforce_get_archive_product_collection_block_markup() );
}

/**
 * Render the full archive shell (filters + toolbar + grid + load more).
 *
 * Uses the global main query so filters, counts, and pagination match SSR.
 *
 * @return string
 */
function chairforce_render_shop_archive_shell_html(): string {

	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query ) {
		return '';
	}

	$filter_groups = chairforce_get_archive_filter_groups();
	$per_page      = chairforce_get_loop_shop_per_page();
	$total         = (int) $wp_query->found_posts;
	$viewing       = min( $per_page, $total );

	ob_start();
	?>
	<div class="cf-shop-archive-shell">
		<?php echo chairforce_render_product_filters_html( $filter_groups ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo chairforce_render_archive_toolbar_html( $total, $viewing ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo chairforce_render_archive_product_collection_html( $wp_query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
	return (string) ob_get_clean();
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
		$query_vars            = $wp_query->query_vars;
		$query_vars['paged']   = 1;
		$query_vars['page']    = 1;
		$query_vars['offset']  = 0;
		$query_vars['nopaging'] = false;

		$wp_query = new \WP_Query( $query_vars ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) );

	echo chairforce_render_shop_archive_shell_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
