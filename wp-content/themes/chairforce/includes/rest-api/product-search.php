<?php
/**
 * REST: AJAX product search for the header search field.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register product search REST route.
 */
function chairforce_register_product_search_route(): void {

	register_rest_route(
		'chairforce/v1',
		'/product-search',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => 'chairforce_rest_product_search',
			'permission_callback' => '__return_true',
			'args'                => [
				's' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		]
	);

}

add_action( 'rest_api_init', 'chairforce_register_product_search_route' );

/**
 * Search WooCommerce products for the header dropdown.
 *
 * @param \WP_REST_Request $request Request object.
 *
 * @return \WP_REST_Response
 */
function chairforce_rest_product_search( \WP_REST_Request $request ): \WP_REST_Response {

	$term = trim( (string) $request->get_param( 's' ) );

	if ( strlen( $term ) < 2 ) {
		return new \WP_REST_Response( [ 'items' => [] ], 200 );
	}

	if ( ! post_type_exists( 'product' ) ) {
		return new \WP_REST_Response( [ 'items' => [] ], 200 );
	}

	$query = new \WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => 8,
			'no_found_rows'  => true,
		]
	);

	$items = [];

	foreach ( $query->posts as $post ) {
		$price_html = '';

		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );

			if ( ! $product ) {
				continue;
			}

			$price_html = $product->get_price_html();
		}

		$thumbnail = get_the_post_thumbnail_url( $post->ID, 'woocommerce_thumbnail' );

		$items[] = [
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'url'       => get_permalink( $post ),
			'thumbnail' => $thumbnail ? $thumbnail : '',
			'price'     => $price_html,
		];
	}

	return new \WP_REST_Response( [ 'items' => $items ], 200 );

}
