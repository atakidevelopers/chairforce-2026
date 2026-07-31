<?php
/**
 * Product archive filter bar buttons.
 *
 * @package Chairforce
 *
 * @var array<int, array<string, mixed>> $filter_groups Filter groups from chairforce_get_archive_filter_groups().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filter_groups = $filter_groups ?? chairforce_get_archive_filter_groups();

if ( empty( $filter_groups ) ) {
	return;
}

$bar_buttons = [];

foreach ( $filter_groups as $group ) {
	$slug  = (string) ( $group['slug'] ?? '' );
	$label = (string) ( $group['label'] ?? $slug );

	if ( '' === $slug ) {
		continue;
	}

	$bar_buttons[] = [
		'label'           => $label,
		'tag'             => 'button',
		'style'           => 'is-style-light',
		'icon'            => chairforce_get_filter_group_icon_slug( $slug ),
		'element_class'   => 'cf-product-filters__bar-button',
		'html_attributes' => [
			'data-filter-card' => $slug,
			'aria-expanded'    => 'false',
			'aria-controls'    => 'cf-filter-card-' . $slug,
		],
	];
}
?>
<div class="cf-product-filters__bar">
	<div class="cf-product-filters__bar-scroll cf-scrollbar" role="toolbar" aria-label="<?php esc_attr_e( 'Product filters', 'chairforce' ); ?>">
		<?php
		echo chairforce_get_buttons_markup(
			$bar_buttons,
			[
				'wrapper_class' => 'cf-product-filters__bar-buttons',
				'default_style' => 'is-style-light',
				'layout'        => [
					'type'     => 'flex',
					'flexWrap' => 'nowrap',
				],
			]
		);
		?>
	</div>
</div>
