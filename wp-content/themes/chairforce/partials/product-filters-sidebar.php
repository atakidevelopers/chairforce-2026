<?php
/**
 * Product archive filter sidebar (panel host).
 *
 * @package Chairforce
 *
 * @var array<int, array<string, mixed>> $filter_groups Filter groups from chairforce_get_archive_filter_groups().
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
?>
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
