<?php
/**
 * Showroom Locator (full) block rendering.
 *
 * @package Chairforce
 */

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side showroom locator full layout markup.
 */
class Showroom_Locator_Full {

	/**
	 * BEM block prefix.
	 */
	private const BLOCK_PREFIX = 'cf-showroom-locator-full';

	/**
	 * Render the showroom locator full block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param \WP_Block|null       $block      Block instance for wrapper attributes.
	 * @return string
	 */
	public static function render( array $attributes, $block = null ): string {
		$requested_default = isset( $attributes['defaultLocation'] )
			? sanitize_key( (string) $attributes['defaultLocation'] )
			: 'brisbane';

		$showrooms = Showroom_Locator::get_showrooms();
		$is_editor = self::is_editor_request();

		if ( empty( $showrooms ) ) {
			if ( $is_editor ) {
				return self::render_editor_empty_notice();
			}

			return '';
		}

		$states       = Showroom_Locator::get_states_with_showrooms( $showrooms );
		$active_slug  = Showroom_Locator::resolve_default_showroom( $requested_default, $showrooms );
		$active_state = (string) ( $showrooms[ $active_slug ]['state_slug'] ?? '' );

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class'                => self::BLOCK_PREFIX,
				'data-default-showroom'  => esc_attr( $active_slug ),
				'data-active-showroom'   => esc_attr( $active_slug ),
				'data-active-state'      => esc_attr( $active_state ),
			],
			$block
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__hero">
				<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__map">
					<?php echo Showroom_Locator::render_map_markup( $states, $active_state, self::BLOCK_PREFIX ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__featured" aria-live="polite">
					<?php
					echo self::render_card_slot(
						$showrooms[ $active_slug ],
						'featured',
						true
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
			<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__grid" role="list">
				<?php
				foreach ( $showrooms as $slug => $showroom ) {
					if ( $slug === $active_slug ) {
						continue;
					}

					echo self::render_card_slot(
						$showroom,
						'grid',
						false
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render one showroom card slot for the full layout.
	 *
	 * @param array<string, mixed> $showroom  Mapped showroom data.
	 * @param string               $context   featured|grid.
	 * @param bool                 $is_active Whether the slot is active.
	 * @return string
	 */
	private static function render_card_slot( array $showroom, string $context, bool $is_active = false ): string {
		$class_names = self::BLOCK_PREFIX . '__card-slot' . ( $is_active ? ' is-active' : '' );

		$markup = sprintf(
			'<div class="%1$s" data-showroom-slug="%2$s" data-showroom-state="%3$s" data-card-context="%4$s" role="listitem">',
			esc_attr( $class_names ),
			esc_attr( (string) $showroom['key'] ),
			esc_attr( (string) $showroom['state_slug'] ),
			esc_attr( $context )
		);

		$markup .= chairforce_render_showroom_card_for_post(
			(int) $showroom['post_id'],
			[
				'ctaType' => 'learn-more',
			]
		);
		$markup .= '</div>';

		return $markup;
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
		return '<div class="' . esc_attr( self::BLOCK_PREFIX ) . ' ' . esc_attr( self::BLOCK_PREFIX ) . '--empty"><p>' . esc_html__(
			'No published showroom posts with a showroom location were found. Assign each showroom to a state in the showroom-locations taxonomy.',
			'chairforce'
		) . '</p></div>';
	}
}
