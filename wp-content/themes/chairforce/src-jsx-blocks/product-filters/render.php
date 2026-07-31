<?php
/**
 * Product filters — server render.
 *
 * Editor UI is handled in edit.js. On the storefront (and shell AJAX), output
 * the live PHP filter bar, panel, and chips.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
	return;
}

if ( ! chairforce_is_product_filter_archive() ) {
	return;
}

$filters_markup = chairforce_render_product_filters_html();

if ( '' === $filters_markup ) {
	return;
}

echo $filters_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
