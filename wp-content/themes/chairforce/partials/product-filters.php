<?php
/**
 * Product archive filters shell (bar + chips + panel).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filter_groups = ( isset( $args ) && is_array( $args ) && ! empty( $args['filter_groups'] ) && is_array( $args['filter_groups'] ) )
	? $args['filter_groups']
	: chairforce_get_archive_filter_groups();

if ( empty( $filter_groups ) ) {
	return;
}

$desktop_orientation = chairforce_get_filters_panel_desktop();
$mobile_orientation  = chairforce_get_filters_panel_mobile();
$clear_url           = chairforce_get_clear_catalog_filters_url();
?>
<div
	class="cf-product-filters alignwide"
	data-panel-desktop="<?php echo esc_attr( $desktop_orientation ); ?>"
	data-panel-mobile="<?php echo esc_attr( $mobile_orientation ); ?>"
	data-clear-url="<?php echo esc_url( $clear_url ); ?>"
>
	<div class="cf-product-filters__chrome">
		<?php
		get_template_part(
			'partials/product-filters',
			'bar',
			[
				'filter_groups' => $filter_groups,
			]
		);

		get_template_part( 'partials/product-filters', 'chips' );
		?>
	</div>

	<aside class="cf-shop-archive-sidebar" aria-label="<?php esc_attr_e( 'Product filters', 'chairforce' ); ?>">
		<?php
		get_template_part(
			'partials/product-filters',
			'panel',
			[
				'filter_groups' => $filter_groups,
			]
		);
		?>
	</aside>
</div>
