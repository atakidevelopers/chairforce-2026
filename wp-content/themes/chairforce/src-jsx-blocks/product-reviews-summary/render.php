<?php
/**
 * Product reviews summary — server render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;

if ( ! $product_id ) {
	$product_id = get_the_ID();
}

if (
	! $product_id
	|| ! function_exists( 'wc_get_product' )
	|| ! wc_get_product( $product_id )
	|| ! chairforce_should_show_product_reviews( $product_id )
	|| ! chairforce_is_product_reviews_section_mode()
) {
	return;
}

$stats   = chairforce_get_product_review_rating_stats( $product_id );
$average = number_format( $stats['average'], 1 );
$total   = $stats['total'];

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-product-reviews-summary',
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cf-product-reviews-summary__inner">
		<div class="cf-product-reviews-summary__score-row">
			<span class="cf-product-reviews-summary__average"><?php echo esc_html( $average ); ?></span>
			<?php if ( wc_review_ratings_enabled() && $stats['average'] > 0 ) : ?>
				<div class="cf-product-reviews-summary__stars star-rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'chairforce' ), $average ) ); ?>">
					<?php echo chairforce_get_review_rating_html( (int) round( $stats['average'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
		<p class="cf-product-reviews-summary__count">
			<?php
			if ( $total > 0 ) {
				echo esc_html(
					sprintf(
						/* translators: %d: review count */
						_n( 'Based on %d review', 'Based on %d reviews', $total, 'chairforce' ),
						$total
					)
				);
			} else {
				esc_html_e( 'No reviews yet', 'chairforce' );
			}
			?>
		</p>
		<ul class="cf-product-reviews-summary__bars" aria-label="<?php esc_attr_e( 'Rating breakdown', 'chairforce' ); ?>">
			<?php foreach ( [ 5, 4, 3, 2, 1 ] as $star ) : ?>
				<?php
				$count   = $stats['counts'][ $star ];
				$percent = $total > 0 ? (int) round( ( $count / $total ) * 100 ) : 0;
				?>
				<li class="cf-product-reviews-summary__bar-row">
					<span class="cf-product-reviews-summary__bar-label"><?php echo esc_html( sprintf( _n( '%d star', '%d stars', $star, 'chairforce' ), $star ) ); ?></span>
					<span class="cf-product-reviews-summary__bar-track" aria-hidden="true">
						<span class="cf-product-reviews-summary__bar-fill" style="width: <?php echo esc_attr( (string) $percent ); ?>%;"></span>
					</span>
					<span class="cf-product-reviews-summary__bar-count"><?php echo esc_html( (string) $count ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		echo chairforce_get_product_reviews_write_review_button_html(
			[
				'wrapper_class' => 'cf-product-reviews-summary__actions',
				'element_class' => 'cf-product-reviews-summary__write-review',
			]
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
