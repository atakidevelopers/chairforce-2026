<?php
/**
 * Shared product card markup helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Chairforce\Product_Swatches;
use Chairforce\Wishlist;

/**
 * Whether the current request is a WooCommerce product shop archive.
 *
 * Matches Load More / archive shell scope: main shop, product taxonomies, and the
 * product post-type archive. Product Collection cards with custom add-to-cart only
 * appear in this context in the theme.
 *
 * @return bool
 */
function chairforce_is_product_shop_archive(): bool {

	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' );
}

/**
 * WC ajax cart endpoints that refresh fragments (not a shop URL, but need quantity payload).
 *
 * @return bool
 */
function chairforce_is_wc_cart_fragment_request(): bool {

	if ( ! defined( 'WC_DOING_AJAX' ) || ! WC_DOING_AJAX ) {
		return false;
	}

	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query ) {
		return false;
	}

	$action = $wp_query->get( 'wc-ajax' );

	return in_array(
		$action,
		[ 'add_to_cart', 'remove_from_cart', 'get_refreshed_fragments' ],
		true
	);
}

/**
 * Whether theme product-card behaviour should run on this request.
 *
 * Gates block render overrides, loop add-to-cart markup, wc-add-to-cart enqueue,
 * cart-quantity fragments, and in-cart label sync. Scoped to shop archives (same
 * surface as Load More). WC cart-ajax requests are included so fragment refresh
 * still carries quantity data after add/remove.
 *
 * @return bool
 */
function chairforce_boots_product_card_features(): bool {

	if ( ! function_exists( 'WC' ) ) {
		return false;
	}

	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return false;
	}

	if ( chairforce_is_wc_cart_fragment_request() ) {
		return true;
	}

	return chairforce_is_product_shop_archive();
}

/**
 * Wishlist toggle markup for classic loop cards.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_card_wishlist_html( int $product_id ): string {

	if ( ! chairforce_is_wishlist_loop_enabled() || ! chairforce_is_wishlist_enabled() ) {
		return '';
	}

	if ( ! $product_id || ! Wishlist::is_valid_product( $product_id ) ) {
		return '';
	}

	$in_wishlist   = is_user_logged_in() && Wishlist::is_in_wishlist( get_current_user_id(), $product_id );
	$wrapper_class = 'cf-wishlist-button' . ( $in_wishlist ? ' is-active' : '' );
	$aria_pressed  = $in_wishlist ? 'true' : 'false';
	$label         = $in_wishlist
		? esc_attr__( 'Remove from wishlist', 'chairforce' )
		: esc_attr__( 'Add to wishlist', 'chairforce' );

	return sprintf(
		'<div class="%1$s"><button type="button" class="cf-wishlist-trigger" data-product-id="%2$d" aria-pressed="%3$s" aria-label="%4$s"><span class="screen-reader-text">%4$s</span></button></div>',
		esc_attr( $wrapper_class ),
		$product_id,
		esc_attr( $aria_pressed ),
		$label
	);
}

/**
 * Quick view trigger markup for classic loop cards.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_card_quick_view_html( int $product_id ): string {

	if ( ! $product_id ) {
		return '';
	}

	$label = esc_attr__( 'Quick view', 'chairforce' );

	return sprintf(
		'<div class="cf-quick-view-button"><button type="button" class="cf-quick-view-trigger" data-product-id="%1$d" aria-label="%2$s"><span class="screen-reader-text">%2$s</span></button></div>',
		$product_id,
		$label
	);
}

/**
 * Wishlist + quick view action stack for the card media overlay.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_card_actions_html( int $product_id ): string {

	$wishlist   = chairforce_get_product_card_wishlist_html( $product_id );
	$quick_view = chairforce_get_product_card_quick_view_html( $product_id );

	if ( '' === $wishlist && '' === $quick_view ) {
		return '';
	}

	return sprintf(
		'<div class="cf-card-actions">%1$s%2$s</div>',
		$wishlist,
		$quick_view
	);
}

/**
 * Grid swatches markup for classic loop cards.
 *
 * @param \WC_Product $product Product object.
 * @return string
 */
