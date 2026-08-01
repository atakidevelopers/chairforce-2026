<?php
/**
 * Site header markup — full PHP render pipeline.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header             = Chairforce\Site_Header::get_instance();
$announcement       = $header->get_option( 'announcement_text' );
$announcement_link  = $header->get_option( 'announcement_link' );
$logo_id            = $header->get_logo_id();
$phone_number       = $header->get_option( 'phone_number', '1300 272 926' );
$phone_hours        = $header->get_option( 'phone_hours', __( 'Mon–Fri 9–5', 'chairforce' ) );
$search_placeholder = $header->get_option(
	'search_placeholder',
	__( 'Search chairs, tables, stools…', 'chairforce' )
);
$phone_href         = $header->get_phone_href( $phone_number );
?>

	<?php if ( $announcement ) : ?>
		<div class="site-header__announcement">
			<div class="site-header__announcement-inner alignwide">
				<span class="site-header__announcement-icon cf-icon-preview cf-icon-<?php echo esc_attr( $header->get_announcement_icon_slug() ); ?>" aria-hidden="true"></span>
				<?php if ( is_array( $announcement_link ) && ! empty( $announcement_link['url'] ) ) : ?>
					<a class="site-header__announcement-text" href="<?php echo esc_url( $announcement_link['url'] ); ?>"
						<?php echo ! empty( $announcement_link['target'] ) ? ' target="' . esc_attr( $announcement_link['target'] ) . '"' : ''; ?>>
						<?php echo esc_html( $announcement ); ?>
					</a>
				<?php else : ?>
					<p class="site-header__announcement-text"><?php echo esc_html( $announcement ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="site-header__primary">
		<div class="site-header__primary-inner site-header__primary-inner--desktop alignwide">
			<?php $header->render_logo_link( $logo_id, 'desktop' ); ?>

			<form class="site-header__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="chairforce-header-search">
					<?php esc_html_e( 'Search products', 'chairforce' ); ?>
				</label>
				<input
					id="chairforce-header-search"
					class="site-header__search-input"
					type="search"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
					autocomplete="off"
				/>
				<input type="hidden" name="post_type" value="product" />
				<button class="site-header__search-submit" type="submit" aria-label="<?php esc_attr_e( 'Search', 'chairforce' ); ?>">
					<span class="site-header__search-submit-icon" aria-hidden="true"></span>
				</button>
			</form>

			<?php if ( $phone_number ) : ?>
				<div class="site-header__contact">
					<span class="site-header__contact-icon" aria-hidden="true"></span>
					<div class="site-header__contact-text">
						<?php if ( $phone_hours ) : ?>
							<span class="site-header__contact-hours"><?php echo esc_html( $phone_hours ); ?></span>
						<?php endif; ?>
						<?php if ( $phone_href ) : ?>
							<a class="site-header__contact-phone" href="<?php echo esc_url( $phone_href ); ?>">
								<?php echo esc_html( $phone_number ); ?>
							</a>
						<?php else : ?>
							<span class="site-header__contact-phone"><?php echo esc_html( $phone_number ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<nav class="site-header__utilities" aria-label="<?php esc_attr_e( 'Utility navigation', 'chairforce' ); ?>">
				<?php $header->render_utility_nav(); ?>
				<?php $header->render_request_quote_widget(); ?>
				<?php $header->render_mini_cart( 'desktop' ); ?>
			</nav>
		</div>

		<div class="site-header__primary-inner site-header__primary-inner--mobile alignwide">
			<?php $header->render_mobile_primary_row(); ?>
		</div>
		</div>

		<div class="site-header__primary-spacer" aria-hidden="true"></div>

		<div class="site-header__search-row">
		<div class="site-header__search-row-inner alignwide">
			<form class="site-header__search site-header__search--mobile-row" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="chairforce-header-search-mobile">
					<?php esc_html_e( 'Search products', 'chairforce' ); ?>
				</label>
				<input
					id="chairforce-header-search-mobile"
					class="site-header__search-input"
					type="search"
					name="s"
					placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
					autocomplete="off"
				/>
				<input type="hidden" name="post_type" value="product" />
				<button class="site-header__search-submit" type="submit" aria-label="<?php esc_attr_e( 'Search', 'chairforce' ); ?>">
					<span class="site-header__search-submit-icon" aria-hidden="true"></span>
				</button>
			</form>
		</div>
	</div>

	<div class="site-header__nav">
		<div class="site-header__nav-inner alignwide">
			<nav class="site-header__nav-primary" aria-label="<?php esc_attr_e( 'Primary navigation', 'chairforce' ); ?>">
				<?php $header->render_primary_nav(); ?>
			</nav>
		</div>
	</div>

<div
	id="chairforce-mobile-drawer"
	class="site-header__mobile-drawer"
	aria-hidden="true"
	inert
>
	<div class="site-header__mobile-drawer-overlay" tabindex="-1" aria-hidden="true"></div>
	<div class="site-header__mobile-drawer-sheet">
		<div class="site-header__mobile-drawer-bar site-header__mobile-drawer-bar--root">
			<span class="site-header__mobile-drawer-bar-title"><?php esc_html_e( 'Menu', 'chairforce' ); ?></span>
			<button class="site-header__mobile-drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'chairforce' ); ?>">
				<span class="site-header__mobile-drawer-close-icon" aria-hidden="true"></span>
			</button>
		</div>
		<div class="site-header__mobile-drawer-bar site-header__mobile-drawer-bar--drill" hidden>
			<button class="site-header__mobile-drawer-back" type="button">
				<span class="site-header__mobile-drawer-back-icon" aria-hidden="true"></span>
				<?php esc_html_e( 'Back', 'chairforce' ); ?>
			</button>
			<button class="site-header__mobile-drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'chairforce' ); ?>">
				<span class="site-header__mobile-drawer-close-icon" aria-hidden="true"></span>
			</button>
		</div>
		<p class="site-header__mobile-drawer-drill-title" hidden></p>
		<div class="site-header__mobile-drawer-body">
			<nav class="site-header__mobile-drawer-nav" aria-label="<?php esc_attr_e( 'Main menu', 'chairforce' ); ?>">
				<?php $header->render_mobile_primary_nav(); ?>
				<?php $header->render_mobile_utility_nav(); ?>
			</nav>
		</div>
	</div>
</div>
