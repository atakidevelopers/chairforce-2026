<?php
/**
 * Shared showroom card markup helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Chairforce\Showroom_Locator;

/**
 * Allowed minimal markup in opening hours.
 */
const CHAIRFORCE_SHOWROOM_CARD_TIME_ALLOWED_HTML = [
	'br' => [],
];

/**
 * Allowed minimal markup in addresses.
 */
const CHAIRFORCE_SHOWROOM_CARD_ADDRESS_ALLOWED_HTML = [
	'br'     => [],
	'b'      => [],
	'strong' => [],
];

/**
 * Build inline block comment markup for a showroom card (for do_blocks).
 *
 * @param array<string, mixed> $args Card configuration.
 * @return string
 */
function chairforce_get_showroom_card_blocks_markup( array $args = [] ): string {
	$defaults = [
		'ctaType'     => 'directions',
		'showImage'   => true,
		'showAddress' => true,
		'showTime'    => true,
		'showContact' => true,
		'className'   => '',
	];

	$args = wp_parse_args( $args, $defaults );

	$block_attrs = [
		'ctaType'     => sanitize_key( (string) $args['ctaType'] ),
		'showImage'   => (bool) $args['showImage'],
		'showAddress' => (bool) $args['showAddress'],
		'showTime'    => (bool) $args['showTime'],
		'showContact' => (bool) $args['showContact'],
	];

	$class_name = trim( (string) $args['className'] );

	if ( '' !== $class_name ) {
		$block_attrs['className'] = $class_name;
	}

	$json = wp_json_encode( $block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	return sprintf( '<!-- wp:chairforce/showroom-card %s /-->', $json );
}

/**
 * Resolve the showroom post ID for card rendering.
 *
 * Order: block query context → REST SSR query arg → loop global post.
 *
 * @param \WP_Block|null $block Block instance.
 * @return int
 */
function chairforce_resolve_showroom_card_post_id( $block = null ): int {
	if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
		return absint( $block->context['postId'] );
	}

	if ( chairforce_is_showroom_card_editor_preview() && ! empty( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( wp_unslash( $_GET['post_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$post_id = get_the_ID();

	return $post_id ? absint( $post_id ) : 0;
}

/**
 * Find mapped showroom data for a showroom post ID.
 *
 * @param int $post_id Showroom post ID.
 * @return array<string, mixed>|null
 */
function chairforce_get_showroom_by_post_id( int $post_id ): ?array {
	if ( $post_id <= 0 || 'showrooms' !== get_post_type( $post_id ) ) {
		return null;
	}

	foreach ( Showroom_Locator::get_showrooms() as $showroom ) {
		if ( (int) ( $showroom['post_id'] ?? 0 ) === $post_id ) {
			return $showroom;
		}
	}

	return null;
}

/**
 * Whether the current request is a block editor server render.
 *
 * @return bool
 */
function chairforce_is_showroom_card_editor_preview(): bool {
	return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

/**
 * Build a Google Maps search URL from a showroom address string.
 *
 * Appends the site title to help Maps disambiguate the destination.
 *
 * @param string $address Raw address (may contain limited HTML).
 * @return string Empty when address is missing.
 */
function chairforce_get_showroom_directions_url( string $address ): string {
	$plain = wp_strip_all_tags(
		str_replace(
			[ '<br>', '<br/>', '<br />', '<BR>', '<BR/>', '<BR />' ],
			', ',
			$address
		)
	);
	$plain = trim( preg_replace( '/\s+/', ' ', $plain ) ?? '' );

	if ( '' === $plain ) {
		return '';
	}

	$site_title = trim( (string) get_bloginfo( 'name' ) );

	if ( '' !== $site_title ) {
		$plain = $plain . ', ' . $site_title;
	}

	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $plain );
}

/**
 * Resolve which CTA button to render on the showroom card.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string directions|learn-more, or empty when hidden.
 */
function chairforce_resolve_showroom_card_cta_type( array $attributes ): string {
	if ( isset( $attributes['ctaType'] ) ) {
		$cta_type = sanitize_key( (string) $attributes['ctaType'] );

		return in_array( $cta_type, [ 'directions', 'learn-more' ], true ) ? $cta_type : 'directions';
	}

	// Legacy attribute from earlier block versions.
	if ( array_key_exists( 'showCta', $attributes ) && ! $attributes['showCta'] ) {
		return '';
	}

	return 'directions';
}

/**
 * Render showroom card CTA button group (single selected button).
 *
 * @param array<string, mixed> $showroom Mapped showroom data.
 * @param string               $cta_type Selected CTA type.
 * @return string
 */
function chairforce_render_showroom_card_cta( array $showroom, string $cta_type ): string {
	$buttons = [];

	if ( 'directions' === $cta_type ) {
		$directions_url = chairforce_get_showroom_directions_url( (string) ( $showroom['address'] ?? '' ) );

		if ( '' !== $directions_url ) {
			$buttons[] = [
				'label'    => __( 'Get Directions', 'chairforce' ),
				'url'      => $directions_url,
				'style'    => 'is-style-primary',
				'external' => true,
				'icon'     => 'map-pin',
			];
		}
	}

	if ( 'learn-more' === $cta_type ) {
		$permalink = (string) ( $showroom['permalink'] ?? '' );

		if ( '' !== $permalink ) {
			$buttons[] = [
				'label'         => sprintf(
					/* translators: %s: showroom city label, e.g. Sydney */
					__( 'Learn Our %s Showroom', 'chairforce' ),
					(string) ( $showroom['label'] ?? '' )
				),
				'url'           => $permalink,
				'style'         => 'is-style-ghost',
				'icon'          => 'arrow-right',
				'icon_position' => 'right',
			];
		}
	}

	if ( empty( $buttons ) ) {
		return '';
	}

	$markup = chairforce_get_buttons_markup(
		$buttons,
		[
			'wrapper_class' => 'cf-showroom-card__actions',
			'layout'        => [
				'type'            => 'flex',
				'justifyContent'  => 'stretch',
				'flexWrap'        => 'wrap',
			],
		]
	);

	if ( '' === $markup ) {
		return '';
	}

	return '<div class="cf-showroom-card__cta">' . $markup . '</div>';
}

/**
 * Render a showroom card for the chairforce/showroom-card block.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param \WP_Block|null       $block      Block instance.
 * @return string
 */
function chairforce_render_showroom_card( array $attributes, $block = null ): string {
	$post_id  = chairforce_resolve_showroom_card_post_id( $block );
	$showroom = chairforce_get_showroom_by_post_id( $post_id );

	if ( empty( $showroom ) ) {
		if ( chairforce_is_showroom_card_editor_preview() ) {
			return chairforce_render_showroom_card_empty_notice( $post_id );
		}

		return '';
	}

	$showroom_key = (string) ( $showroom['key'] ?? '' );

	$cta_type     = chairforce_resolve_showroom_card_cta_type( $attributes );
	$show_image   = ! array_key_exists( 'showImage', $attributes ) || (bool) $attributes['showImage'];
	$show_address = ! array_key_exists( 'showAddress', $attributes ) || (bool) $attributes['showAddress'];
	$show_time    = ! array_key_exists( 'showTime', $attributes ) || (bool) $attributes['showTime'];
	$show_contact = ! array_key_exists( 'showContact', $attributes ) || (bool) $attributes['showContact'];

	$wrapper_classes = 'cf-showroom-card';

	if ( 'directions' === $cta_type ) {
		$wrapper_classes .= ' cf-showroom-card--cta-directions';
	} elseif ( 'learn-more' === $cta_type ) {
		$wrapper_classes .= ' cf-showroom-card--cta-learn-more';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		[
			'class' => $wrapper_classes,
		],
		$block
	);

	ob_start();
	?>
	<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-showroom-location="<?php echo esc_attr( $showroom_key ); ?>">
		<?php
		if ( $show_image ) {
			echo chairforce_render_showroom_card_media( $showroom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<div class="cf-showroom-card__content">
			<h3 class="cf-showroom-card__title"><?php echo esc_html( (string) $showroom['warehouse'] ); ?></h3>
			<?php
			if ( $show_address && '' !== trim( (string) $showroom['address'] ) ) {
				echo chairforce_render_showroom_card_detail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'map-pin',
					wp_kses( (string) $showroom['address'], CHAIRFORCE_SHOWROOM_CARD_ADDRESS_ALLOWED_HTML )
				);
			}

			if ( $show_time && '' !== trim( (string) $showroom['time'] ) ) {
				echo chairforce_render_showroom_card_detail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'clock',
					wp_kses( (string) $showroom['time'], CHAIRFORCE_SHOWROOM_CARD_TIME_ALLOWED_HTML )
				);
			}

			if ( $show_contact ) {
				echo chairforce_render_showroom_card_contact( $showroom ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( '' !== $cta_type ) {
				echo chairforce_render_showroom_card_cta( $showroom, $cta_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Editor-only empty state when no loop showroom is available.
 *
 * @param int $post_id Requested post ID, if any.
 * @return string
 */
function chairforce_render_showroom_card_empty_notice( int $post_id ): string {
	if ( $post_id > 0 ) {
		$message = sprintf(
			/* translators: %d: showroom post ID */
			__( 'No mapped showroom data for post ID %d. The post must be a published showroom with a supported id meta value.', 'chairforce' ),
			$post_id
		);
	} else {
		$message = __( 'Insert this block inside a Query Loop for the showrooms post type so it can render the current loop item.', 'chairforce' );
	}

	return sprintf(
		'<div class="cf-showroom-card cf-showroom-card--empty"><p>%s</p></div>',
		esc_html( $message )
	);
}

/**
 * Render card media.
 *
 * @param array<string, mixed> $showroom Mapped showroom data.
 * @return string
 */
function chairforce_render_showroom_card_media( array $showroom ): string {
	$image_id = isset( $showroom['image_id'] ) ? absint( $showroom['image_id'] ) : 0;
	$image    = '';

	if ( $image_id > 0 ) {
		$image = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			[
				'class'    => 'cf-showroom-card__image',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => (string) $showroom['warehouse'],
			]
		);
	}

	if ( ! $image ) {
		$placeholder_url = get_theme_file_uri( 'assets/images/menu-thumb-placeholder.png' );

		$image = sprintf(
			'<img class="cf-showroom-card__image" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			esc_url( $placeholder_url ),
			esc_attr( (string) $showroom['warehouse'] )
		);
	}

	$media_class = 'cf-showroom-card__media';

	if ( $image_id <= 0 ) {
		$media_class .= ' cf-showroom-card__media--placeholder';
	}

	return '<div class="' . esc_attr( $media_class ) . '">' . $image . '</div>';
}

/**
 * Render one detail row.
 *
 * @param string $icon_slug Lucide icon slug.
 * @param string $text      Detail text (already kses'd when needed).
 * @return string
 */
function chairforce_render_showroom_card_detail( string $icon_slug, string $text ): string {
	$icon_slug = sanitize_key( $icon_slug );

	return sprintf(
		'<div class="cf-showroom-card__detail"><span class="cf-icon-preview cf-icon-%1$s" aria-hidden="true"></span><span class="cf-showroom-card__detail-text">%2$s</span></div>',
		esc_attr( $icon_slug ),
		$text
	);
}

/**
 * Render phone and email contact row.
 *
 * @param array<string, mixed> $showroom Mapped showroom data.
 * @return string
 */
function chairforce_render_showroom_card_contact( array $showroom ): string {
	$has_phone = '' !== trim( (string) ( $showroom['phone'] ?? '' ) ) && '' !== (string) ( $showroom['phone_href'] ?? '' );
	$has_email = '' !== (string) ( $showroom['email'] ?? '' );

	if ( ! $has_phone && ! $has_email ) {
		return '';
	}

	$leading_icon = $has_phone ? 'phone' : 'mail';

	$markup  = '<div class="cf-showroom-card__detail cf-showroom-card__detail--contact">';
	$markup .= sprintf(
		'<span class="cf-icon-preview cf-icon-%1$s" aria-hidden="true"></span>',
		esc_attr( $leading_icon )
	);
	$markup .= '<span class="cf-showroom-card__contact-line">';

	if ( $has_phone ) {
		$markup .= sprintf(
			'<a class="cf-showroom-card__detail-link" href="tel:%1$s">%2$s</a>',
			esc_attr( (string) $showroom['phone_href'] ),
			esc_html( (string) $showroom['phone'] )
		);
	}

	if ( $has_email ) {
		if ( $has_phone ) {
			$markup .= '<span class="cf-showroom-card__email-item">';
			$markup .= '<span class="cf-icon-preview cf-icon-mail" aria-hidden="true"></span>';
		}

		$markup .= sprintf(
			'<a class="cf-showroom-card__detail-link" href="mailto:%1$s">%2$s</a>',
			esc_attr( (string) $showroom['email'] ),
			esc_html( antispambot( (string) $showroom['email'] ) )
		);

		if ( $has_phone ) {
			$markup .= '</span>';
		}
	}

	$markup .= '</span>';
	$markup .= '</div>';

	return $markup;
}
