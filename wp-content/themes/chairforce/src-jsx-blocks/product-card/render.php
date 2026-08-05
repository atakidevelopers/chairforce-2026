<?php
/**
 * Product card block — server render via shared block markup.
 *
 * Renders the canonical Product Collection card inner blocks inside
 * `.wp-block-chairforce-product-card`. Markup lives in
 * chairforce_get_product_card_blocks_markup().
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = $attributes ?? [];

$block_props     = get_block_wrapper_attributes();
$blocks_rendered = do_blocks( chairforce_get_product_card_blocks_markup() );

// TODO: Do not remove the Block Wrapper class, its going to be Very Helpful to identify this ourt block no matter where its inserted.
printf(
	'
	<div %s>
		%s
	</div>',
	$block_props,
	$blocks_rendered
);
