<?php
/**
 * REST: Quick View product markup for archive card popups.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register quick view REST route.
 */
function chairforce_register_quick_view_route(): void {

	register_rest_route(
		'chairforce/v1',
		'/quick-view/(?P<id>\d+)',
		[
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => 'chairforce_rest_quick_view',
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => [
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]
	);

}

add_action( 'rest_api_init', 'chairforce_register_quick_view_route' );

/**
 * Render single-product summary markup for the quick-view popup.
 *
 * @param \WP_REST_Request $request Request object.
 *
 * @return \WP_REST_Response
 */
function chairforce_rest_quick_view( \WP_REST_Request $request ): \WP_REST_Response {

	$product_id = absint( $request->get_param( 'id' ) );

	if (
		! $product_id
		|| 'product' !== get_post_type( $product_id )
		|| 'publish' !== get_post_status( $product_id )
	) {
		return new \WP_REST_Response( [ 'html' => '' ], 404 );
	}

	if ( ! function_exists( 'wc_get_product' ) ) {
		return new \WP_REST_Response( [ 'html' => '' ], 404 );
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return new \WP_REST_Response( [ 'html' => '' ], 404 );
	}

	global $post, $product;

	$post = get_post( $product_id );

	if ( ! $post ) {
		return new \WP_REST_Response( [ 'html' => '' ], 404 );
	}

	setup_postdata( $post );

	ob_start();
	?>
	<div id="product-<?php echo esc_attr( (string) $product_id ); ?>" <?php wc_product_class( '', $product ); ?>>
		<?php
		/**
		 * Gallery + sale flash — same hook sequence as content-single-product.php.
		 *
		 * @see woocommerce/templates/content-single-product.php
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>
		<div class="summary entry-summary">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	wp_reset_postdata();

	return new \WP_REST_Response(
		[
			'html' => $html,
		],
		200
	);

}
