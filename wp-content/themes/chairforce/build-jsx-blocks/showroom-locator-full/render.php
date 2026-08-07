<?php
/**
 * Showroom Locator (full) block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

use Chairforce\Showroom_Locator_Full;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = is_array( $attributes ) ? $attributes : [];

echo Showroom_Locator_Full::render( $attributes, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