function chairforce_get_product_card_swatches_html( \WC_Product $product ): string {

	if ( ! class_exists( Product_Swatches::class ) ) {
		return '';
	}

	$markup = Product_Swatches::render_grid_swatches( $product );

	if ( '' === $markup ) {
		return '';
	}

	return sprintf(
		'<div class="cf-product-swatches">%s</div>',
		$markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Product_Swatches.
	);
}

/**
 * Sale badge markup aligned with block product cards.
 *
 * @param \WC_Product $product Product object.
 * @return string
 */
function chairforce_get_product_card_sale_badge_html( \WC_Product $product ): string {

	if ( ! $product->is_on_sale() ) {
		return '';
	}

	ob_start();
	woocommerce_show_product_loop_sale_flash();
	$flash = (string) ob_get_clean();

	if ( '' === trim( $flash ) ) {
		return '';
	}

	return str_replace(
		'class="onsale"',
		'class="onsale wc-block-components-product-sale-badge"',
		$flash
	);
}

/**
 * Linked product thumbnail for classic loop cards.
 *
 * @param \WC_Product $product Product object.
 * @return string
 */
function chairforce_get_product_card_thumbnail_html( \WC_Product $product ): string {

	$permalink = $product->get_permalink();
	$image     = $product->get_image(
		'woocommerce_thumbnail',
		[
			'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'   => $product->get_name(),
		]
	);

	if ( '' === $image ) {
		return '';
	}

	return sprintf(
		'<a href="%1$s" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">%2$s</a>',
		esc_url( $permalink ),
		$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC image helper.
	);
}

/**
 * Media column — image, sale badge, wishlist, quick view.
 *
 * @param \WC_Product $product Product object.
 * @return string
 */
function chairforce_get_product_card_media_html( \WC_Product $product ): string {

	$product_id = $product->get_id();
	$thumbnail  = chairforce_get_product_card_thumbnail_html( $product );
	$badge      = chairforce_get_product_card_sale_badge_html( $product );
	$actions    = chairforce_get_product_card_actions_html( $product_id );

	if ( '' === $thumbnail && '' === $badge && '' === $actions ) {
		return '';
	}

	return sprintf(
		'<div class="cf-card-media"><div class="wc-block-components-product-image">%1$s%2$s</div>%3$s</div>',
		$badge,
		$thumbnail,
		$actions
	);
}

/**
 * Build wrapper classes for a product collection product-button block.
 *
 * @param array<string, mixed> $block_attrs Parsed block attributes.
 * @return string
 */
