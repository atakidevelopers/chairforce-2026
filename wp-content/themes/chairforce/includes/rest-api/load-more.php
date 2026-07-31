<?php
/**
 * REST: Load More — append product cards (page 2+ only).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'includes/load-more-functions.php' );

/**
 * Register load-more REST route.
 */
function chairforce_register_load_more_route(): void {

	register_rest_route(
		'chairforce/v1',
		'/load-more',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => 'chairforce_rest_load_more',
			'permission_callback' => '__return_true',
			'args'                => [
				'page' => [
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				],
				'query_vars' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'wp_unslash',
				],
				'orderby' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'order' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'min_price' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'max_price' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		]
	);

}

add_action( 'rest_api_init', 'chairforce_register_load_more_route' );

/**
 * Load More — append server-rendered product-template `<li>` HTML.
 *
 * Filter/sort refresh uses archive shell partial reload (`_cf_archive=shell`), not this route.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error
 */
function chairforce_rest_load_more( \WP_REST_Request $request ) {

	$page = max( 1, (int) $request->get_param( 'page' ) );

	if ( $page < 2 ) {
		return new \WP_Error(
			'chairforce_load_more_invalid_page',
			__( 'Load More requests must use page 2 or higher.', 'chairforce' ),
			[ 'status' => 400 ]
		);
	}

	$client_vars = [];

	$query_vars_raw = $request->get_param( 'query_vars' );

	if ( is_string( $query_vars_raw ) && '' !== $query_vars_raw ) {
		$decoded = json_decode( $query_vars_raw, true );

		if ( is_array( $decoded ) ) {
			$client_vars = chairforce_sanitize_load_more_query_vars( $decoded );
		}
	}

	$filter_params = chairforce_parse_catalog_filter_params_from_request( $request );

	$client_vars = chairforce_normalize_load_more_ordering( $client_vars );

	$query_args = chairforce_build_load_more_query_args( $client_vars, $page, $filter_params );
	$post_types = (array) ( $query_args['post_type'] ?? 'product' );

	foreach ( $post_types as $post_type ) {
		if ( ! post_type_exists( (string) $post_type ) ) {
			return new \WP_Error(
				'chairforce_load_more_invalid_post_type',
				__( 'The requested post type is not available.', 'chairforce' ),
				[ 'status' => 400 ]
			);
		}
	}

	$per_page = chairforce_get_loop_shop_per_page();
	$backup_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	foreach ( $filter_params as $key => $value ) {
		$_GET[ $key ] = $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$price_filter_callback = null;

	if ( function_exists( 'WC' ) && WC()->query instanceof \WC_Query ) {
		$price_filter_callback = [ WC()->query, 'price_filter_post_clauses' ];
		add_filter( 'posts_clauses', $price_filter_callback, 10, 2 );
	}

	$query = new \WP_Query( $query_args );

	if ( $price_filter_callback ) {
		remove_filter( 'posts_clauses', $price_filter_callback, 10 );
	}

	$_GET = $backup_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$post_type = chairforce_get_load_more_post_type_from_vars( $query_args );
	$html      = chairforce_render_product_template_items(
		$query,
		chairforce_get_load_more_block_context( $post_type )
	);

	$max_pages = chairforce_get_load_more_max_pages( (int) $query->found_posts, $per_page );
	$total     = (int) $query->found_posts;
	$viewing   = min( $page * $per_page, $total );

	return new \WP_REST_Response(
		[
			'html'         => $html,
			'nextPage'     => $page + 1,
			'hasMore'      => $page < $max_pages,
			'maxPages'     => $max_pages,
			'perPage'      => $per_page,
			'offset'       => ( $page - 1 ) * $per_page,
			'total'        => $total,
			'viewingCount' => $viewing,
		],
		200
	);
}
