<?php
/**
 * Opens submenu / mega menu containers.
 *
 * @package Chairforce
 */

$args    = $args ?? [];
$depth   = isset( $args['depth'] ) ? (int) $args['depth'] : 0;
$item    = $args['item'] ?? null;
$context = $args['context'] ?? 'desktop-nav';

if ( ! $item instanceof WP_Post ) {
	return;
}

$item_slug  = chairforce_menu_get_item_slug( $item );
$item_title = apply_filters( 'the_title', $item->title, $item->ID );

if ( 0 === $depth && 'desktop-nav' === $context ) :
	?>
	<div
		id="<?php echo esc_attr( 'chairforce-mega-menu-' . $item_slug ); ?>"
		class="is-layout-constrained site-header__mega-menu"
		role="region"
		aria-label="<?php echo esc_attr( $item_title ); ?>"
		hidden
	>
		<div class="alignwide site-header__mega-menu-inner">
	<?php
endif;

$list_class = 0 === $depth ? 'site-header__mega-menu-list' : 'site-header__mega-menu-sublist';
?>
<ul class="<?php echo esc_attr( $list_class ); ?> site-header__mega-menu-depth-<?php echo (int) $depth; ?>">
