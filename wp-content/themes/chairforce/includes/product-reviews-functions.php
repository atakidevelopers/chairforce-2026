<?php
/**
 * Product reviews helpers (Phase 3o).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a boolean theme option with a default when unset.
 *
 * @param string $field_name ACF field name.
 * @param bool   $default    Default when ACF missing or value empty.
 * @return bool
 */
function chairforce_get_theme_option_bool( string $field_name, bool $default = true ): bool {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $field_name, 'option' );

	if ( null === $value || '' === $value ) {
		return $default;
	}

	return (bool) $value;
}

/**
 * Whether product reviews are enabled globally (WooCommerce setting).
 */
function chairforce_is_product_reviews_enabled(): bool {
	return get_option( 'woocommerce_enable_reviews' ) === 'yes';
}

/**
 * Product reviews display mode: section or tab.
 */
function chairforce_get_product_reviews_display_mode(): string {
	if ( ! function_exists( 'get_field' ) ) {
		return 'section';
	}

	$value = get_field( 'product_reviews_display', 'option' );

	if ( ! in_array( $value, [ 'tab', 'section' ], true ) ) {
		return 'section';
	}

	return $value;
}

/**
 * Whether reviews render as the Figma standalone section (blocks).
 */
function chairforce_is_product_reviews_section_mode(): bool {
	return chairforce_is_product_reviews_enabled()
		&& 'section' === chairforce_get_product_reviews_display_mode();
}

/**
 * Whether reviews render in the WooCommerce Reviews tab only.
 */
function chairforce_is_product_reviews_tab_mode(): bool {
	return chairforce_is_product_reviews_enabled()
		&& 'tab' === chairforce_get_product_reviews_display_mode();
}

/**
 * Whether reviews UI should render for a product (global + per-product).
 *
 * @param int $product_id Product post ID.
 * @return bool
 */
function chairforce_should_show_product_reviews( int $product_id ): bool {
	if ( ! chairforce_is_product_reviews_enabled() ) {
		return false;
	}

	if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
		return false;
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return false;
	}

	return $product->get_reviews_allowed();
}

/**
 * Whether a custom single-product tab is enabled in theme options.
 *
 * @param string $tab_key WooCommerce tab key.
 * @return bool
 */
function chairforce_is_product_tab_enabled( string $tab_key ): bool {
	$map = [
		'description'               => 'product_tab_overview_enabled',
		'cf_delivery_information'   => 'product_tab_delivery_enabled',
		'cf_dimensions'             => 'product_tab_dimensions_enabled',
		'cf_care'                   => 'product_tab_care_enabled',
		'cf_parts'                  => 'product_tab_parts_enabled',
		'cf_additional_information' => 'product_tab_additional_info_enabled',
		'cf_product_info'           => 'product_tab_product_info_enabled',
	];

	if ( ! isset( $map[ $tab_key ] ) ) {
		return true;
	}

	return chairforce_get_theme_option_bool( $map[ $tab_key ], true );
}

/**
 * Base args for approved product review comments.
 *
 * @param int $product_id Product post ID.
 * @return array<string, mixed>
 */
function chairforce_get_product_review_query_args( int $product_id ): array {
	return [
		'post_id' => $product_id,
		'status'  => 'approve',
		'type'    => 'review',
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	];
}

/**
 * Fetch paginated approved review comments for a product.
 *
 * @param int                  $product_id Product post ID.
 * @param array<string, mixed> $args       Optional overrides (number, offset, paged).
 * @return WP_Comment[]
 */
function chairforce_get_product_review_comments( int $product_id, array $args = [] ): array {
	if ( $product_id <= 0 ) {
		return [];
	}

	$query_args = array_merge(
		chairforce_get_product_review_query_args( $product_id ),
		$args
	);

	$comments = get_comments( $query_args );

	return is_array( $comments ) ? $comments : [];
}

/**
 * Count approved review comments for a product.
 *
 * @param int $product_id Product post ID.
 * @return int
 */
function chairforce_get_product_review_comment_count( int $product_id ): int {
	if ( $product_id <= 0 ) {
		return 0;
	}

	return (int) get_comments(
		array_merge(
			chairforce_get_product_review_query_args( $product_id ),
			[ 'count' => true ]
		)
	);
}

/**
 * Average rating, total count, and per-star histogram.
 *
 * @param int $product_id Product post ID.
 * @return array{average: float, total: int, counts: array<int, int>}
 */
function chairforce_get_product_review_rating_stats( int $product_id ): array {
	$counts = [
		5 => 0,
		4 => 0,
		3 => 0,
		2 => 0,
		1 => 0,
	];

	$average = 0.0;
	$total   = 0;

	if ( $product_id <= 0 || ! function_exists( 'wc_get_product' ) ) {
		return [
			'average' => $average,
			'total'   => $total,
			'counts'  => $counts,
		];
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return [
			'average' => $average,
			'total'   => $total,
			'counts'  => $counts,
		];
	}

	$average = (float) $product->get_average_rating();
	$total   = (int) $product->get_review_count();

	$comments = chairforce_get_product_review_comments( $product_id );

	foreach ( $comments as $comment ) {
		$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );

		if ( $rating >= 1 && $rating <= 5 ) {
			++$counts[ $rating ];
		}
	}

	return [
		'average' => $average,
		'total'   => $total,
		'counts'  => $counts,
	];
}

