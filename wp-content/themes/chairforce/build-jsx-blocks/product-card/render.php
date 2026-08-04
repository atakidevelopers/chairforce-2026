<?php
/**
 * Product card block — server render via shared template part.
 *
 * Thin wrapper around `parts/product-card.html` so the canonical card can be
 * inserted in Product Collection loops on regular page content (not only FSE
 * template parts).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$attributes = $attributes ?? [];

$block_props   = get_block_wrapper_attributes();

$product_id = get_the_ID();

//if ( ! $product_id ) {
//	return;
//}
//
//$loop_post = get_post( $product_id );
//
//if ( ! $loop_post instanceof \WP_Post ) {
//	return;
//}
//
//global $post, $product;
//
//$previous_post    = ( $post instanceof \WP_Post ) ? $post : null;
//$previous_product = ( isset( $product ) && $product instanceof \WC_Product ) ? $product : null;
//
//$post = $loop_post;
//
//if ( function_exists( 'wc_get_product' ) ) {
//	$product = wc_get_product( $product_id );
//}
$product = wc_get_product( $product_id );

$parent_context = ( $block instanceof \WP_Block && is_array( $block->context ) ) ? $block->context : [];

$available_context = array_merge(
	$parent_context,
	[
		'postType' => get_post_type( $product ),
		'postId'   => $product_id,
	]
);

$blocks_rendered = (
new \WP_Block(
	[
		'blockName'    => 'core/template-part',
		'attrs'        => [
			'slug'  => 'product-card',
			'theme' => 'chairforce',
		],
		'innerBlocks'  => [],
		'innerHTML'    => '',
		'innerContent' => [],
	],
	$available_context
)
)->render();

printf( '
	<div %s>
		%s
	</div>',
	$block_props,
	$blocks_rendered
);
//echo (
//	new \WP_Block(
//		[
//			'blockName'    => 'core/template-part',
//			'attrs'        => [
//				'slug'  => 'product-card',
//				'theme' => 'chairforce',
//			],
//			'innerBlocks'  => [],
//			'innerHTML'    => '',
//			'innerContent' => [],
//		],
//		$available_context
//	)
//)->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output.
//


//if ( $previous_post instanceof \WP_Post ) {
//	$post = $previous_post;
//} else {
//	unset( $post );
//}

//if ( $previous_product instanceof \WC_Product ) {
//	$product = $previous_product;
//} else {
//	unset( $product );
//}
