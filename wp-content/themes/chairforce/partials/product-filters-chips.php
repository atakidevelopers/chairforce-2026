<?php
/**
 * Active catalog filter chips.
 *
 * @package Chairforce
 *
 * @var string $context Display context: `chrome` (bar area) or `panel` (filter drawer).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context   = isset( $args['context'] ) ? (string) $args['context'] : 'chrome';
$chips     = chairforce_get_active_filter_chips();
$clear_url = chairforce_get_clear_catalog_filters_url();
$is_empty  = empty( $chips );

// Panel drawer: omit entirely when empty. Chrome bar: always render a stable slot.
if ( $is_empty && 'panel' === $context ) {
	return;
}

$wrapper_class = 'cf-active-filters';

if ( 'panel' === $context ) {
	$wrapper_class .= ' cf-active-filters--panel';
} else {
	$wrapper_class .= ' cf-active-filters--chrome';

	if ( $is_empty ) {
		$wrapper_class .= ' is-empty';
	}
}

$chip_buttons = [];

foreach ( $chips as $chip ) {
	$chip_label = (string) ( $chip['label'] ?? '' );

	if ( '' === $chip_label ) {
		continue;
	}

	$html_attributes = [
		'data-filter-name' => (string) ( $chip['filter_name'] ?? '' ),
		'data-remove-url'  => (string) ( $chip['remove_url'] ?? '' ),
		'aria-label'       => sprintf(
			/* translators: %s: active filter label */
			__( 'Remove filter: %s', 'chairforce' ),
			$chip_label
		),
	];

	if ( ! empty( $chip['term_slug'] ) ) {
		$html_attributes['data-term-slug'] = (string) $chip['term_slug'];
	}

	$chip_buttons[] = [
		'label'           => $chip_label,
		'tag'             => 'button',
		'style'           => 'is-style-light',
		'icon'            => 'x',
		'icon_position'   => 'right',
		'element_class'   => 'cf-active-filters__chip',
		'html_attributes' => $html_attributes,
	];
}
?>
<div
	class="<?php echo esc_attr( $wrapper_class ); ?>"
	<?php if ( $is_empty && 'chrome' === $context ) : ?>
		aria-hidden="true"
	<?php endif; ?>
>
	<div class="cf-active-filters__inner">
		<?php if ( 'chrome' === $context ) : ?>
			<p class="cf-active-filters__label"><?php esc_html_e( 'Active Filters:', 'chairforce' ); ?></p>
		<?php endif; ?>
		<?php if ( ! $is_empty ) : ?>
			<?php
			echo chairforce_get_buttons_markup(
				$chip_buttons,
				[
					'wrapper_class' => 'cf-active-filters__chips',
					'default_style' => 'is-style-light',
					'layout'        => [
						'type'     => 'flex',
						'flexWrap' => 'wrap',
					],
				]
			);
			?>
			<?php if ( $clear_url ) : ?>
				<button
					type="button"
					class="cf-active-filters__clear"
					data-clear-url="<?php echo esc_url( $clear_url ); ?>"
				>
					<?php esc_html_e( 'Clear all', 'chairforce' ); ?>
				</button>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
