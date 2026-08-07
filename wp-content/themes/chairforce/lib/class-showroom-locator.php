<?php
/**
 * Showroom Locator block rendering and data helpers.
 *
 * @package Chairforce
 */

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side showroom locator markup.
 */
class Showroom_Locator {

	/**
	 * Allowed minimal markup in opening hours.
	 */
	private const TIME_ALLOWED_HTML = [
		'br' => [],
	];

	/**
	 * Allowed minimal markup in addresses.
	 */
	private const ADDRESS_ALLOWED_HTML = [
		'br'     => [],
		'b'      => [],
		'strong' => [],
	];

	/**
	 * Render the showroom locator block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param \WP_Block|null       $block      Block instance for wrapper attributes.
	 * @return string
	 */
	public static function render( array $attributes, $block = null ): string {
		$show_cta         = ! array_key_exists( 'showCta', $attributes ) || (bool) $attributes['showCta'];
		$requested_default = isset( $attributes['defaultLocation'] )
			? sanitize_key( (string) $attributes['defaultLocation'] )
			: 'brisbane';

		$showrooms   = self::get_showrooms();
		$is_editor   = self::is_editor_preview_request();
		$instance_id = wp_unique_id( 'cf-showroom-locator-' );

		if ( empty( $showrooms ) ) {
			if ( $is_editor ) {
				return self::render_editor_empty_notice();
			}

			return '';
		}

		$active_location = self::resolve_default_location( $requested_default, $showrooms );

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class'                 => 'cf-showroom-locator',
				'data-default-location'   => esc_attr( $active_location ),
				'data-active-location'  => esc_attr( $active_location ),
			],
			$block
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="cf-showroom-locator__filters" role="tablist" aria-label="<?php esc_attr_e( 'Showroom locations', 'chairforce' ); ?>">
				<?php echo self::render_filters( $showrooms, $active_location, $instance_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="cf-showroom-locator__body">
				<div class="cf-showroom-locator__cards">
					<?php echo self::render_cards( $showrooms, $active_location, $instance_id, $show_cta ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="cf-showroom-locator__map">
					<?php echo self::render_map( $showrooms, $active_location, $instance_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Location configuration keyed by machine slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_locations_config(): array {
		return [
			'sydney'    => [
				'key'          => 'sydney',
				'filter_label' => 'NSW',
				'label'        => 'Sydney',
				'marker_x'     => 75.17,
				'marker_y'     => 69.16,
				'order'        => 1,
			],
			'brisbane'  => [
				'key'          => 'brisbane',
				'filter_label' => 'QLD',
				'label'        => 'Brisbane',
				'marker_x'     => 81.25,
				'marker_y'     => 43.80,
				'order'        => 2,
			],
			'melbourne' => [
				'key'          => 'melbourne',
				'filter_label' => 'VIC',
				'label'        => 'Melbourne',
				'marker_x'     => 64.21,
				'marker_y'     => 80.64,
				'order'        => 3,
			],
			'adelaide'  => [
				'key'          => 'adelaide',
				'filter_label' => 'SA',
				'label'        => 'Adelaide',
				'marker_x'     => 52.08,
				'marker_y'     => 69.63,
				'order'        => 4,
			],
			'perth'     => [
				'key'          => 'perth',
				'filter_label' => 'WA',
				'label'        => 'Perth',
				'marker_x'     => 8.15,
				'marker_y'     => 64.85,
				'order'        => 5,
			],
			'hobart'    => [
				'key'          => 'hobart',
				'filter_label' => 'TAS',
				'label'        => 'Hobart',
				'marker_x'     => 66.10,
				'marker_y'     => 94.04,
				'order'        => 6,
			],
			'auckland'  => [
				'key'          => 'auckland',
				'filter_label' => 'NZ',
				'label'        => 'Auckland',
				'marker_x'     => 94.89,
				'marker_y'     => 69.63,
				'order'        => 7,
			],
		];
	}

	/**
	 * Query and map published showroom posts.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_showrooms(): array {
		return self::get_mapped_showrooms();
	}

	/**
	 * Query and map published showroom posts.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_mapped_showrooms(): array {
		$config  = self::get_locations_config();
		$posts   = get_posts(
			[
				'post_type'              => 'showrooms',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);
		$mapped  = [];
		$seen    = [];

		foreach ( $posts as $post ) {
			$key = self::resolve_location_key( (int) $post->ID, (string) $post->post_name );

			if ( '' === $key || ! isset( $config[ $key ] ) || isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;

			$warehouse = (string) get_post_meta( $post->ID, 'warehouse', true );
			$time      = (string) get_post_meta( $post->ID, 'time', true );
			$phone     = (string) get_post_meta( $post->ID, 'phone', true );
			$email     = sanitize_email( (string) get_post_meta( $post->ID, 'email', true ) );
			$address   = (string) get_post_meta( $post->ID, 'address', true );
			$heading   = '' !== trim( $warehouse ) ? $warehouse : get_the_title( $post );

			$mapped[ $key ] = [
				'key'          => $key,
				'post_id'      => (int) $post->ID,
				'filter_label' => self::resolve_filter_label( (int) $post->ID, $config[ $key ]['label'] ),
				'label'        => $config[ $key ]['label'],
				'marker_x'     => $config[ $key ]['marker_x'],
				'marker_y'     => $config[ $key ]['marker_y'],
				'order'        => $config[ $key ]['order'],
				'warehouse'    => $heading,
				'image_id'     => self::resolve_showroom_image_id( (int) $post->ID ),
				'time'         => $time,
				'phone'        => $phone,
				'phone_href'   => self::normalize_phone_link( $phone ),
				'email'        => $email,
				'address'      => $address,
				'permalink'    => get_permalink( $post ),
			];
		}

		uasort(
			$mapped,
			static function ( array $a, array $b ): int {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		return $mapped;
	}

	/**
	 * Resolve a showroom machine key.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_slug Post slug fallback.
	 * @return string
	 */
	private static function resolve_location_key( int $post_id, string $post_slug ): string {
		$id_meta = sanitize_key( (string) get_post_meta( $post_id, 'id', true ) );

		if ( '' !== $id_meta ) {
			return $id_meta;
		}

		return sanitize_key( $post_slug );
	}

	/**
	 * Resolve the default active location.
	 *
	 * @param string                              $requested Default requested by block attributes.
	 * @param array<string, array<string, mixed>> $showrooms Mapped showrooms.
	 * @return string
	 */
	private static function resolve_default_location( string $requested, array $showrooms ): string {
		if ( isset( $showrooms[ $requested ] ) ) {
			return $requested;
		}

		if ( isset( $showrooms['brisbane'] ) ) {
			return 'brisbane';
		}

		$keys = array_keys( $showrooms );

		return (string) ( $keys[0] ?? 'brisbane' );
	}

	/**
	 * Render filter tabs.
	 *
	 * @param array<string, array<string, mixed>> $showrooms       Mapped showrooms.
	 * @param string                              $active_location Active location key.
	 * @param string                              $instance_id     Unique instance prefix.
	 * @return string
	 */
	private static function render_filters( array $showrooms, string $active_location, string $instance_id ): string {
		$markup = '';

		foreach ( $showrooms as $key => $showroom ) {
			$is_active   = $key === $active_location;
			$tab_id      = $instance_id . '-tab-' . $key;
			$panel_id    = $instance_id . '-panel-' . $key;
			$class_names = 'wp-block-button__link wp-element-button cf-showroom-locator__filter' . ( $is_active ? ' is-active' : '' );

			$markup .= '<div class="wp-block-button is-style-ghost cf-showroom-locator__filter-button">';
			$markup .= sprintf(
				'<a id="%1$s" class="%2$s" role="tab" href="%3$s" data-showroom-location="%4$s" aria-controls="%5$s" aria-selected="%6$s" tabindex="%7$d">%8$s</a>',
				esc_attr( $tab_id ),
				esc_attr( $class_names ),
				esc_url( (string) $showroom['permalink'] ),
				esc_attr( $key ),
				esc_attr( $panel_id ),
				$is_active ? 'true' : 'false',
				$is_active ? 0 : -1,
				esc_html( self::format_title_case( (string) $showroom['filter_label'] ) )
			);
			$markup .= '</div>';
		}

		return $markup;
	}

	/**
	 * Render showroom cards.
	 *
	 * @param array<string, array<string, mixed>> $showrooms       Mapped showrooms.
	 * @param string                              $active_location Active location key.
	 * @param string                              $instance_id     Unique instance prefix.
	 * @param bool                                $show_cta        Whether to render CTA links.
	 * @return string
	 */
	private static function render_cards( array $showrooms, string $active_location, string $instance_id, bool $show_cta ): string {
		$markup = '';

		foreach ( $showrooms as $key => $showroom ) {
			$is_active   = $key === $active_location;
			$tab_id      = $instance_id . '-tab-' . $key;
			$panel_id    = $instance_id . '-panel-' . $key;
			$class_names = 'cf-showroom-locator__card' . ( $is_active ? ' is-active' : '' );
			$hidden_attr = $is_active ? '' : ' hidden';

			$markup .= sprintf(
				'<article id="%1$s" class="%2$s" role="tabpanel" aria-labelledby="%3$s" data-showroom-location="%4$s" tabindex="0"%5$s>',
				esc_attr( $panel_id ),
				esc_attr( $class_names ),
				esc_attr( $tab_id ),
				esc_attr( $key ),
				$hidden_attr
			);

			$markup .= self::render_card_image( $showroom, 'cf-showroom-locator', true );
			$markup .= '<div class="cf-showroom-locator__card-content">';
			$markup .= self::render_card_body( $showroom, $show_cta, 'cf-showroom-locator' );

			$markup .= '</div>';
			$markup .= '</article>';
		}

		return $markup;
	}

	/**
	 * Render shared showroom card body markup.
	 *
	 * @param array<string, mixed> $showroom Mapped showroom data.
	 * @param bool                 $show_cta Whether to render CTA links.
	 * @param string               $prefix   BEM block prefix.
	 * @return string
	 */
	public static function render_card_body( array $showroom, bool $show_cta, string $prefix = 'cf-showroom-locator', array $options = [] ): string {
		$cta_variant = isset( $options['cta_variant'] ) ? sanitize_key( (string) $options['cta_variant'] ) : 'primary';
		$cta_label   = isset( $options['cta_label'] ) ? (string) $options['cta_label'] : '';

		$markup  = '<h3 class="' . esc_attr( $prefix ) . '__card-title">' . esc_html( (string) $showroom['warehouse'] ) . '</h3>';

		if ( '' !== trim( (string) $showroom['address'] ) ) {
			$markup .= '<div class="' . esc_attr( $prefix ) . '__detail">';
			$markup .= '<span class="cf-icon-preview cf-icon-map-pin" aria-hidden="true"></span>';
			$markup .= '<span class="' . esc_attr( $prefix ) . '__detail-text">' . wp_kses( (string) $showroom['address'], self::ADDRESS_ALLOWED_HTML ) . '</span>';
			$markup .= '</div>';
		}

		if ( '' !== trim( (string) $showroom['time'] ) ) {
			$markup .= '<div class="' . esc_attr( $prefix ) . '__detail">';
			$markup .= '<span class="cf-icon-preview cf-icon-clock" aria-hidden="true"></span>';
			$markup .= '<span class="' . esc_attr( $prefix ) . '__detail-text">' . wp_kses( (string) $showroom['time'], self::TIME_ALLOWED_HTML ) . '</span>';
			$markup .= '</div>';
		}

		$markup .= self::render_card_contact( $showroom, $prefix );

		if ( $show_cta ) {
			$button_style = 'ghost' === $cta_variant ? 'ghost' : 'primary';
			$label        = '' !== trim( $cta_label )
				? $cta_label
				: __( 'Get Directions', 'chairforce' );

			$markup .= '<div class="' . esc_attr( $prefix ) . '__cta wp-block-button is-style-' . esc_attr( $button_style ) . ' cf-has-icon cf-icon-arrow-right cf-icon-right">';
			$markup .= sprintf(
				'<a class="wp-block-button__link wp-element-button" href="%1$s">%2$s</a>',
				esc_url( (string) $showroom['permalink'] ),
				esc_html( $label )
			);
			$markup .= '</div>';
		}

		return $markup;
	}

	/**
	 * Render a showroom card article for the full locator layout.
	 *
	 * @param array<string, mixed> $showroom Mapped showroom data.
	 * @param bool                 $show_cta Whether to render CTA links.
	 * @param string               $prefix   BEM block prefix.
	 * @param string               $context  Card context slug.
	 * @param bool                 $is_active Whether the card is active.
	 * @return string
	 */
	public static function render_full_card( array $showroom, bool $show_cta, string $prefix, string $context, bool $is_active = false ): string {
		$class_names = $prefix . '__card' . ( $is_active ? ' is-active' : '' );
		$cta_label   = sprintf(
			/* translators: %s: showroom city label, e.g. Sydney */
			__( 'Learn Our %s Showroom', 'chairforce' ),
			(string) $showroom['label']
		);

		$markup  = sprintf(
			'<article class="%1$s" data-showroom-location="%2$s" data-card-context="%3$s">',
			esc_attr( $class_names ),
			esc_attr( (string) $showroom['key'] ),
			esc_attr( $context )
		);
		$markup .= self::render_card_image( $showroom, $prefix, true );
		$markup .= '<div class="' . esc_attr( $prefix ) . '__card-content">';
		$markup .= self::render_card_body(
			$showroom,
			$show_cta,
			$prefix,
			[
				'cta_variant' => 'ghost',
				'cta_label'   => $cta_label,
			]
		);
		$markup .= '</div>';
		$markup .= '</article>';

		return $markup;
	}

	/**
	 * Render map image and marker links.
	 *
	 * @param array<string, array<string, mixed>> $showrooms       Mapped showrooms.
	 * @param string                              $active_location Active location key.
	 * @param string                              $prefix          BEM block prefix.
	 * @return string
	 */
	public static function render_map_markup( array $showrooms, string $active_location, string $prefix = 'cf-showroom-locator' ): string {
		$map_url = get_theme_file_uri( 'assets/images/showroom-locator-map.svg' );

		$markup  = '<div class="' . esc_attr( $prefix ) . '__map-canvas">';
		$markup .= sprintf(
			'<img class="%1$s__map-image" src="%2$s" alt="" aria-hidden="true" width="264" height="209" decoding="async" />',
			esc_attr( $prefix ),
			esc_url( $map_url )
		);

		foreach ( $showrooms as $key => $showroom ) {
			$is_active = $key === $active_location;
			$label     = sprintf(
				/* translators: %s: showroom location label, e.g. Sydney */
				__( 'Show %s showroom', 'chairforce' ),
				(string) $showroom['label']
			);

			$style = sprintf(
				'--cf-marker-x:%1$s%%;--cf-marker-y:%2$s%%;',
				esc_attr( (string) $showroom['marker_x'] ),
				esc_attr( (string) $showroom['marker_y'] )
			);

			$markup .= sprintf(
				'<a class="%1$s__marker%2$s" href="%3$s" data-showroom-location="%4$s" aria-label="%5$s" style="%6$s"%7$s><span aria-hidden="true"></span></a>',
				esc_attr( $prefix ),
				$is_active ? ' is-active' : '',
				esc_url( (string) $showroom['permalink'] ),
				esc_attr( $key ),
				esc_attr( $label ),
				esc_attr( $style ),
				$is_active ? ' aria-current="true"' : ''
			);
		}

		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Format a label using title case.
	 *
	 * @param string $value Raw label value.
	 * @return string
	 */
	private static function format_title_case( string $value ): string {
		if ( function_exists( 'mb_convert_case' ) ) {
			return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
		}

		return ucwords( strtolower( $value ) );
	}

	/**
	 * Resolve the filter label from showroom taxonomy terms.
	 *
	 * @param int    $post_id       Showroom post ID.
	 * @param string $fallback_label Fallback label when taxonomy is missing.
	 * @return string
	 */
	private static function resolve_filter_label( int $post_id, string $fallback_label ): string {
		$terms = get_the_terms( $post_id, 'showroom-locations' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $fallback_label;
		}

		$term = $terms[0];

		return '' !== trim( (string) $term->name ) ? (string) $term->name : $fallback_label;
	}

	/**
	 * Render phone and email on a single contact row.
	 *
	 * @param array<string, mixed> $showroom Mapped showroom data.
	 * @return string
	 */
	private static function render_card_contact( array $showroom, string $prefix = 'cf-showroom-locator' ): string {
		$has_phone = '' !== trim( (string) $showroom['phone'] ) && '' !== (string) $showroom['phone_href'];
		$has_email = '' !== (string) $showroom['email'];

		if ( ! $has_phone && ! $has_email ) {
			return '';
		}

		$leading_icon = $has_phone ? 'cf-icon-phone' : 'cf-icon-mail';

		$markup  = '<div class="' . esc_attr( $prefix ) . '__detail ' . esc_attr( $prefix ) . '__detail--contact">';
		$markup .= sprintf(
			'<span class="cf-icon-preview %1$s" aria-hidden="true"></span>',
			esc_attr( $leading_icon )
		);
		$markup .= '<span class="' . esc_attr( $prefix ) . '__contact-line">';

		if ( $has_phone ) {
			$markup .= sprintf(
				'<a class="%1$s__detail-link" href="tel:%2$s">%3$s</a>',
				esc_attr( $prefix ),
				esc_attr( (string) $showroom['phone_href'] ),
				esc_html( (string) $showroom['phone'] )
			);
		}

		if ( $has_email ) {
			if ( $has_phone ) {
				$markup .= '<span class="' . esc_attr( $prefix ) . '__email-item">';
				$markup .= '<span class="cf-icon-preview cf-icon-mail" aria-hidden="true"></span>';
			}

			$markup .= sprintf(
				'<a class="%1$s__detail-link" href="mailto:%2$s">%3$s</a>',
				esc_attr( $prefix ),
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

	/**
	 * Resolve the attachment ID used for a showroom card image.
	 *
	 * @param int $post_id Showroom post ID.
	 * @return int
	 */
	private static function resolve_showroom_image_id( int $post_id ): int {
		$gallery_ids = self::parse_attachment_ids( get_post_meta( $post_id, 'showroom_gallery', true ) );

		if ( ! empty( $gallery_ids ) ) {
			return (int) $gallery_ids[0];
		}

		$map_image_id = absint( get_post_meta( $post_id, 'map', true ) );

		if ( $map_image_id > 0 ) {
			return $map_image_id;
		}

		return (int) get_post_thumbnail_id( $post_id );
	}

	/**
	 * Normalize attachment ID values from gallery meta.
	 *
	 * @param mixed $value Raw post meta value.
	 * @return int[]
	 */
	private static function parse_attachment_ids( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'absint', $value ) ) );
		}

		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );

			return $attachment_id > 0 ? [ $attachment_id ] : [];
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return [];
		}

		$unserialized = maybe_unserialize( $value );

		if ( is_array( $unserialized ) ) {
			return array_values( array_filter( array_map( 'absint', $unserialized ) ) );
		}

		$ids = array_map( 'absint', explode( ',', $value ) );

		return array_values( array_filter( $ids ) );
	}

	/**
	 * Render a showroom card image when available.
	 *
	 * @param array<string, mixed> $showroom Mapped showroom data.
	 * @return string
	 */
	private static function render_card_image( array $showroom, string $prefix = 'cf-showroom-locator', bool $use_placeholder = false ): string {
		$image_id = isset( $showroom['image_id'] ) ? absint( $showroom['image_id'] ) : 0;
		$image    = '';

		if ( $image_id > 0 ) {
			$image = wp_get_attachment_image(
				$image_id,
				'large',
				false,
				[
					'class'    => $prefix . '__card-image',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => (string) $showroom['warehouse'],
				]
			);
		}

		if ( ! $image && $use_placeholder ) {
			$placeholder_url = get_theme_file_uri( 'assets/images/menu-thumb-placeholder.png' );

			$image = sprintf(
				'<img class="%1$s" src="%2$s" alt="%3$s" loading="lazy" decoding="async" />',
				esc_attr( $prefix . '__card-image' ),
				esc_url( $placeholder_url ),
				esc_attr( (string) $showroom['warehouse'] )
			);
		}

		if ( ! $image ) {
			return '';
		}

		$media_class = $prefix . '__card-media';

		if ( $image_id <= 0 && $use_placeholder ) {
			$media_class .= ' ' . $prefix . '__card-media--placeholder';
		}

		return '<div class="' . esc_attr( $media_class ) . '">' . $image . '</div>';
	}

	/**
	 * Render map image and marker links.
	 *
	 * @param array<string, array<string, mixed>> $showrooms       Mapped showrooms.
	 * @param string                              $active_location Active location key.
	 * @param string                              $instance_id     Unique instance prefix.
	 * @return string
	 */
	private static function render_map( array $showrooms, string $active_location, string $instance_id ): string {
		unset( $instance_id );

		return self::render_map_markup( $showrooms, $active_location, 'cf-showroom-locator' );
	}

	/**
	 * Normalize a phone number for tel: links.
	 *
	 * @param string $phone Raw phone value.
	 * @return string
	 */
	private static function normalize_phone_link( string $phone ): string {
		$phone = trim( $phone );

		if ( '' === $phone ) {
			return '';
		}

		$has_plus = str_starts_with( $phone, '+' );
		$digits   = preg_replace( '/[^0-9]/', '', $phone );

		if ( ! is_string( $digits ) || '' === $digits ) {
			return '';
		}

		return ( $has_plus ? '+' : '' ) . $digits;
	}

	/**
	 * Whether the current request is a block editor server render.
	 *
	 * @return bool
	 */
	private static function is_editor_preview_request(): bool {
		return ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * Editor-only empty state notice.
	 *
	 * @return string
	 */
	private static function render_editor_empty_notice(): string {
		return '<div class="cf-showroom-locator cf-showroom-locator--empty"><p>' . esc_html__(
			'No valid mapped showroom posts were found. Each published showroom needs a supported id value (sydney, brisbane, melbourne, adelaide, perth, hobart, or auckland).',
			'chairforce'
		) . '</p></div>';
	}
}
