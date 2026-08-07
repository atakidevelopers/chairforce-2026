<?php
/**
 * Testimonials Carousel block rendering and data helpers.
 *
 * @package Chairforce
 */

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side testimonials carousel markup.
 */
class Testimonials_Carousel {

	/**
	 * Verified badge label (fallback when Figma is unavailable).
	 */
	private const VERIFIED_LABEL = 'Verified';

	/**
	 * Render the testimonials carousel block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param \WP_Block|null       $block      Block instance for wrapper attributes.
	 * @return string
	 */
	public static function render( array $attributes, $block = null ): string {
		$reviews    = self::get_reviews();
		$is_editor  = self::is_editor_request();

		if ( empty( $reviews ) ) {
			if ( $is_editor ) {
				return self::render_editor_empty_notice();
			}

			return '';
		}

		$viewport_id = wp_unique_id( 'cf-testimonials-carousel-' );
		$total       = count( $reviews );

		$wrapper_attributes = get_block_wrapper_attributes( [], $block );

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				class="cf-testimonials-carousel"
				aria-label="<?php esc_attr_e( 'Customer testimonials', 'chairforce' ); ?>"
				aria-roledescription="carousel"
			>
				<div
					id="<?php echo esc_attr( $viewport_id ); ?>"
					class="cf-testimonials-carousel__viewport swiper"
					data-slide-count="<?php echo esc_attr( (string) $total ); ?>"
				>
					<div class="cf-testimonials-carousel__track swiper-wrapper">
						<?php
						foreach ( $reviews as $index => $review ) {
							echo self::render_card_slide( $review, $index + 1, $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>

				<?php echo self::render_controls( $viewport_id, $total > 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Query and normalize published review posts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_reviews(): array {
		$query = new \WP_Query(
			[
				'post_type'              => 'review',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);

		$reviews = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$normalized = self::normalize_review( get_the_ID() );

				if ( null !== $normalized ) {
					$reviews[] = $normalized;
				}
			}

			wp_reset_postdata();
		}

		return $reviews;
	}

	/**
	 * Normalize one review post into render-ready data.
	 *
	 * @param int $review_id Review post ID.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_review( int $review_id ): ?array {
		$text = get_post_meta( $review_id, 'text', true );

		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return null;
		}

		$name     = get_the_title( $review_id );
		$title    = get_post_meta( $review_id, 'review_title', true );
		$location = get_post_meta( $review_id, 'review_location', true );
		$stars    = absint( get_post_meta( $review_id, 'stars', true ) );
		$verified = self::is_verified_meta( get_post_meta( $review_id, 'verified', true ) );

		if ( $stars < 1 || $stars > 5 ) {
			$stars = 0;
		}

		return [
			'id'       => $review_id,
			'name'     => is_string( $name ) ? trim( $name ) : '',
			'title'    => is_string( $title ) ? trim( $title ) : '',
			'text'     => trim( $text ),
			'location' => is_string( $location ) ? trim( $location ) : '',
			'stars'    => $stars,
			'verified' => $verified,
		];
	}

	/**
	 * Determine whether verified meta is enabled.
	 *
	 * @param mixed $value Raw meta value.
	 * @return bool
	 */
	private static function is_verified_meta( $value ): bool {
		if ( true === $value ) {
			return true;
		}

		if ( is_string( $value ) && in_array( $value, [ '1', 'true', 'yes', 'on' ], true ) ) {
			return true;
		}

		return 1 === absint( $value );
	}

	/**
	 * Render one slide wrapper and card.
	 *
	 * @param array<string, mixed> $review      Normalized review data.
	 * @param int                  $position    1-based slide position.
	 * @param int                  $total_slides Total slide count.
	 * @return string
	 */
	private static function render_card_slide( array $review, int $position, int $total_slides ): string {
		$slide_label = sprintf(
			/* translators: 1: current slide number, 2: total slides */
			__( '%1$s of %2$s', 'chairforce' ),
			(string) $position,
			(string) $total_slides
		);

		return sprintf(
			'<div class="cf-testimonials-carousel__slide swiper-slide" role="group" aria-roledescription="slide" aria-label="%1$s">%2$s</div>',
			esc_attr( $slide_label ),
			self::render_card( $review )
		);
	}

	/**
	 * Render one testimonial card.
	 *
	 * @param array<string, mixed> $review Normalized review data.
	 * @return string
	 */
	private static function render_card( array $review ): string {
		$rating_markup = self::render_rating( (int) $review['stars'] );

		ob_start();
		?>
		<article class="cf-testimonial-card">
			<?php if ( '' !== $rating_markup ) : ?>
				<?php echo $rating_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php if ( '' !== (string) $review['title'] ) : ?>
				<h3 class="cf-testimonial-card__title"><?php echo esc_html( (string) $review['title'] ); ?></h3>
			<?php endif; ?>

			<blockquote class="cf-testimonial-card__quote">
				<?php echo wpautop( esc_html( (string) $review['text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</blockquote>

			<?php if ( '' !== (string) $review['name'] || '' !== (string) $review['location'] || ! empty( $review['verified'] ) ) : ?>
				<footer class="cf-testimonial-card__footer">
					<?php if ( '' !== (string) $review['name'] || '' !== (string) $review['location'] ) : ?>
						<div class="cf-testimonial-card__person">
							<?php if ( '' !== (string) $review['name'] ) : ?>
								<p class="cf-testimonial-card__name"><?php echo esc_html( (string) $review['name'] ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== (string) $review['location'] ) : ?>
								<p class="cf-testimonial-card__location"><?php echo esc_html( (string) $review['location'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php echo self::render_verified_badge( ! empty( $review['verified'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</footer>
			<?php endif; ?>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render accessible star rating markup.
	 *
	 * @param int $stars Rating from 1 to 5.
	 * @return string
	 */
	private static function render_rating( int $stars ): string {
		if ( $stars < 1 || $stars > 5 ) {
			return '';
		}

		$label = sprintf(
			/* translators: %d: star rating out of five */
			__( 'Rated %d out of 5', 'chairforce' ),
			$stars
		);

		$stars_markup = '';

		for ( $index = 1; $index <= 5; $index++ ) {
			$class = 'cf-testimonial-card__star';

			if ( $index <= $stars ) {
				$class .= ' is-active';
			}

			$glyph = $index <= $stars ? '&#9733;' : '&#9734;';

			$stars_markup .= sprintf(
				'<span class="%1$s" aria-hidden="true">%2$s</span>',
				esc_attr( $class ),
				$glyph
			);
		}

		return sprintf(
			'<div class="cf-testimonial-card__rating" aria-label="%1$s">%2$s</div>',
			esc_attr( $label ),
			$stars_markup
		);
	}

	/**
	 * Render verified badge markup.
	 *
	 * @param bool $is_verified Whether the review is verified.
	 * @return string
	 */
	private static function render_verified_badge( bool $is_verified ): string {
		if ( ! $is_verified ) {
			return '';
		}

		return sprintf(
			'<p class="cf-testimonial-card__verified"><span class="cf-icon-preview cf-icon-shield-check" aria-hidden="true"></span><span class="cf-testimonial-card__verified-text">%s</span></p>',
			esc_html( self::VERIFIED_LABEL )
		);
	}

	/**
	 * Render carousel controls.
	 *
	 * @param string $viewport_id Viewport element ID.
	 * @param bool   $show_controls Whether navigation should be rendered.
	 * @return string
	 */
	private static function render_controls( string $viewport_id, bool $show_controls ): string {
		if ( ! $show_controls ) {
			return '';
		}

		ob_start();
		?>
		<div class="cf-testimonials-carousel__controls">
			<button
				type="button"
				class="cf-testimonials-carousel__arrow cf-testimonials-carousel__arrow--previous"
				aria-label="<?php esc_attr_e( 'Previous testimonial', 'chairforce' ); ?>"
				aria-controls="<?php echo esc_attr( $viewport_id ); ?>"
			>
				<span class="cf-icon-preview cf-icon-chevron-left" aria-hidden="true"></span>
			</button>

			<div class="cf-testimonials-carousel__scrollbar swiper-scrollbar" aria-hidden="true"></div>

			<button
				type="button"
				class="cf-testimonials-carousel__arrow cf-testimonials-carousel__arrow--next"
				aria-label="<?php esc_attr_e( 'Next testimonial', 'chairforce' ); ?>"
				aria-controls="<?php echo esc_attr( $viewport_id ); ?>"
			>
				<span class="cf-icon-preview cf-icon-chevron-right" aria-hidden="true"></span>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Whether the current request is a block editor server render.
	 *
	 * @return bool
	 */
	private static function is_editor_request(): bool {
		return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * Editor-only empty state notice.
	 *
	 * @return string
	 */
	private static function render_editor_empty_notice(): string {
		return '<div class="cf-testimonials-carousel cf-testimonials-carousel--empty"><p>' . esc_html__(
			'No published testimonials with valid text were found. Add testimonials under Reviews in WordPress Admin.',
			'chairforce'
		) . '</p></div>';
	}
}
