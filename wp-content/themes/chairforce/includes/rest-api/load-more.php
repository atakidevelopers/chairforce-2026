<?php
/**
 * REST: Load More product cards for WooCommerce Product Collection (page 1).
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
					'validate_callback' => static function ( $value ) {
						return is_numeric( $value ) && (int) $value >= 2;
					},
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
			],
		]
	);

}

add_action( 'rest_api_init', 'chairforce_register_load_more_route' );

/**
 * Load More — return server-rendered product-template `<li>` HTML.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error
 */
function chairforce_rest_load_more( \WP_REST_Request $request ) {

	$page = max( 2, (int) $request->get_param( 'page' ) );

	$client_vars = [];

	$query_vars_raw = $request->get_param( 'query_vars' );

	if ( is_string( $query_vars_raw ) && '' !== $query_vars_raw ) {
		$decoded = json_decode( $query_vars_raw, true );

		if ( is_array( $decoded ) ) {
			$client_vars = chairforce_sanitize_load_more_query_vars( $decoded );
		}
	}

	$orderby = $request->get_param( 'orderby' );
	$order   = $request->get_param( 'order' );

	if ( is_string( $orderby ) && '' !== $orderby ) {
		$client_vars['orderby'] = sanitize_key( $orderby );
	}

	if ( is_string( $order ) && in_array( strtoupper( $order ), [ 'ASC', 'DESC' ], true ) ) {
		$client_vars['order'] = strtoupper( $order );
	}

	$query_args = chairforce_build_load_more_query_args( $client_vars, $page );
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

	$per_page   = chairforce_get_loop_shop_per_page();
	$query      = new \WP_Query( $query_args );
	$post_type  = chairforce_get_load_more_post_type_from_vars( $query_args );

	$html = chairforce_render_product_template_items(
		$query,
		chairforce_get_load_more_block_context( $post_type )
	);

	$max_pages = chairforce_get_load_more_max_pages( (int) $query->found_posts, $per_page );

	return new \WP_REST_Response(
		[
			'html'         => $html,
			'nextPage'     => $page + 1,
			'hasMore'      => $page < $max_pages,
			'maxPages'     => $max_pages,
			'perPage'      => $per_page,
			'offset'       => ( $page - 1 ) * $per_page,
			'total'        => (int) $query->found_posts,
			'viewingCount' => min( $page * $per_page, (int) $query->found_posts ),
		],
		200
	);
}
