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
?>
<div class="cf-product-filters__bar">
	<div class="cf-product-filters__bar-scroll" role="toolbar" aria-label="<?php esc_attr_e( 'Product filters', 'chairforce' ); ?>">
		<?php foreach ( $filter_groups as $group ) : ?>
			<?php
			$slug  = (string) ( $group['slug'] ?? '' );
			$label = (string) ( $group['label'] ?? $slug );
			$active_count = 0;

			if ( 'price' === ( $group['type'] ?? '' ) ) {
				$params = chairforce_parse_catalog_filter_params();
				$active_count = ( ! empty( $params['min_price'] ) || ! empty( $params['max_price'] ) ) ? 1 : 0;
			} else {
				foreach ( (array) ( $group['terms'] ?? [] ) as $term ) {
					if ( ! empty( $term['chosen'] ) ) {
						++$active_count;
					}
				}
			}
			?>
			<button
				type="button"
				class="cf-product-filters__bar-button"
				data-filter-card="<?php echo esc_attr( $slug ); ?>"
				aria-expanded="false"
				aria-controls="cf-filter-card-<?php echo esc_attr( $slug ); ?>"
			>
				<span class="cf-product-filters__bar-button-label"><?php echo esc_html( $label ); ?></span>
				<?php if ( $active_count > 0 ) : ?>
					<span class="cf-product-filters__bar-button-count"><?php echo esc_html( (string) $active_count ); ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
</div>