/**
 * Current review list page from query string (native cpage var).
 *
 * @return int
 */
function chairforce_get_product_review_page(): int {
	$paged = get_query_var( 'cpage' );

	if ( ! $paged && isset( $_GET['cpage'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = absint( wp_unslash( $_GET['cpage'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return max( 1, (int) $paged );
}

/**
 * Build star rating markup for a review.
 *
 * @param int $rating Rating 1–5.
 * @return string
 */
function chairforce_get_review_rating_html( int $rating ): string {
	if ( ! function_exists( 'wc_get_rating_html' ) || ! wc_review_ratings_enabled() ) {
		return '';
	}

	return wc_get_rating_html( $rating );
}

/**
 * Author initials for review avatar placeholder.
 *
 * @param string $author Comment author name.
 * @return string
 */
function chairforce_get_review_author_initials( string $author ): string {
	$parts    = preg_split( '/\s+/', trim( $author ) );
	$initials = '';

	if ( ! is_array( $parts ) ) {
		return '?';
	}

	foreach ( array_slice( $parts, 0, 2 ) as $part ) {
		if ( '' !== $part ) {
			$initials .= strtoupper( mb_substr( $part, 0, 1 ) );
		}
	}

	return '' !== $initials ? $initials : '?';
}

/**
 * Render a single review card.
 *
 * @param WP_Comment $comment Review comment.
 * @return void
 */
function chairforce_render_product_review_item( WP_Comment $comment ): void {
	$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
	$author = get_comment_author( $comment );
	$date   = get_comment_date( '', $comment );
	?>
	<li id="comment-<?php echo esc_attr( (string) $comment->comment_ID ); ?>" class="cf-product-reviews__item">
		<div class="cf-product-reviews__item-header">
			<div class="cf-product-reviews__author">
				<span class="cf-product-reviews__avatar" aria-hidden="true"><?php echo esc_html( chairforce_get_review_author_initials( $author ) ); ?></span>
				<span class="cf-product-reviews__author-name"><?php echo esc_html( $author ); ?></span>
				<?php if ( function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $comment->comment_ID ) ) : ?>
					<span class="cf-product-reviews__verified"><?php esc_html_e( 'Verified Purchase', 'chairforce' ); ?></span>
				<?php endif; ?>
			</div>
			<time class="cf-product-reviews__date" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>"><?php echo esc_html( $date ); ?></time>
		</div>
		<?php if ( $rating > 0 ) : ?>
			<div class="cf-product-reviews__rating star-rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %d out of 5', 'chairforce' ), $rating ) ); ?>">
				<?php echo chairforce_get_review_rating_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
		<div class="cf-product-reviews__content">
			<?php echo wp_kses_post( $comment->comment_content ); ?>
		</div>
	</li>
	<?php
}

/**
 * Render review pagination links.
 *
 * @param int $product_id     Product post ID.
 * @param int $reviews_per_page Reviews per page.
 * @return void
 */
function chairforce_render_product_review_pagination( int $product_id, int $reviews_per_page ): void {
	$total       = chairforce_get_product_review_comment_count( $product_id );
	$total_pages = (int) ceil( $total / max( 1, $reviews_per_page ) );
	$current     = chairforce_get_product_review_page();

	if ( $total_pages <= 1 ) {
		return;
	}

	$links = paginate_links(
		[
			'base'      => esc_url_raw( add_query_arg( 'cpage', '%#%' ) ),
			'format'    => '',
			'current'   => $current,
			'total'     => $total_pages,
			'type'      => 'list',
			'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
			'next_text' => is_rtl() ? '&larr;' : '&rarr;',
		]
	);

	if ( ! $links ) {
		return;
	}

	printf(
		'<nav class="cf-product-reviews__pagination woocommerce-pagination" aria-label="%1$s">%2$s</nav>',
		esc_attr__( 'Product reviews pagination', 'chairforce' ),
		$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Output the WooCommerce review submission form.
 *
 * @param int $product_id Product post ID.
 * @return void
 */
function chairforce_render_product_review_form( int $product_id ): void {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$product = wc_get_product( $product_id );

	if ( ! $product || ! $product->get_reviews_allowed() ) {
		return;
	}

	$can_review = get_option( 'woocommerce_review_rating_verification_required' ) === 'no'
		|| wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );

	if ( ! $can_review ) {
		echo '<p class="woocommerce-verification-required">' . esc_html__( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ) . '</p>';
		return;
	}

	$commenter    = wp_get_current_commenter();
	$comment_form = [
		/* translators: %s is product title */
		'title_reply'         => chairforce_get_product_review_comment_count( $product_id ) > 0
			? esc_html__( 'Add a review', 'woocommerce' )
			: sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title( $product_id ) ),
		'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
		'title_reply_before'  => '<span id="reply-title" class="comment-reply-title" role="heading" aria-level="3">',
		'title_reply_after'   => '</span>',
		'comment_notes_after' => '',
		'label_submit'        => esc_html__( 'Submit', 'woocommerce' ),
		'logged_in_as'        => '',
		'comment_field'       => '',
	];

	$name_email_required = (bool) get_option( 'require_name_email', 1 );
	$fields              = [
		'author' => [
			'label'        => __( 'Name', 'woocommerce' ),
			'type'         => 'text',
			'value'        => $commenter['comment_author'],
			'required'     => $name_email_required,
			'autocomplete' => 'name',
		],
		'email'  => [
			'label'        => __( 'Email', 'woocommerce' ),
			'type'         => 'email',
			'value'        => $commenter['comment_author_email'],
			'required'     => $name_email_required,
			'autocomplete' => 'email',
		],
	];

	$comment_form['fields'] = [];

	foreach ( $fields as $key => $field ) {
		$field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
		$field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

		if ( $field['required'] ) {
			$field_html .= '&nbsp;<span class="required">*</span>';
		}

		$field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" autocomplete="' . esc_attr( $field['autocomplete'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

		$comment_form['fields'][ $key ] = $field_html;
	}

	$account_page_url = wc_get_page_permalink( 'myaccount' );

	if ( $account_page_url ) {
		/* translators: %s opening and closing link tags respectively */
		$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
	}

	if ( wc_review_ratings_enabled() ) {
		$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Your rating', 'woocommerce' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" required>
			<option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
			<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>
			<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>
			<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>
			<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>
			<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>
		</select></div>';
	}

	$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

	global $post;
	$previous_post = $post;
	$post          = get_post( $product_id );

	if ( ! $post ) {
		return;
	}

	setup_postdata( $post );
	?>
	<div id="review_form_wrapper">
		<div id="review_form">
			<?php
			comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
			?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
	$post = $previous_post;
}

/**
 * Build the Write a Review button markup.
 *
 * @param array<string, mixed> $args Optional overrides (wrapper_class, style, element_class).
 * @return string
 */
function chairforce_get_product_reviews_write_review_button_html( array $args = [] ): string {
	$wrapper_class = isset( $args['wrapper_class'] ) ? (string) $args['wrapper_class'] : 'cf-product-reviews__write-review-actions';
	$style         = isset( $args['style'] ) ? (string) $args['style'] : 'is-style-primary';
	$element_class = isset( $args['element_class'] ) ? (string) $args['element_class'] : 'cf-product-reviews__write-review';

	return chairforce_get_buttons_markup(
		[
			[
				'label'           => __( 'Write a Review', 'chairforce' ),
				'url'             => '#reviews',
				'style'           => $style,
				'element_class'   => $element_class,
				'html_attributes' => [
					'data-cf-show-review-form' => 'true',
				],
			],
		],
		[
			'wrapper_class' => $wrapper_class,
			'layout'        => [
				'type'           => 'flex',
				'justifyContent' => 'stretch',
			],
		]
	);
}

/**
 * Render the product reviews summary block with explicit post context.
 *
 * Uses WP_Block so postId context is available to the nested dynamic block.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_render_product_reviews_summary_block( int $product_id ): string {
	if ( $product_id <= 0 || ! class_exists( 'WP_Block' ) ) {
		return '';
	}

	$parsed_blocks = parse_blocks( '<!-- wp:chairforce/product-reviews-summary /-->' );

	if ( empty( $parsed_blocks[0]['blockName'] ) ) {
		return '';
	}

	$block = new WP_Block(
		$parsed_blocks[0],
		[
			'postId'   => $product_id,
			'postType' => 'product',
		]
	);

	return $block->render();
}

/**
 * Build the review list + form column HTML for the product reviews block.
 *
 * @param int  $product_id       Product post ID.
 * @param int  $reviews_per_page Reviews per page.
 * @param bool $show_write_button Show Write a Review when summary is hidden.
 * @return string
 */
function chairforce_get_product_reviews_main_column_html( int $product_id, int $reviews_per_page, bool $show_write_button ): string {
	$reviews_per_page = max( 1, $reviews_per_page );
	$total            = chairforce_get_product_review_comment_count( $product_id );
	$paged            = chairforce_get_product_review_page();
	$comments         = chairforce_get_product_review_comments(
		$product_id,
		[
			'number' => $reviews_per_page,
			'offset' => ( $paged - 1 ) * $reviews_per_page,
		]
	);

	$has_reviews = $total > 0;

	ob_start();
	?>
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
	<?php
	return (string) ob_get_clean();
}

/**
 * Serialized core/columns markup for the product reviews section.
 *
 * @param string $main_html     Review list column HTML.
 * @param string $summary_html  Summary block render output.
 * @param bool   $show_summary  Whether to include the summary column.
 * @return string
 */
function chairforce_get_product_reviews_columns_blocks_markup( string $main_html, string $summary_html, bool $show_summary ): string {
	if ( '' === trim( $main_html ) ) {
		return '';
	}

	if ( $show_summary && '' !== trim( $summary_html ) ) {
		return '<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%">' . $summary_html . '</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">' . $main_html . '</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->';
	}

	return '<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column">' . $main_html . '</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->';
}
