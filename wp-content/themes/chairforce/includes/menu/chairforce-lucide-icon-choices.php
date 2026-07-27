<?php
/**
 * Curated Lucide icon slugs for menu ACF fields.
 * Keep in sync with src/js-admin/lucide-icon-options.js (CHAIRFORCE_LUCIDE_ICON_OPTIONS).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug => admin label map for utility (and other) menu icon pickers.
 *
 * @return array<string, string>
 */
function chairforce_get_lucide_icon_choices(): array {

	return [
		'search'             => __( 'Search', 'chairforce' ),
		'shopping-cart'      => __( 'Shopping Cart', 'chairforce' ),
		'file-text'          => __( 'File Text', 'chairforce' ),
		'user'               => __( 'User', 'chairforce' ),
		'map-pin'            => __( 'Map Pin', 'chairforce' ),
		'phone'              => __( 'Phone', 'chairforce' ),
		'heart'              => __( 'Heart', 'chairforce' ),
		'plus'               => __( 'Plus', 'chairforce' ),
		'minus'              => __( 'Minus', 'chairforce' ),
		'trash-2'            => __( 'Trash 2', 'chairforce' ),
		'chevron-left'       => __( 'Chevron Left', 'chairforce' ),
		'chevron-right'      => __( 'Chevron Right', 'chairforce' ),
		'chevron-down'       => __( 'Chevron Down', 'chairforce' ),
		'arrow-right'        => __( 'Arrow Right', 'chairforce' ),
		'x'                  => __( 'X', 'chairforce' ),
		'menu'               => __( 'Menu', 'chairforce' ),
		'truck'              => __( 'Truck', 'chairforce' ),
		'zap'                => __( 'Zap', 'chairforce' ),
		'shield-check'       => __( 'Shield Check', 'chairforce' ),
		'smile'              => __( 'Smile', 'chairforce' ),
		'package'            => __( 'Package', 'chairforce' ),
		'star'               => __( 'Star', 'chairforce' ),
		'clock'              => __( 'Clock', 'chairforce' ),
		'mail'               => __( 'Mail', 'chairforce' ),
		'check'              => __( 'Check', 'chairforce' ),
		'check-circle'       => __( 'Check Circle', 'chairforce' ),
		'tag'                => __( 'Tag', 'chairforce' ),
		'filter'             => __( 'Filter', 'chairforce' ),
		'sliders-horizontal' => __( 'Sliders Horizontal', 'chairforce' ),
		'grid-2x2'           => __( 'Grid', 'chairforce' ),
	];

}

/**
 * Validate a Lucide icon slug against the curated project list.
 */
function chairforce_sanitize_lucide_icon_slug( string $slug ): string {

	$slug    = sanitize_key( $slug );
	$choices = chairforce_get_lucide_icon_choices();

	return array_key_exists( $slug, $choices ) ? $slug : '';

}
