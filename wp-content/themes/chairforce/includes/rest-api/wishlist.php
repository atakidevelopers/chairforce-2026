<?php
/**
 * REST: Logged-in customer wishlist.
 *
 * @package Chairforce
 */

use Chairforce\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register wishlist REST routes.
 */
function chairforce_register_wishlist_routes(): void {

	register_rest_route(
		'chairforce/v1',
		'/wishlist/toggle',
		[
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => 'chairforce_rest_wishlist_toggle',
			'permission_callback' => 'chairforce_rest_wishlist_permission',
			'args'                => [
				'productId' => [
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]
	);

	register_rest_route(
		'chairforce/v1',
		'/wishlist',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => 'chairforce_rest_wishlist_list',
			'permission_callback' => 'chairforce_rest_wishlist_permission',
		]
	);

	register_rest_route(
		'chairforce/v1',
		'/wishlist/status',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => 'chairforce_rest_wishlist_status',
			'permission_callback' => 'chairforce_rest_wishlist_permission',
			'args'                => [
				'ids' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'chairforce_rest_wishlist_sanitize_ids_param',
					'validate_callback' => 'chairforce_rest_wishlist_validate_ids_param',
				],
			],
		]
	);

}

add_action( 'rest_api_init', 'chairforce_register_wishlist_routes' );

/**
 * REST permission: wishlist enabled + logged-in user.
 *
 * @return true|\WP_Error
 */
function chairforce_rest_wishlist_permission() {
	if ( ! chairforce_is_wishlist_enabled() ) {
		return new \WP_Error(
			'chairforce_wishlist_disabled',
			__( 'Wishlist is disabled.', 'chairforce' ),
			[ 'status' => 403 ]
		);
	}

	if ( ! is_user_logged_in() ) {
		return new \WP_Error(
			'chairforce_wishlist_login_required',
			__( 'You must be logged in to use the wishlist.', 'chairforce' ),
			[ 'status' => 401 ]
		);
	}

	return true;
}

/**
 * @param mixed $value Raw ids param.
 */
function chairforce_rest_wishlist_sanitize_ids_param( $value ): string {
	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return '';
	}

	return sanitize_text_field( (string) $value );
}

/**
 * @param mixed           $value   Param value.
 * @param \WP_REST_Request $request Request.
 * @param string          $param   Param name.
 */
function chairforce_rest_wishlist_validate_ids_param( $value, \WP_REST_Request $request, string $param ): bool {
	$ids = chairforce_rest_wishlist_parse_ids_param( (string) $value );

	return [] !== $ids && count( $ids ) <= Wishlist::STATUS_IDS_MAX;
}

/**
 * @param string $raw Comma-separated product IDs.
 * @return int[]
 */
function chairforce_rest_wishlist_parse_ids_param( string $raw ): array {
	if ( '' === trim( $raw ) ) {
		return [];
	}

	$parts = array_map( 'trim', explode( ',', $raw ) );
	$ids   = [];

	foreach ( $parts as $part ) {
		if ( '' === $part || ! ctype_digit( $part ) ) {
			continue;
		}

		$id = absint( $part );

		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Toggle a product in the current user's wishlist.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error
 */
function chairforce_rest_wishlist_toggle( \WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$product_id = absint( $request->get_param( 'productId' ) );

	if ( $product_id <= 0 ) {
		return new \WP_Error(
			'chairforce_wishlist_invalid_product',
			__( 'Invalid product.', 'chairforce' ),
			[ 'status' => 400 ]
		);
	}

	if ( Wishlist::is_in_wishlist( $user_id, $product_id ) ) {
		Wishlist::remove_item( $user_id, $product_id );

		return new \WP_REST_Response(
			[
				'productId'  => $product_id,
				'inWishlist' => false,
				'count'      => Wishlist::get_count( $user_id ),
			],
			200
		);
	}

	if ( ! Wishlist::is_valid_product( $product_id ) ) {
		return new \WP_Error(
			'chairforce_wishlist_invalid_product',
			__( 'Invalid product.', 'chairforce' ),
			[ 'status' => 400 ]
		);
	}

	$result = Wishlist::toggle_item( $user_id, $product_id );

	return new \WP_REST_Response(
		[
			'productId'  => $product_id,
			'inWishlist' => (bool) $result['in_wishlist'],
			'count'      => (int) $result['count'],
		],
		200
	);
}

/**
 * List product IDs in the current user's wishlist.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response
 */
function chairforce_rest_wishlist_list( \WP_REST_Request $request ): \WP_REST_Response {
	$user_id     = get_current_user_id();
	$product_ids = Wishlist::get_product_ids( $user_id );

	return new \WP_REST_Response(
		[
			'productIds' => $product_ids,
			'count'      => count( $product_ids ),
		],
		200
	);
}

/**
 * Batch wishlist membership for product IDs on the current page.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error
 */
function chairforce_rest_wishlist_status( \WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$ids     = chairforce_rest_wishlist_parse_ids_param( (string) $request->get_param( 'ids' ) );

	if ( [] === $ids ) {
		return new \WP_Error(
			'chairforce_wishlist_invalid_ids',
			__( 'No valid product IDs were provided.', 'chairforce' ),
			[ 'status' => 400 ]
		);
	}

	if ( count( $ids ) > Wishlist::STATUS_IDS_MAX ) {
		return new \WP_Error(
			'chairforce_wishlist_too_many_ids',
			__( 'Too many product IDs in one request.', 'chairforce' ),
			[ 'status' => 400 ]
		);
	}

	$status_map = Wishlist::get_status_map( $user_id, $ids );
	$response   = [];

	foreach ( $status_map as $product_id => $in_wishlist ) {
		$response[ (string) $product_id ] = (bool) $in_wishlist;
	}

	return new \WP_REST_Response( $response, 200 );
}
