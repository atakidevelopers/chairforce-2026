<?php
/**
 * Product archive filter chrome (bar + active chips).
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
