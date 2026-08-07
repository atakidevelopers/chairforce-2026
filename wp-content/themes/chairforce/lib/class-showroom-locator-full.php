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
		$show_cta          = ! array_key_exists( 'showCta', $attributes ) || (bool) $attributes['showCta'];
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

		$active_location = self::resolve_default_location( $requested_default, $showrooms );

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class'                  => self::BLOCK_PREFIX,
				'data-default-location'  => esc_attr( $active_location ),
				'data-active-location'   => esc_attr( $active_location ),
			],
			$block
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__hero">
				<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__map">
					<?php echo Showroom_Locator::render_map_markup( $showrooms, $active_location, self::BLOCK_PREFIX ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__featured" aria-live="polite">
					<?php
					echo Showroom_Locator::render_full_card(
						$showrooms[ $active_location ],
						$show_cta,
						self::BLOCK_PREFIX,
						'featured',
						true
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
			<div class="<?php echo esc_attr( self::BLOCK_PREFIX ); ?>__grid" role="list">
				<?php
				foreach ( $showrooms as $key => $showroom ) {
					if ( $key === $active_location ) {
						continue;
					}

					echo Showroom_Locator::render_full_card(
						$showroom,
						$show_cta,
						self::BLOCK_PREFIX,
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
			'No valid mapped showroom posts were found. Each published showroom needs a supported id value (sydney, brisbane, melbourne, adelaide, perth, hobart, or auckland).',
			'chairforce'
		) . '</p></div>';
	}
}
