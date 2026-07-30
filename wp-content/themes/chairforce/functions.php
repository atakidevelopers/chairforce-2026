<?php
/**
 * Required functions parts
 */
require get_stylesheet_directory() . '/includes/init.php';
/**
 * Theme Bootstrap.
 * @since    0.0.1
 */
function Chairforce() {

	return Chairforce\Init::get_instance();

}

Chairforce();


// Shortcode_display :  year    // like: show_post_list

function year_cb($atts = [], $content = null, $tag = '')
{
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);


	$atts = shortcode_atts( array(
        // Update the default Values
		'arg_1' => true,
		'arg_2' => 'arg Value',

	), $atts );

	// start output
	$output = '';


	// Update output
//	$output .= '<div class="">';
	$output .= date( 'Y');
//	$output .= '</div>';

	// return output
	return $output;
}
add_shortcode('year', 'year_cb');




add_action(
	'woocommerce_before_shop_loop_item',
	'my_product_card_content',
	9
);

function my_product_card_content(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	echo '<div class="product-card-extra">';
	echo esc_html__( 'Your custom content Added once', 'your-textdomain' );
	echo '</div>';
}

/**
 * SPIKE / TEST ONLY — Block Hooks verification.
 * @see context/notes/product-grid-cards-and-load-more.md §3.2e
 *
 * Auto-inserts a paragraph after every `woocommerce/product-image` block
 * on any Product Collection card (archive grid, related products, etc.)
 * using the core Block Hooks API — no template edits.
 *
 * REMOVE this whole block once verified; this is not production code.
 */
//add_filter(
//	'hooked_block_types',
//	function ( $hooked_blocks, $relative_position, $anchor_block_type, $context ) {
//		if ( 'woocommerce/product-image' === $anchor_block_type && 'after' === $relative_position ) {
//			$hooked_blocks[] = 'core/paragraph';
//		}
//
//		return $hooked_blocks;
//	},
//	10,
//	4
//);
//
//add_filter(
//	'hooked_block_core/paragraph',
//	function ( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
//		if ( 'woocommerce/product-image' !== ( $parsed_anchor_block['blockName'] ?? '' ) ) {
//			return $parsed_hooked_block;
//		}
//
//		$text = '🔧 Block Hooks spike — if you can see this, hooks work here.';
//
//		// `core/paragraph` has no render_callback — it renders whatever's in
//		// innerHTML/innerContent, not attrs.content, so both must be set.
//		$parsed_hooked_block['attrs']        = [ 'content' => $text ];
//		$parsed_hooked_block['innerHTML']    = '<p>' . esc_html( $text ) . '</p>';
//		$parsed_hooked_block['innerContent'] = [ $parsed_hooked_block['innerHTML'] ];
//
//		return $parsed_hooked_block;
//	},
//	10,
//	5
//);

/**
 * SPIKE / TEST ONLY — `chairforce/product-card` classic-hooks verification.
 * @see context/notes/product-grid-cards-and-load-more.md
 *
 * Insert the `chairforce/product-card` block inside `woocommerce/product-template`
 * (replacing the existing product-image/title/price/button blocks there) and
 * this should appear once per product card, right after the title/rating/price
 * block WooCommerce's own `woocommerce_template_loop_price` hooks in — proving
 * our own future swatches/quick-view can extend the card the same way.
 *
 * REMOVE this whole block once verified; this is not production code.
 */
add_action(
	'woocommerce_after_shop_loop_item_title',
	function () {
		echo '<p class="cf-spike-test-marker" style="color:#fff;background:#d63384;padding:4px 8px;border-radius:4px;font-size:12px;">'
			. esc_html__( '🔧 Product Card spike — classic hook fired here.', 'chairforce' )
			. '</p>';
	},
	20
);
