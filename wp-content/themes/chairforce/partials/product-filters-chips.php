<?php
/**
 * Active catalog filter chips.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$chips     = chairforce_get_active_filter_chips();
$clear_url = chairforce_get_clear_catalog_filters_url();

if ( empty( $chips ) ) {
	return;
}
?>
<div class="cf-active-filters">
	<ul class="cf-active-filters__list">
		<?php foreach ( $chips as $chip ) : ?>
			<li class="cf-active-filters__item">
				<button
					type="button"
					class="cf-active-filters__chip"
					data-filter-name="<?php echo esc_attr( (string) ( $chip['filter_name'] ?? '' ) ); ?>"
					<?php if ( ! empty( $chip['term_slug'] ) ) : ?>
						data-term-slug="<?php echo esc_attr( (string) $chip['term_slug'] ); ?>"
					<?php endif; ?>
					data-remove-url="<?php echo esc_url( (string) ( $chip['remove_url'] ?? '' ) ); ?>"
				>
					<span class="cf-active-filters__chip-label"><?php echo esc_html( (string) ( $chip['label'] ?? '' ) ); ?></span>
					<span class="cf-active-filters__chip-remove" aria-hidden="true">&times;</span>
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: %s: active filter label */
							esc_html__( 'Remove filter: %s', 'chairforce' ),
							esc_html( (string) ( $chip['label'] ?? '' ) )
						);
						?>
					</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $clear_url ) : ?>
		<button
			type="button"
			class="cf-active-filters__clear"
			data-clear-url="<?php echo esc_url( $clear_url ); ?>"
		>
			<?php esc_html_e( 'Clear all', 'chairforce' ); ?>
		</button>
	<?php endif; ?>
</div>
