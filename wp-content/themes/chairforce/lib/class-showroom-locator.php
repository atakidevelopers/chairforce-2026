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
	 * Taxonomy slug for showroom state/region terms.
	 */
	public const TAXONOMY = 'showroom-locations';

	/**
	 * Render the showroom locator block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param \WP_Block|null       $block      Block instance for wrapper attributes.
	 * @return string
	 */
	public static function render( array $attributes, $block = null ): string {
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

		$states         = self::get_states_with_showrooms( $showrooms );
		$active_slug    = self::resolve_default_showroom( $requested_default, $showrooms );
		$active_state   = (string) ( $showrooms[ $active_slug ]['state_slug'] ?? '' );
		$cta_type       = 'directions';

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class'                 => 'cf-showroom-locator',
				'data-default-showroom'   => esc_attr( $active_slug ),
				'data-active-showroom'    => esc_attr( $active_slug ),
				'data-active-state'       => esc_attr( $active_state ),
				'data-view-mode'          => 'single',
			],
			$block
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="cf-showroom-locator__filters" role="tablist" aria-label="<?php esc_attr_e( 'Showrooms', 'chairforce' ); ?>">
				<?php echo self::render_filters( $showrooms, $active_slug, $instance_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="cf-showroom-locator__body">
				<div class="cf-showroom-locator__cards">
					<?php echo self::render_card_panels( $showrooms, $active_slug, $instance_id, $cta_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="cf-showroom-locator__map">
					<?php echo self::render_map_markup( $states, $active_state, 'cf-showroom-locator' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Internal map marker config keyed by state taxonomy term slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_state_markers_config(): array {
		return [
			'new-south-wales' => [
				'marker_x' => 75.17,
				'marker_y' => 69.16,
				'order'    => 1,
			],
			'queensland'      => [
				'marker_x' => 81.25,
				'marker_y' => 43.80,
				'order'    => 2,
			],
			'northern-territory' => [
				'marker_x' => 38.80,
				'marker_y' => 3.50,
				'order'    => 8,
			],
			'victoria'        => [
				'marker_x' => 64.21,
				'marker_y' => 80.64,
				'order'    => 3,
			],
			'south-australia' => [
				'marker_x' => 52.08,
				'marker_y' => 69.63,
				'order'    => 4,
			],
			'western-australia' => [
				'marker_x' => 8.15,
				'marker_y' => 64.85,
				'order'    => 5,
			],
			'tasmania'        => [
				'marker_x' => 66.10,
				'marker_y' => 94.04,
				'order'    => 6,
			],
			'new-zealand'     => [
				'marker_x' => 94.89,
				'marker_y' => 69.63,
				'order'    => 7,
			],
		];
	}

	/**
	 * Legacy city config alias — prefer get_state_markers_config().
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_locations_config(): array {
		return self::get_state_markers_config();
	}

	/**
	 * Query and map published showroom posts keyed by post slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_showrooms(): array {
		$posts  = get_posts(
			[
				'post_type'              => 'showrooms',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			]
		);
		$mapped = [];

		foreach ( $posts as $post ) {
			$mapped_showroom = self::map_showroom_post( $post );

			if ( null === $mapped_showroom ) {
				continue;
			}

			$slug = (string) $mapped_showroom['key'];

			if ( isset( $mapped[ $slug ] ) ) {
				continue;
			}

			$mapped[ $slug ] = $mapped_showroom;
		}

		return $mapped;
	}

	/**
	 * Group showrooms by assigned state taxonomy term.
	 *
	 * @param array<string, array<string, mixed>> $showrooms Mapped showrooms keyed by slug.
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_states_with_showrooms( array $showrooms ): array {
		$config = self::get_state_markers_config();
		$states = [];

		foreach ( $showrooms as $showroom ) {
			$state_slug = sanitize_key( (string) ( $showroom['state_slug'] ?? '' ) );

			if ( '' === $state_slug || ! isset( $config[ $state_slug ] ) ) {
				continue;
			}

			if ( ! isset( $states[ $state_slug ] ) ) {
				$states[ $state_slug ] = array_merge(
					$config[ $state_slug ],
					[
						'slug'      => $state_slug,
						'label'     => (string) ( $showroom['state_label'] ?? $state_slug ),
						'showrooms' => [],
					]
				);
			}

			$states[ $state_slug ]['showrooms'][] = $showroom;
		}

		uasort(
			$states,
			static function ( array $a, array $b ): int {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		return $states;
	}

	/**
	 * Map one showroom post to locator/card data.
	 *
	 * @param \WP_Post $post Showroom post.
	 * @return array<string, mixed>|null
	 */
	public static function map_showroom_post( \WP_Post $post ): ?array {
		$slug  = sanitize_key( (string) $post->post_name );
		$terms = get_the_terms( (int) $post->ID, self::TAXONOMY );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		$term       = $terms[0];
		$state_slug = sanitize_key( (string) $term->slug );

		if ( '' === $slug || '' === $state_slug ) {
			return null;
		}

		$warehouse = (string) get_post_meta( $post->ID, 'warehouse', true );
		$time      = (string) get_post_meta( $post->ID, 'time', true );
		$phone     = (string) get_post_meta( $post->ID, 'phone', true );
		$email     = sanitize_email( (string) get_post_meta( $post->ID, 'email', true ) );
		$address   = (string) get_post_meta( $post->ID, 'address', true );
		$heading   = '' !== trim( $warehouse ) ? $warehouse : get_the_title( $post );

		return [
			'key'         => $slug,
			'post_id'     => (int) $post->ID,
			'tab_label'   => get_the_title( $post ),
			'label'       => self::format_slug_label( $slug ),
			'state_slug'  => $state_slug,
			'state_label' => (string) $term->name,
			'warehouse'   => $heading,
			'image_id'    => self::resolve_showroom_image_id( (int) $post->ID ),
			'time'        => $time,
			'phone'       => $phone,
			'phone_href'  => self::normalize_phone_link( $phone ),
			'email'       => $email,
			'address'     => $address,
			'permalink'   => get_permalink( $post ),
		];
	}

	/**
	 * Render filter tabs — one tab per showroom post.
	 *
	 * @param array<string, array<string, mixed>> $showrooms    Mapped showrooms.
	 * @param string                              $active_slug  Active showroom slug.
	 * @param string                              $instance_id  Unique instance prefix.
	 * @return string
	 */
	private static function render_filters( array $showrooms, string $active_slug, string $instance_id ): string {
		$markup = '';

		foreach ( $showrooms as $slug => $showroom ) {
			$is_active   = $slug === $active_slug;
			$tab_id      = $instance_id . '-tab-' . $slug;
			$panel_id    = $instance_id . '-panel-' . $slug;
			$class_names = 'wp-block-button__link wp-element-button cf-showroom-locator__filter' . ( $is_active ? ' is-active' : '' );

			$markup .= '<div class="wp-block-button is-style-ghost cf-showroom-locator__filter-button">';
			$markup .= sprintf(
				'<button type="button" id="%1$s" class="%2$s" role="tab" data-showroom-slug="%3$s" data-showroom-state="%4$s" aria-controls="%5$s" aria-selected="%6$s" tabindex="%7$d">%8$s</button>',
				esc_attr( $tab_id ),
				esc_attr( $class_names ),
				esc_attr( $slug ),
				esc_attr( (string) $showroom['state_slug'] ),
				esc_attr( $panel_id ),
				$is_active ? 'true' : 'false',
				$is_active ? 0 : -1,
				esc_html( (string) $showroom['tab_label'] )
			);
			$markup .= '</div>';
		}

		return $markup;
	}

	/**
	 * Render card panels for tab (single) and marker (state swiper) modes.
	 *
	 * @param array<string, array<string, mixed>> $showrooms   Mapped showrooms.
	 * @param string                              $active_slug Active showroom slug.
	 * @param string                              $instance_id Unique instance prefix.
	 * @param string                              $cta_type    Showroom card CTA type.
	 * @return string
	 */
	private static function render_card_panels( array $showrooms, string $active_slug, string $instance_id, string $cta_type ): string {
		$markup = '';
		$states = self::get_states_with_showrooms( $showrooms );

		foreach ( $showrooms as $slug => $showroom ) {
			$is_active   = $slug === $active_slug;
			$tab_id      = $instance_id . '-tab-' . $slug;
			$panel_id    = $instance_id . '-panel-' . $slug;
			$class_names = 'cf-showroom-locator__panel cf-showroom-locator__panel--single' . ( $is_active ? ' is-active' : '' );
			$hidden_attr = $is_active ? '' : ' hidden';

			$markup .= sprintf(
				'<div id="%1$s" class="%2$s" role="tabpanel" aria-labelledby="%3$s" data-showroom-slug="%4$s" data-showroom-state="%5$s" tabindex="0"%6$s>',
				esc_attr( $panel_id ),
				esc_attr( $class_names ),
				esc_attr( $tab_id ),
				esc_attr( $slug ),
				esc_attr( (string) $showroom['state_slug'] ),
				$hidden_attr
			);
			$markup .= chairforce_render_showroom_card_for_post(
				(int) $showroom['post_id'],
				[
					'ctaType' => $cta_type,
				]
			);
			$markup .= '</div>';
		}

		foreach ( $states as $state_slug => $state ) {
			if ( count( $state['showrooms'] ) < 2 ) {
				continue;
			}

			$markup .= sprintf(
				'<div class="cf-showroom-locator__panel cf-showroom-locator__panel--state" data-showroom-state="%1$s" hidden>',
				esc_attr( $state_slug )
			);
			$markup .= '<div class="cf-showroom-locator__swiper swiper"><div class="swiper-wrapper">';

			foreach ( $state['showrooms'] as $showroom ) {
				$markup .= '<div class="swiper-slide">';
				$markup .= chairforce_render_showroom_card_for_post(
					(int) $showroom['post_id'],
					[
						'ctaType' => $cta_type,
					]
				);
				$markup .= '</div>';
			}

			$markup .= '</div></div>';
			$markup .= '<div class="cf-showroom-locator__swiper-controls">';
			$markup .= sprintf(
				'<button type="button" class="cf-showroom-locator__nav cf-showroom-locator__nav--prev" aria-label="%1$s"></button>',
				esc_attr__( 'Previous showroom', 'chairforce' )
			);
			$markup .= '<div class="swiper-pagination"></div>';
			$markup .= sprintf(
				'<button type="button" class="cf-showroom-locator__nav cf-showroom-locator__nav--next" aria-label="%1$s"></button>',
				esc_attr__( 'Next showroom', 'chairforce' )
			);
			$markup .= '</div></div>';
		}

		return $markup;
	}

	/**
	 * Render map image and state marker buttons.
	 *
	 * @param array<string, array<string, mixed>> $states            State groups with marker coords.
	 * @param string                              $active_state_slug Active state taxonomy slug.
	 * @param string                              $prefix            BEM block prefix.
	 * @return string
	 */
	public static function render_map_markup( array $states, string $active_state_slug, string $prefix = 'cf-showroom-locator' ): string {
		$map_url = get_theme_file_uri( 'assets/images/showroom-locator-map.svg' );

		$markup  = '<div class="' . esc_attr( $prefix ) . '__map-canvas">';
		$markup .= sprintf(
			'<img class="%1$s__map-image" src="%2$s" alt="" aria-hidden="true" width="264" height="209" decoding="async" />',
			esc_attr( $prefix ),
			esc_url( $map_url )
		);

		foreach ( $states as $state_slug => $state ) {
			$is_active = $state_slug === $active_state_slug;
			$label     = sprintf(
				/* translators: %s: state/region label, e.g. Queensland */
				__( 'Show showrooms in %s', 'chairforce' ),
				(string) ( $state['label'] ?? $state_slug )
			);

			$style = sprintf(
				'--cf-marker-x:%1$s%%;--cf-marker-y:%2$s%%;',
				esc_attr( (string) $state['marker_x'] ),
				esc_attr( (string) $state['marker_y'] )
			);

			$markup .= sprintf(
				'<button type="button" class="%1$s__marker%2$s" data-showroom-state="%3$s" aria-label="%4$s" style="%5$s"%6$s><span aria-hidden="true"></span></button>',
				esc_attr( $prefix ),
				$is_active ? ' is-active' : '',
				esc_attr( $state_slug ),
				esc_attr( $label ),
				esc_attr( $style ),
				$is_active ? ' aria-current="true"' : ''
			);
		}

		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Resolve the default active showroom slug.
	 *
	 * @param string                              $requested Default requested by block attributes.
	 * @param array<string, array<string, mixed>> $showrooms Mapped showrooms.
	 * @return string
	 */
	public static function resolve_default_showroom( string $requested, array $showrooms ): string {
		if ( isset( $showrooms[ $requested ] ) ) {
			return $requested;
		}

		if ( isset( $showrooms['brisbane'] ) ) {
			return 'brisbane';
		}

		$keys = array_keys( $showrooms );

		return (string) ( $keys[0] ?? '' );
	}

	/**
	 * Format a slug into a display label (e.g. new-south-wales → New South Wales).
	 *
	 * @param string $slug Post slug.
	 * @return string
	 */
	private static function format_slug_label( string $slug ): string {
		$label = str_replace( '-', ' ', $slug );

		return self::format_title_case( $label );
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
			'No published showroom posts with a showroom location were found. Assign each showroom to a state in the showroom-locations taxonomy.',
			'chairforce'
		) . '</p></div>';
	}
}
