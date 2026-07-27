<?php
/**
 * Utility cluster menu item renderer (Showrooms, Account, Quotes).
 *
 * @package Chairforce
 */

if ( empty( $args['item'] ) || ! $args['item'] instanceof WP_Post ) {
	return;
}

$item      = $args['item'];
$url       = ! empty( $item->url ) ? $item->url : '#';
$icon_slug = chairforce_menu_get_utility_icon_slug( $item );

printf(
	'<a href="%s" class="site-header__utility-link site-header__utility-link--icon-%s">',
	esc_url( $url ),
	esc_attr( $icon_slug )
);

echo '<span class="site-header__utility-icon cf-icon-' . esc_attr( $icon_slug ) . '" aria-hidden="true"></span>';
echo '<span class="site-header__utility-label">';
echo chairforce_menu_render_labels( $item );
echo '</span>';
echo '</a>';
