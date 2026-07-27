<?php
/**
 * Shared menu helper functions for the header navigation system.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the nav menu args target the primary location.
 *
 * @param object $args wp_nav_menu() args.
 */
function chairforce_menu_is_primary( $args ): bool {

	return isset( $args->theme_location ) && CHAIRFORCE_MENU_PRIMARY === $args->theme_location;

}

/**
 * Whether the nav menu args target the utility location.
 *
 * @param object $args wp_nav_menu() args.
 */
function chairforce_menu_is_utility( $args ): bool {

	return isset( $args->theme_location ) && CHAIRFORCE_MENU_UTILITY === $args->theme_location;

}

/**
 * Whether hooks should run for chairforce header menus.
 *
 * @param object $args wp_nav_menu() args.
 */
function chairforce_menu_is_chairforce_nav( $args ): bool {

	return chairforce_menu_is_primary( $args ) || chairforce_menu_is_utility( $args );

}

/**
 * Menu render context: desktop nav row or mobile drawer list.
 *
 * @param object $args wp_nav_menu() args.
 */
function chairforce_menu_get_context( $args ): string {

	if ( ! empty( $args->chairforce_menu_context ) ) {
		return (string) $args->chairforce_menu_context;
	}

	return 'desktop-nav';

}

/**
 * Resolve link_type for a menu item.
 *
 * @param WP_Post $item Menu item.
 * @param object  $args wp_nav_menu() args.
 */
function chairforce_menu_get_link_type( $item, $args ): string {

	$link_type = '';

	if ( function_exists( 'get_field' ) ) {
		$link_type = (string) get_field( 'link_type', $item->ID );
	}

	if ( '' === $link_type && chairforce_menu_is_utility( $args ) ) {
		return 'utility-link';
	}

	if ( '' === $link_type ) {
		return 'default';
	}

	return $link_type;

}

/**
 * Stable slug for mega menu IDs from menu item title.
 *
 * @param WP_Post $item Menu item.
 */
function chairforce_menu_get_item_slug( $item ): string {

	$slug = sanitize_title( $item->title );

	if ( '' === $slug ) {
		$slug = 'item-' . $item->ID;
	}

	return $slug;

}

/**
 * Desktop + mobile label markup for CSS breakpoint toggle.
 *
 * @param WP_Post $item  Menu item.
 * @param string  $title Desktop label text.
 */
function chairforce_menu_render_labels( $item, string $title = '' ): string {

	$desktop = '' !== $title ? $title : $item->title;
	$mobile  = $desktop;

	if ( function_exists( 'get_field' ) ) {
		$mobile_override = get_field( 'label_mobile', $item->ID );
		if ( is_string( $mobile_override ) && '' !== $mobile_override ) {
			$mobile = $mobile_override;
		}
	}

	return sprintf(
		'<span class="site-header__menu-label site-header__menu-label--desktop">%1$s</span><span class="site-header__menu-label site-header__menu-label--mobile">%2$s</span>',
		esc_html( $desktop ),
		esc_html( $mobile )
	);

}

/**
 * Resolve thumbnail attachment ID for a menu item.
 *
 * @param WP_Post $item Menu item.
 */
function chairforce_menu_get_thumbnail_id( $item ): int {

	if ( function_exists( 'get_field' ) ) {
		$override = get_field( 'image', $item->ID );
		if ( $override ) {
			return (int) $override;
		}
	}

	if ( 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
		$term_thumb = get_term_meta( (int) $item->object_id, 'thumbnail_id', true );
		if ( $term_thumb ) {
			return (int) $term_thumb;
		}
	}

	return 0;

}

/**
 * Theme asset URL for menu thumbnail placeholders.
 */
function chairforce_menu_get_placeholder_thumbnail_url(): string {

	return get_theme_file_uri( CHAIRFORCE_MENU_THUMB_PLACEHOLDER );

}

/**
 * Output a menu thumbnail (attachment or theme placeholder).
 *
 * @param WP_Post $item Menu item.
 */
function chairforce_menu_render_thumbnail( $item ): void {

	if ( wp_is_mobile() ) {
		return;
	}

	$attachment_id = chairforce_menu_get_thumbnail_id( $item );

	if ( $attachment_id ) {
		echo wp_get_attachment_image(
			$attachment_id,
			CHAIRFORCE_MENU_THUMB_SIZE,
			false,
			[
				'class'    => 'site-header__menu-thumb',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => '',
			]
		);
		return;
	}

	printf(
		'<img src="%s" class="site-header__menu-thumb" width="%d" height="%d" alt="" loading="lazy" decoding="async" />',
		esc_url( chairforce_menu_get_placeholder_thumbnail_url() ),
		CHAIRFORCE_MENU_THUMB_DISPLAY,
		CHAIRFORCE_MENU_THUMB_DISPLAY
	);

}

/**
 * Lucide icon slug for utility menu items.
 *
 * @param WP_Post $item Menu item.
 */
function chairforce_menu_get_utility_icon_slug( $item ): string {

	if ( function_exists( 'get_field' ) ) {
		$icon = get_field( 'utility_icon', $item->ID );
		if ( is_string( $icon ) && '' !== $icon ) {
			$icon = chairforce_sanitize_lucide_icon_slug( $icon );
			if ( $icon ) {
				return $icon;
			}
		}
	}

	$title_slug = sanitize_title( $item->title );

	if ( str_contains( $title_slug, 'account' ) ) {
		return 'user';
	}

	if ( str_contains( $title_slug, 'quote' ) ) {
		return 'file-text';
	}

	if ( str_contains( $title_slug, 'showroom' ) ) {
		return 'map-pin';
	}

	return 'user';

}
