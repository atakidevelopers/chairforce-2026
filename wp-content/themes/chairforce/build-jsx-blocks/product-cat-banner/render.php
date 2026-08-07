<?php
/**
 * Product category banner — resolved from Banner Configurations.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$banner_id = chairforce_get_queried_product_cat_banner_id();

if ( $banner_id < 1 ) {
	return;
}

$markup = chairforce_get_banner_post_markup( $banner_id );

if ( '' === trim( $markup ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-product-cat-banner',
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
</div>
