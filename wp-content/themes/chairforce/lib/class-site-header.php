<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Site_Header' ) ) {
	return;
}

/**
 * Site header render pipeline, options access, and header-specific hooks.
 *
 * @package Chairforce
 */
class Site_Header {

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Site_Header constructor.
	 */
	public function __construct() {

		$this->register_hooks();

	}

	/**
	 * @return self
	 */
	public static function get_instance(): self {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Output the site header partial.
	 */
	public static function render(): void {

		get_template_part( 'partials/site', 'header' );

	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {

		add_filter( 'body_class', [ $this, 'filter_body_class' ] );

	}

	/**
	 * Add header behaviour classes to the body element.
	 *
	 * @param string[] $classes Body classes.
	 *
	 * @return string[]
	 */
	public function filter_body_class( array $classes ): array {

		if ( $this->hide_on_scroll_enabled() ) {
			$classes[] = 'site-header--hide-on-scroll';
		}

		return $classes;

	}

	/**
	 * Read a header options field with graceful fallbacks when ACF is inactive.
	 *
	 * @param string $field_name ACF field name.
	 * @param mixed  $default    Default when empty or ACF unavailable.
	 *
	 * @return mixed
	 */
	public function get_option( string $field_name, $default = '' ) {

		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}

		$value = get_field( $field_name, 'option' );

		if ( null === $value || '' === $value || false === $value ) {
			return $default;
		}

		return $value;

	}

	/**
	 * Whether hide-on-scroll behaviour is enabled (default on).
	 */
	public function hide_on_scroll_enabled(): bool {

		if ( ! function_exists( 'get_field' ) ) {
			return true;
		}

		$value = get_field( 'header_hide_on_scroll', 'option' );

		if ( null === $value ) {
			return true;
		}

		return (bool) $value;

	}

	/**
	 * Desktop header logo attachment ID.
	 */
	public function get_logo_id(): int {

		return (int) $this->get_option( 'logo', 0 );

	}

	/**
	 * Mobile header logo attachment ID with fallbacks.
	 */
	public function get_mobile_logo_id(): int {

		$mobile_logo = (int) $this->get_option( 'logo_mobile', 0 );

		if ( $mobile_logo ) {
			return $mobile_logo;
		}

		return $this->get_logo_id();

	}

	/**
	 * Compact mobile logo shown after the announcement bar scrolls away.
	 */
	public function get_mobile_sticky_logo_id(): int {

		$sticky_logo = (int) $this->get_option( 'logo_mobile_sticky', 0 );

		if ( $sticky_logo ) {
			return $sticky_logo;
		}

		return $this->get_mobile_logo_id();

	}

	/**
	 * Whether the mobile header should swap to the sticky logo asset.
	 */
	public function has_distinct_mobile_sticky_logo(): bool {

		return $this->get_mobile_sticky_logo_id() !== $this->get_mobile_logo_id();

	}

	/**
	 * Output a header logo link.
	 *
	 * @param int    $attachment_id Logo attachment ID.
	 * @param string $modifier      BEM modifier for the logo link (desktop|mobile).
	 * @param string $image_class   Optional extra class on the image element.
	 */
	public function render_logo_link( int $attachment_id, string $modifier = 'desktop', string $image_class = '' ): void {

		$classes = 'site-header__logo site-header__logo--' . sanitize_html_class( $modifier );
		$home    = esc_url( home_url( '/' ) );
		$label   = esc_attr__( 'Home', 'chairforce' );

		if ( ! $attachment_id ) {
			printf(
				'<a class="%1$s site-header__logo--text" href="%2$s"><span class="site-header__site-title">%3$s</span></a>',
				esc_attr( $classes ),
				$home,
				esc_html( get_bloginfo( 'name', 'display' ) )
			);
			return;
		}

		$img_class = trim( 'site-header__logo-img ' . $image_class );
		$attrs     = [
			'class'   => $img_class,
			'alt'     => get_bloginfo( 'name', 'display' ),
			'loading' => false,
		];

		printf(
			'<a class="%1$s" href="%2$s" aria-label="%3$s">%4$s</a>',
			esc_attr( $classes ),
			$home,
			$label,
			wp_get_attachment_image( $attachment_id, 'medium', false, $attrs )
		);

	}

	/**
	 * Render the hardcoded mobile primary row (logo, phone, cart, menu).
	 */
	public function render_mobile_primary_row(): void {

		$phone_number = $this->get_option( 'phone_number', '1300 272 926' );
		$phone_href   = $this->get_phone_href( (string) $phone_number );
		$mobile_logo  = $this->get_mobile_logo_id();
		$sticky_logo  = $this->get_mobile_sticky_logo_id();
		?>
		<div class="site-header__mobile-logo">
			<?php if ( $mobile_logo ) : ?>
				<a class="site-header__logo site-header__logo--mobile" href="<?php echo esc_url( home_url( '/' ) ); ?>"
					aria-label="<?php esc_attr_e( 'Home', 'chairforce' ); ?>">
					<?php
					echo wp_get_attachment_image(
						$mobile_logo,
						'medium',
						false,
						[
							'class'   => 'site-header__logo-img site-header__logo-img--default',
							'alt'     => get_bloginfo( 'name', 'display' ),
							'loading' => false,
						]
					);

					if ( $this->has_distinct_mobile_sticky_logo() ) {
						echo wp_get_attachment_image(
							$sticky_logo,
							'medium',
							false,
							[
								'class'   => 'site-header__logo-img site-header__logo-img--sticky',
								'alt'     => '',
								'loading' => false,
							]
						);
					}
					?>
				</a>
			<?php else : ?>
				<?php $this->render_logo_link( 0, 'mobile' ); ?>
			<?php endif; ?>
		</div>

		<div class="site-header__mobile-actions">
			<?php if ( $phone_href ) : ?>
				<a class="site-header__mobile-phone" href="<?php echo esc_url( $phone_href ); ?>" aria-label="<?php echo esc_attr( $phone_number ); ?>">
					<span class="site-header__mobile-phone-icon" aria-hidden="true"></span>
				</a>
			<?php endif; ?>

			<?php $this->render_mini_cart(); ?>

			<button
				class="site-header__menu-toggle"
				type="button"
				aria-expanded="false"
				aria-controls="chairforce-mobile-drawer"
				aria-label="<?php esc_attr_e( 'Open menu', 'chairforce' ); ?>"
			>
				<span class="site-header__menu-toggle-icon" aria-hidden="true"></span>
			</button>
		</div>
		<?php

	}

	/**
	 * Sanitized tel: href from a display phone string.
	 *
	 * @param string $phone_number Display phone number.
	 */
	public function get_phone_href( string $phone_number ): string {

		$digits = preg_replace( '/[^\d+]/', '', $phone_number );

		return $digits ? 'tel:' . $digits : '';

	}

	/**
	 * WooCommerce Mini Cart block (desktop + mobile header).
	 */
	public function render_mini_cart(): void {

		if ( ! function_exists( 'do_blocks' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block output is escaped by core.
		echo do_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"always"} /-->' );

	}

	/**
	 * Shared wp_nav_menu() args for header menus.
	 *
	 * @param string $context desktop-nav|mobile-drawer.
	 *
	 * @return array<string, mixed>
	 */
	private function get_nav_menu_args( string $context = 'desktop-nav' ): array {

		return [
			'container'                 => false,
			'fallback_cb'               => false,
			'walker'                    => new Primary_Walker(),
			'chairforce_menu_context'   => $context,
		];

	}

	/**
	 * Render primary navigation in the desktop nav row.
	 */
	public function render_primary_nav(): void {

		if ( ! has_nav_menu( CHAIRFORCE_MENU_PRIMARY ) ) {
			return;
		}

		wp_nav_menu(
			array_merge(
				$this->get_nav_menu_args( 'desktop-nav' ),
				[
					'theme_location' => CHAIRFORCE_MENU_PRIMARY,
					'menu_class'     => 'site-header__nav-list',
				]
			)
		);

	}

	/**
	 * Render primary navigation inside the mobile drawer.
	 */
	public function render_mobile_primary_nav(): void {

		if ( ! has_nav_menu( CHAIRFORCE_MENU_PRIMARY ) ) {
			return;
		}

		wp_nav_menu(
			array_merge(
				$this->get_nav_menu_args( 'mobile-drawer' ),
				[
					'theme_location' => CHAIRFORCE_MENU_PRIMARY,
					'menu_class'     => 'site-header__mobile-drawer-list',
				]
			)
		);

	}

	/**
	 * Render utility navigation in the desktop utility cluster.
	 */
	public function render_utility_nav(): void {

		if ( ! has_nav_menu( CHAIRFORCE_MENU_UTILITY ) ) {
			return;
		}

		wp_nav_menu(
			array_merge(
				$this->get_nav_menu_args( 'desktop-nav' ),
				[
					'theme_location' => CHAIRFORCE_MENU_UTILITY,
					'menu_class'     => 'site-header__utilities-list',
					'depth'          => 1,
				]
			)
		);

	}

	/**
	 * Render utility navigation in the mobile drawer.
	 */
	public function render_mobile_utility_nav(): void {

		if ( ! has_nav_menu( CHAIRFORCE_MENU_UTILITY ) ) {
			return;
		}

		wp_nav_menu(
			array_merge(
				$this->get_nav_menu_args( 'mobile-drawer' ),
				[
					'theme_location' => CHAIRFORCE_MENU_UTILITY,
					'menu_class'     => 'site-header__mobile-drawer-list site-header__mobile-drawer-list--utility',
					'depth'          => 1,
				]
			)
		);

	}

}
