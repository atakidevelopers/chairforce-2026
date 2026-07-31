<?php
/**
 * Filter panel sections (stacked sidebar layout).
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

$desktop_orientation = chairforce_get_filters_panel_desktop();
$mobile_orientation  = chairforce_get_filters_panel_mobile();
?>
<div
	class="cf-filters-panel cf-filters-panel--<?php echo esc_attr( $desktop_orientation ); ?> cf-filters-panel--desktop-<?php echo esc_attr( $desktop_orientation ); ?> cf-filters-panel--mobile-<?php echo esc_attr( $mobile_orientation ); ?>"
	hidden
	aria-hidden="true"
	inert
>
	<div class="cf-filters-panel__backdrop" data-cf-filters-close hidden aria-hidden="true"></div>
	<div
		class="cf-filters-panel__inner"
		role="dialog"
		aria-modal="true"
		aria-labelledby="cf-filters-panel-title"
		tabindex="-1"
	>
		<div class="cf-filters-panel__header">
			<p class="cf-filters-panel__title" id="cf-filters-panel-title"><?php esc_html_e( 'Filters', 'chairforce' ); ?></p>
			<button type="button" class="cf-filters-panel__close" data-cf-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'chairforce' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="cf-filters-panel__sections">
			<?php foreach ( $filter_groups as $group ) : ?>
				<?php
				$slug  = (string) ( $group['slug'] ?? '' );
				$type  = (string) ( $group['type'] ?? 'attribute' );
				$label = (string) ( $group['label'] ?? $slug );
				$is_colour = 'attribute' === $type && chairforce_is_colour_filter_attribute( $slug );
				?>
				<section
					class="cf-filter-section cf-filter-section--<?php echo esc_attr( $type ); ?><?php echo $is_colour ? ' cf-filter-section--swatches' : ''; ?>"
					data-filter-card="<?php echo esc_attr( $slug ); ?>"
					id="cf-filter-card-<?php echo esc_attr( $slug ); ?>"
				>
					<h3 class="cf-filter-section__title"><?php echo esc_html( $label ); ?></h3>

					<?php if ( 'price' === $type ) : ?>
						<?php
						$min_bound = (float) ( $group['min_bound'] ?? 0 );
						$max_bound = (float) ( $group['max_bound'] ?? 0 );
						$min_value = (float) ( $group['min'] ?? $min_bound );
						$max_value = (float) ( $group['max'] ?? $max_bound );

						if ( $max_bound <= $min_bound ) {
							$max_bound = max( $min_bound + 1, 10000 );
						}

						if ( $min_value < $min_bound ) {
							$min_value = $min_bound;
						}

						if ( $max_value <= 0 || $max_value > $max_bound ) {
							$max_value = $max_bound;
						}
						?>
						<div
							class="cf-filter-price"
							data-min-bound="<?php echo esc_attr( (string) $min_bound ); ?>"
							data-max-bound="<?php echo esc_attr( (string) $max_bound ); ?>"
						>
							<div class="cf-filter-price__inputs">
								<label class="cf-filter-price__field">
									<span class="screen-reader-text"><?php esc_html_e( 'Minimum price', 'chairforce' ); ?></span>
									<input
										type="number"
										class="cf-filter-price__input cf-filter-price__input--min"
										min="<?php echo esc_attr( (string) $min_bound ); ?>"
										max="<?php echo esc_attr( (string) $max_bound ); ?>"
										value="<?php echo esc_attr( (string) (int) $min_value ); ?>"
										step="1"
										inputmode="numeric"
									/>
								</label>
								<span class="cf-filter-price__separator" aria-hidden="true">&ndash;</span>
								<label class="cf-filter-price__field">
									<span class="screen-reader-text"><?php esc_html_e( 'Maximum price', 'chairforce' ); ?></span>
									<input
										type="number"
										class="cf-filter-price__input cf-filter-price__input--max"
										min="<?php echo esc_attr( (string) $min_bound ); ?>"
										max="<?php echo esc_attr( (string) $max_bound ); ?>"
										value="<?php echo esc_attr( (string) (int) $max_value ); ?>"
										step="1"
										inputmode="numeric"
									/>
								</label>
							</div>
							<button type="button" class="cf-filter-price__apply">
								<?php esc_html_e( 'Apply', 'chairforce' ); ?>
							</button>
						</div>
					<?php elseif ( $is_colour ) : ?>
						<div class="cf-filter-card__terms cf-filter-card__terms--swatches cf-swatches-grid cf-swatches--style-3 cf-swatches--dis-style-3 cf-swatches--size-m cf-swatches--shape-round">
							<?php foreach ( (array) ( $group['terms'] ?? [] ) as $term ) : ?>
								<?php
								$taxonomy    = (string) ( $group['taxonomy'] ?? '' );
								$filter_name = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
								echo chairforce_render_filter_swatch_term( $taxonomy, $term, $filter_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<ul class="cf-filter-card__terms">
							<?php foreach ( (array) ( $group['terms'] ?? [] ) as $term ) : ?>
								<?php
								$term_slug   = (string) ( $term['slug'] ?? '' );
								$term_label  = (string) ( $term['label'] ?? $term_slug );
								$term_count  = (int) ( $term['count'] ?? 0 );
								$is_chosen   = ! empty( $term['chosen'] );
								$taxonomy    = (string) ( $group['taxonomy'] ?? '' );
								$filter_name = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
								?>
								<li class="cf-filter-card__term">
									<button
										type="button"
										class="cf-filter-term<?php echo $is_chosen ? ' is-active' : ''; ?>"
										data-filter-name="<?php echo esc_attr( $filter_name ); ?>"
										data-term-slug="<?php echo esc_attr( $term_slug ); ?>"
										data-toggle-url="<?php echo esc_url( (string) ( $term['toggle_url'] ?? '' ) ); ?>"
										aria-pressed="<?php echo $is_chosen ? 'true' : 'false'; ?>"
									>
										<span class="cf-filter-term__label"><?php echo esc_html( $term_label ); ?></span>
										<span class="cf-filter-term__count"><?php echo esc_html( number_format_i18n( $term_count ) ); ?></span>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
</div>
