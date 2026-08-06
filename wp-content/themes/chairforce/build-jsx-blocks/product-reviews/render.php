<?php
/**
 * Product reviews section — server render.
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

$reviews_per_page = isset( $attributes['reviewsPerPage'] ) ? absint( $attributes['reviewsPerPage'] ) : 3;

if ( $reviews_per_page < 1 ) {
	$reviews_per_page = 3;
}

$display_reviews_summary = ! isset( $attributes['displayReviewsSummary'] ) || $attributes['displayReviewsSummary'];
$show_write_button       = ! $display_reviews_summary;

$total    = chairforce_get_product_review_comment_count( $product_id );
$paged    = chairforce_get_product_review_page();
$comments = chairforce_get_product_review_comments(
	$product_id,
	[
		'number' => $reviews_per_page,
		'offset' => ( $paged - 1 ) * $reviews_per_page,
	]
);

$has_reviews   = $total > 0;
$summary_html = '';

if ( $display_reviews_summary ) {
	$summary_html = chairforce_render_product_reviews_summary_block( $product_id );
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'id'    => 'reviews',
		'class' => 'woocommerce-Reviews cf-product-reviews is-layout-constrained has-global-padding',
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $display_reviews_summary && '' !== trim( $summary_html ) ) : ?>
		<div class="cf-product-reviews__layout alignwide">
			<div class="cf-product-reviews__summary-column">
				<?php echo $summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
			</div>
			<div class="cf-product-reviews__main-column">
	<?php endif; ?>

	<div class="cf-product-reviews__inner">
		<div class="cf-product-reviews__header">
			<h2 class="cf-product-reviews__title">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: review count */
						_n( '%d Review', '%d Reviews', $total, 'chairforce' ),
						$total
					)
				);
				?>
			</h2>
		</div>

		<div id="comments" class="cf-product-reviews__comments">
			<?php if ( $has_reviews ) : ?>
				<ol class="cf-product-reviews__list commentlist">
					<?php
					foreach ( $comments as $comment ) {
						chairforce_render_product_review_item( $comment );
					}
					?>
				</ol>
				<?php chairforce_render_product_review_pagination( $product_id, $reviews_per_page ); ?>
			<?php else : ?>
				<p class="cf-product-reviews__empty woocommerce-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'woocommerce' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $show_write_button ) : ?>
			<?php echo chairforce_get_product_reviews_write_review_button_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<div class="cf-product-reviews__form-wrapper cf-product-reviews__form-wrapper--hidden">
			<?php chairforce_render_product_review_form( $product_id ); ?>
		</div>
	</div>

	<?php if ( $display_reviews_summary && '' !== trim( $summary_html ) ) : ?>
			</div>
		</div>
	<?php endif; ?>
</div>
