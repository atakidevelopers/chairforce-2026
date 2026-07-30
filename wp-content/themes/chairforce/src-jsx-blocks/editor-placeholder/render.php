<?php
/**
 * Editor placeholder — server render.
 *
 * Editor UI is handled in edit.js. On the frontend, known modifiers swap in
 * live PHP output (header → chairforce_render_site_header()).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modifier = isset( $attributes['modifier'] ) ? sanitize_key( $attributes['modifier'] ) : '';

if ( 'header' === $modifier ) {
	chairforce_render_site_header();
	return;
}

// Fallback: render nothing for unknown modifiers on the frontend.
return;
