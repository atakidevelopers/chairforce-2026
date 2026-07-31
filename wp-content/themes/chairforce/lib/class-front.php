<?php

namespace Chairforce;

use Error;

/**
 * The public-facing functionality of the theme.
 *
 * @link       https://nextandco.com.au/
 * @author     N&C Team <rao@nextandco.com.au>
 * @since      0.0.1
 * @package    Chairforce
 * @subpackage Chairforce/public
 */
class Front {

	/**
	 * @var string $public_style_handle the public style handle id
	 */
	private $public_style_handle;

	/**
	 * @var string $public_script_handle the public style handle id
	 */
	private $public_script_handle;

	public function __construct() {

		$this->public_style_handle  = CHAIRFORCE_IDENTIFIER . '-public-style';
		$this->public_script_handle = CHAIRFORCE_IDENTIFIER . '-public-script';

		$this->register_hooks();
	}

	/**
	 * Register required hooks
	 */
	public function register_hooks() {

		/**
		 * Enqueue Styles
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		/**
		 * Enqueue Scripts
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		/**
		 * Code To be inserted as per Theme Options
		 * TODO: if you choose to disable this, also disable its CSS: /src/sass/components/_index.scss
		 */
		add_action( 'wp_footer', [ $this, 'maybe_enable_grid_guides' ] );

	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_styles() {

		$style_css = 'public.css';

		wp_enqueue_style(
			$this->public_style_handle,
			chairforce_get_build_url( $style_css ),
			[],
			filemtime( chairforce_get_build_dir( $style_css ) )
		);

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {

		$script_asset_path = chairforce_get_build_dir( 'public.asset.php' );

		if ( ! file_exists( $script_asset_path ) ) {
			throw new Error(
				'You need to first run `npm start` or `npm run build` for the blocks offered by this them. Could Not find the index.asset.php file'
			);
		}

		/**
		 * Register Scripts
		 */
		$script_asset = require( $script_asset_path );
		wp_enqueue_script(
			$this->public_script_handle,
			chairforce_get_build_url( 'public.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		/**
		 * Localize Scripts
		 * Passes translations to JavaScript.
		 */
		if ( function_exists( 'wp_set_script_translations' ) ) {
			/**
			 * For details see
			 * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
			 */
			wp_set_script_translations( $this->public_script_handle, 'chairforce' );
		}

		wp_localize_script(
			$this->public_script_handle,
			'Chairforce_Public',
			$this->get_localize_script_data()
		);

	}

	/**
	 * Localize Public facing scripts
	 */
	public function get_localize_script_data() {

		$quick_view_display = 'drawer';
		$quick_view_content = 'dimensions';

		if ( function_exists( 'get_field' ) ) {
			$quick_view_display = get_field( 'quick_view_display', 'option' ) ?: 'drawer';
			$quick_view_content = get_field( 'quick_view_content', 'option' ) ?: 'dimensions';
		}

		$localize_data = [
			'site_url'           => get_site_url(),
			'nonce'              => wp_create_nonce( 'wp_rest' ),
			'rest_url'           => rest_url( 'chairforce/v1/product-search' ),
			'loadMoreRestUrl'    => rest_url( 'chairforce/v1/load-more' ),
			'loadMoreViewingStatus' => __( 'Viewing %1$s of %2$s', 'chairforce' ),
			'resultsCountSingle'    => __( 'Showing the single result', 'chairforce' ),
			'resultsCountAll'       => __( 'Showing all %1$s results', 'chairforce' ),
			'resultsCountRange'     => __( 'Showing %1$s–%2$s of %3$s results', 'chairforce' ),
			'quickViewRestUrl'   => rest_url( 'chairforce/v1/quick-view' ),
			'quickViewDisplay'   => in_array( $quick_view_display, [ 'modal', 'drawer' ], true ) ? $quick_view_display : 'drawer',
			'quickViewContent'   => in_array( $quick_view_content, [ 'dimensions', 'short_description' ], true ) ? $quick_view_content : 'dimensions',
			'wishlistEnabled'    => chairforce_is_wishlist_enabled(),
			'wishlistLoopEnabled' => chairforce_is_wishlist_loop_enabled(),
			'wishlistIsLoggedIn' => is_user_logged_in(),
			'wishlistToggleUrl'  => rest_url( 'chairforce/v1/wishlist/toggle' ),
			'wishlistStatusUrl'  => rest_url( 'chairforce/v1/wishlist/status' ),
			'wishlistLoginUrl'   => chairforce_get_wishlist_login_url(),
			'wishlistAddLabel'   => __( 'Add to wishlist', 'chairforce' ),
			'wishlistRemoveLabel' => __( 'Remove from wishlist', 'chairforce' ),
		];


		return apply_filters( CHAIRFORCE_PREFIX . 'public_script_localize_data', $localize_data );

	}


	/**
	 * Maybe Enable grid guides as per theme options
	 * @hooked wp_footer
	 */
	public function maybe_enable_grid_guides() {

		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		$enable_grid_guides = true;

		if ( $enable_grid_guides ) {
			get_template_part( 'partials/grid', 'overlay' );
		}

	}

}