function chairforce_get_product_card_button_wrapper_classes( array $block_attrs = [] ): string {

	$classes = [
		'cf-add-to-cart',
		'wp-block-button',
		'wc-block-components-product-button',
		'wp-block-woocommerce-product-button',
	];

	if ( ! empty( $block_attrs['fontSize'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( (string) $block_attrs['fontSize'] ) . '-font-size';
	}

	if ( ! empty( $block_attrs['width'] ) ) {
		$classes[] = 'has-custom-width';
		$classes[] = 'wp-block-button__width-' . absint( $block_attrs['width'] );
	}

	if ( ! empty( $block_attrs['textAlign'] ) ) {
		$classes[] = 'align-' . sanitize_html_class( (string) $block_attrs['textAlign'] );
	}

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Align classic loop add-to-cart link classes with block product-button markup.
 *
 * @param string $link Loop add-to-cart anchor HTML.
 * @return string
 */
function chairforce_enhance_product_card_add_to_cart_link( string $link ): string {

	if ( '' === trim( $link ) ) {
		return '';
	}

	if ( str_contains( $link, 'wc-block-components-product-button__button' ) ) {
		return $link;
	}

	$updated = preg_replace(
		'/class="([^"]*)"/',
		'class="wp-block-button__link wp-element-button wc-block-components-product-button__button $1"',
		$link,
		1
	);

	return is_string( $updated ) ? $updated : $link;
}

/**
 * Cart quantity for a product (simple product ID; sums lines for that product).
 *
 * @param int $product_id Product or variation stock-managed ID.
 * @return int
 */
function chairforce_get_product_cart_quantity( int $product_id ): int {

	if ( ! $product_id || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	$quantities = WC()->cart->get_cart_item_quantities();

	return isset( $quantities[ $product_id ] ) ? (int) $quantities[ $product_id ] : 0;
}

/**
 * Label when a simple product is already in the cart (matches WC Blocks wording).
 *
 * @param int $quantity Quantity in cart.
 * @return string
 */
function chairforce_get_product_in_cart_label( int $quantity ): string {

	$quantity = max( 0, $quantity );

	return $quantity > 0
		? sprintf(
			/* translators: %d: number of products in cart. */
			_n( '%d in cart', '%d in cart', $quantity, 'woocommerce' ),
			$quantity
		)
		: '';
}

/**
 * JSON script tag with product_id => quantity map for cart fragment refresh.
 *
 * @return string
 */
function chairforce_get_product_cart_quantities_script_markup(): string {

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	return sprintf(
		'<script type="application/json" id="cf-product-cart-quantities">%s</script>',
		wp_json_encode( WC()->cart->get_cart_item_quantities() )
	);
}

/**
 * @param string               $link    Loop add-to-cart anchor HTML.
 * @param \WC_Product          $product Product object.
 * @param array<string, mixed> $args    Loop args.
 * @return string
 */
function chairforce_filter_product_card_add_to_cart_link( string $link, \WC_Product $product, array $args ): string {

	if ( ! $product->is_type( 'simple' ) || ! $product->supports( 'ajax_add_to_cart' ) ) {
		return $link;
	}

	$default_label = wp_strip_all_tags( $product->add_to_cart_text() );
	$quantity      = chairforce_get_product_cart_quantity( $product->get_id() );

	$link = preg_replace(
		'/(<a\s)/',
		'$1data-cf-default-label="' . esc_attr( $default_label ) . '" ',
		$link,
		1
	);

	if ( $quantity <= 0 ) {
		return is_string( $link ) ? $link : '';
	}

	$in_cart_label = chairforce_get_product_in_cart_label( $quantity );

	$updated = preg_replace(
		'/>([^<]*)<\/a>/',
		'>' . esc_html( $in_cart_label ) . '</a>',
		$link,
		1
	);

	if ( ! is_string( $updated ) ) {
		return $link;
	}

	$updated = preg_replace(
		'/class="([^"]*)"/',
		'class="$1 cf-in-cart added"',
		$updated,
		1
	);

	return is_string( $updated ) ? $updated : $link;
}

 /**
 * Uses WooCommerce loop add-to-cart so simple, variable, and grouped products
 * behave like the classic shop loop (ajax add-to-cart, Select options links, etc.).
 *
 * @param \WC_Product          $product     Product object.
 * @param array<string, mixed> $block_attrs Optional product-button block attrs.
 * @return string
 */
function chairforce_get_product_card_add_to_cart_html( \WC_Product $product, array $block_attrs = [] ): string {

	global $product;

	$previous_product = ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product )
		? $GLOBALS['product']
		: null;

	$GLOBALS['product'] = $product;

	add_filter( 'woocommerce_loop_add_to_cart_link', 'chairforce_filter_product_card_add_to_cart_link', 10, 3 );

	ob_start();
	woocommerce_template_loop_add_to_cart();
	$link = (string) ob_get_clean();

	remove_filter( 'woocommerce_loop_add_to_cart_link', 'chairforce_filter_product_card_add_to_cart_link', 10 );

	if ( $previous_product instanceof \WC_Product ) {
		$GLOBALS['product'] = $previous_product;
	} else {
		unset( $GLOBALS['product'] );
	}

	$link = chairforce_enhance_product_card_add_to_cart_link( $link );

	if ( '' === trim( $link ) ) {
		return '';
	}

	return sprintf(
		'<div class="%1$s">%2$s</div>',
		esc_attr( chairforce_get_product_card_button_wrapper_classes( $block_attrs ) ),
		$link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC loop add to cart.
	);
}
