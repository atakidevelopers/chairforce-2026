<?php
/**
 * Site header — server render.
 *
 * Editor UI is handled in edit.js. On the frontend (and admin AJAX), output the
 * live PHP header. In wp-admin otherwise, render nothing so the editor notice
 * is not duplicated by server output.
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

chairforce_render_site_header();
