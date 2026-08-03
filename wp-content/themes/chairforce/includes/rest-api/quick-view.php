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
 * Quick view summary content mode from theme options.
 *
 * @return 'dimensions'|'short_description'
 */
function chairforce_get_quick_view_content_mode(): string {

	$mode = 'dimensions';

	if ( function_exists( 'get_field' ) ) {
		$mode = get_field( 'quick_view_content', 'option' ) ?: 'dimensions';
	}

	return in_array( $mode, [ 'dimensions', 'short_description' ], true )
		? $mode
		: 'dimensions';
}

/**
 * Read formatted product Dimensions meta for quick view.
 *
 * Uses the legacy `dimensions` post meta / ACF field — not WooCommerce short
 * description or native shipping dimensions.
 *
 * @param int $product_id Product post ID.
 * @return string Safe HTML, or empty string when unset.
 */
function chairforce_get_product_dimensions_html( int $product_id ): string {

	$raw = '';

	if ( function_exists( 'get_field' ) ) {
		$field_value = get_field( 'dimensions', $product_id );
		if ( is_string( $field_value ) ) {
			$raw = $field_value;
		}
	}

	if ( '' === trim( $raw ) ) {
		$raw = (string) get_post_meta( $product_id, 'dimensions', true );
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
 * Output Dimensions block in quick-view summary (Figma "Product Details").
 */
function chairforce_quick_view_render_product_dimensions(): void {

	$html = chairforce_get_product_dimensions_html( get_the_ID() );

	if ( '' === $html ) {
		return;
	}
	?>
	<div class="cf-quick-view-details cf-quick-view-details--dimensions">
		<p class="cf-quick-view-details__label"><?php esc_html_e( 'Product Details', 'chairforce' ); ?></p>
		<div class="cf-quick-view-details__content"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in chairforce_get_product_dimensions_html(). ?></div>
	</div>
	<?php
}

/**
 * Output WooCommerce short description in quick-view summary.
 */
function chairforce_quick_view_render_product_short_description(): void {

	$product = wc_get_product( get_the_ID() );

	if ( ! $product ) {
		return;
	}

	$short_description = $product->get_short_description();

	if ( '' === trim( $short_description ) ) {
		return;
	}

	$html = wp_kses_post( wpautop( $short_description ) );
	?>
	<div class="cf-quick-view-details cf-quick-view-details--description">
		<div class="cf-quick-view-details__content"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></div>
	</div>
	<?php
}

/**
 * Output the configured quick-view summary text block.
 */
function chairforce_quick_view_render_product_details(): void {

	if ( 'short_description' === chairforce_get_quick_view_content_mode() ) {
		chairforce_quick_view_render_product_short_description();
		return;
	}

	chairforce_quick_view_render_product_dimensions();
}

/**
 * Use native product-button block in quick view for simple products.
 *
 * Same Interactivity API markup as product collection cards ("X in cart", Store API cart sync).
 */
function chairforce_quick_view_prepare_simple_add_to_cart(): void {

	global $product;

	if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
		return;
	}

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	add_action( 'woocommerce_single_product_summary', 'chairforce_quick_view_render_simple_add_to_cart', 30 );
}

/**
 * Native WooCommerce product-button block for simple products in quick view.
 */
function chairforce_quick_view_render_simple_add_to_cart(): void {

	global $product;

	if ( ! $product instanceof \WC_Product ) {
		return;
	}

	$product_id = $product->get_id();

	if ( function_exists( 'wc_interactivity_api_load_product' ) ) {
		wc_interactivity_api_load_product(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			$product_id
		);
	}

	$wrapper_directives = 'data-wp-interactive="woocommerce/product-collection"';

	if ( function_exists( 'wp_interactivity_data_wp_context' ) ) {
		$wrapper_directives .= ' ' . wp_interactivity_data_wp_context(
			[
				'productId'   => $product_id,
				'variationId' => null,
			],
			'woocommerce/products'
		);
	}

	printf(
		'<div %1$s>%2$s</div>',
		$wrapper_directives, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC iAPI directives.
		chairforce_render_product_button_block( $product_id ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted WC block render.
	);
}

/**
 * Output YITH Add to Quote below the add-to-cart form in quick view.
 */
function chairforce_quick_view_render_add_to_quote(): void {

	if ( ! chairforce_is_quick_view_request_quote_enabled() ) {
		return;
	}

	if ( ! chairforce_is_ywraq_available() ) {
		return;
	}

	if ( ! chairforce_is_ywraq_quote_button_enabled( 'single_product' ) ) {
		return;
	}

	if ( function_exists( 'yith_ywraq_show_button_in_single_page' ) && ! yith_ywraq_show_button_in_single_page() ) {
		return;
	}

	$product_id = get_the_ID();

	if ( ! $product_id ) {
		return;
	}

	$markup = chairforce_render_ywraq_button_quote_markup( $product_id );

	if ( '' === trim( $markup ) ) {
		return;
	}
	?>
	<div class="cf-quick-view-quote">
		<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- YITH shortcode output. ?>
	</div>
	<?php
}

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

	// Quick view summary: admin-selected dimensions or short description — not SKU/categories.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'chairforce_quick_view_render_product_details', 15 );

	if ( chairforce_is_quick_view_request_quote_enabled() ) {
		add_action( 'woocommerce_single_product_summary', 'chairforce_quick_view_render_add_to_quote', 35 );
	}

	chairforce_quick_view_prepare_simple_add_to_cart();

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
