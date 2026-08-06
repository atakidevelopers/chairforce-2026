<?php
/**
 * Reusable category swiper markup helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize category swiper display options.
 *
 * @param array<string, mixed> $args Raw options.
 * @return array{
 *     show_arrows_desktop: bool,
 *     show_arrows_mobile: bool,
 *     show_progress_bar: bool,
 *     show_labels: bool,
 *     instance_id: string,
 * }
 */
function chairforce_normalize_category_swiper_args( array $args = [] ): array {
	$instance_id = isset( $args['instance_id'] )
		? sanitize_html_class( (string) $args['instance_id'] )
		: 'cf-category-swiper';

	if ( '' === $instance_id ) {
		$instance_id = 'cf-category-swiper';
	}

	return [
		'show_arrows_desktop' => ! isset( $args['showArrowsDesktop'] ) || (bool) $args['showArrowsDesktop'],
		'show_arrows_mobile'  => isset( $args['showArrowsMobile'] ) && (bool) $args['showArrowsMobile'],
		'show_progress_bar'   => ! isset( $args['showProgressBar'] ) || (bool) $args['showProgressBar'],
		'show_labels'         => ! isset( $args['showLabels'] ) || (bool) $args['showLabels'],
		'instance_id'         => $instance_id,
	];
}

/**
 * Render category swiper markup from a prepared items config.
 *
 * @param array<int, array{
 *     id?: int,
 *     title?: string,
 *     label?: string,
 *     url?: string,
 *     image_id?: int,
 * }> $items Swiper slides.
 * @param array<string, mixed> $args Display options.
 * @return string
 */
function chairforce_get_category_swiper_html( array $items, array $args = [] ): string {
	if ( empty( $items ) ) {
		return '';
	}

	$options = chairforce_normalize_category_swiper_args( $args );

	ob_start();
	?>
	<div
		class="cf-category-swiper__viewport"
		data-cf-category-swiper
		data-show-arrows-desktop="<?php echo $options['show_arrows_desktop'] ? 'true' : 'false'; ?>"
		data-show-arrows-mobile="<?php echo $options['show_arrows_mobile'] ? 'true' : 'false'; ?>"
		data-show-progress-bar="<?php echo $options['show_progress_bar'] ? 'true' : 'false'; ?>"
		data-show-labels="<?php echo $options['show_labels'] ? 'true' : 'false'; ?>"
		id="<?php echo esc_attr( $options['instance_id'] ); ?>"
	>
		<div class="cf-category-swiper__track">
			<div class="swiper cf-category-swiper__swiper">
				<div class="swiper-wrapper">
					<?php
					foreach ( $items as $item ) {
						echo chairforce_get_category_swiper_slide_html( $item, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
					}
					?>
				</div>
				<?php if ( $options['show_progress_bar'] ) : ?>
					<div class="swiper-scrollbar cf-category-swiper__scrollbar" aria-hidden="true"></div>
				<?php endif; ?>
			</div>
			<?php if ( $options['show_arrows_desktop'] || $options['show_arrows_mobile'] ) : ?>
				<div class="cf-category-swiper__nav" aria-hidden="false">
					<button
						type="button"
						class="cf-category-swiper__arrow cf-category-swiper__arrow--prev"
						aria-label="<?php esc_attr_e( 'Previous categories', 'chairforce' ); ?>"
					></button>
					<button
						type="button"
						class="cf-category-swiper__arrow cf-category-swiper__arrow--next"
						aria-label="<?php esc_attr_e( 'Next categories', 'chairforce' ); ?>"
					></button>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render a static flex row of category cards (editor preview — no Swiper JS).
 *
 * @param array<int, array<string, mixed>> $items Swiper slides.
 * @param array<string, mixed>             $args  Display options.
 * @return string
 */
function chairforce_get_category_swiper_flex_list_html( array $items, array $args = [] ): string {
	if ( empty( $items ) ) {
		return '';
	}

	$options = chairforce_normalize_category_swiper_args( $args );
	$options['static_slide'] = true;

	ob_start();
	?>
	<div class="cf-category-swiper__flex-list">
		<?php
		foreach ( $items as $item ) {
			echo chairforce_get_category_swiper_slide_html( $item, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		}
		?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render one swiper slide.
 *
 * @param array<string, mixed> $item Slide config.
 * @param array<string, mixed> $options Normalized display options.
 * @return string
 */
function chairforce_get_category_swiper_slide_html( array $item, array $options ): string {
	$url       = isset( $item['url'] ) ? (string) $item['url'] : '';
	$title     = isset( $item['title'] ) ? (string) $item['title'] : '';
	$label     = isset( $item['label'] ) ? (string) $item['label'] : $title;
	$image_id  = isset( $item['image_id'] ) ? absint( $item['image_id'] ) : 0;
	$item_id   = isset( $item['id'] ) ? absint( $item['id'] ) : 0;

	if ( '' === trim( $label ) || '' === trim( $url ) ) {
		return '';
	}

	$card_classes = 'cf-category-swiper__card';

	if ( empty( $options['show_labels'] ) ) {
		$card_classes .= ' cf-category-swiper__card--no-label';
	}

	$slide_classes = ! empty( $options['static_slide'] )
		? 'cf-category-swiper__slide cf-category-swiper__slide--static'
		: 'swiper-slide cf-category-swiper__slide';

	ob_start();
	?>
	<div class="<?php echo esc_attr( $slide_classes ); ?>"<?php echo $item_id > 0 ? ' data-term-id="' . esc_attr( (string) $item_id ) . '"' : ''; ?>>
		<a class="<?php echo esc_attr( $card_classes ); ?>" href="<?php echo esc_url( $url ); ?>">
			<span class="cf-category-swiper__media">
				<?php echo chairforce_get_category_swiper_slide_image_html( $image_id, $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
			</span>
			<?php if ( ! empty( $options['show_labels'] ) ) : ?>
				<span class="cf-category-swiper__label"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</a>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render a slide image from an attachment ID or theme placeholder.
 *
 * @param int    $image_id Attachment ID.
 * @param string $alt      Accessible image label.
 * @return string
 */
function chairforce_get_category_swiper_slide_image_html( int $image_id, string $alt ): string {
	if ( $image_id > 0 ) {
		$image = wp_get_attachment_image(
			$image_id,
			'thumbnail',
			false,
			[
				'class'    => 'cf-category-swiper__image',
				'alt'      => $alt,
				'loading'  => 'lazy',
				'decoding' => 'async',
			]
		);

		if ( $image ) {
			return $image;
		}
	}

	$placeholder_url = get_theme_file_uri( CHAIRFORCE_MENU_THUMB_PLACEHOLDER );

	return sprintf(
		'<img src="%s" class="cf-category-swiper__image cf-category-swiper__image--placeholder" alt="%s" loading="lazy" decoding="async" width="100" height="100" />',
		esc_url( $placeholder_url ),
		esc_attr( $alt )
	);
}
