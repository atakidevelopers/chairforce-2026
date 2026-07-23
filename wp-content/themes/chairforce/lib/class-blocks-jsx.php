<?php

namespace Chairforce;
// exit if file is called directly
use Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\Blocks_Jsx' ) ) {
	return;
}


/**
 * This class will set up the JSX Blocks and enqueue required assets
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Blocks_Jsx {

	/**
	 * @var string $editor_script_handle the editor script handle id
	 */
	private $editor_script_handle;

	/**
	 * @var string $editor_style_handle the editor style handle id
	 */
	private $editor_style_handle;

	/**
	 * @var array a list of blocks directories
	 */
	private $blocks_directories = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->editor_script_handle = CHAIRFORCE_IDENTIFIER . '-block-editor-script';
		$this->editor_style_handle  = CHAIRFORCE_IDENTIFIER . '-block-editor-style';

		$this->register_hooks();

	}

	/**
	 * Register required hooks
	 */
	public function register_hooks() {

		/**
		 * Only for Editor (admin)
		 */
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

		/**
		 * Register JSX Block Types
		 */
		add_action( 'init', [ $this, 'register_block_type' ] );

	}


	/**
	 * Enqueue CSS and JS assets for Editor
	 * @hooked  enqueue_block_editor_assets
	 */
	public function enqueue_block_editor_assets() {

		$script_asset_path = chairforce_get_build_dir( 'index.asset.php' );

		if ( ! file_exists( $script_asset_path ) ) {
			throw new Error(
				"$script_asset_path file missing in build directory. Please execute `npm run build` to create theme build directory"
			);
		}

		/**
		 * Register Scripts
		 */
		$script_asset = require( $script_asset_path );
		wp_enqueue_script(
			$this->editor_script_handle,
			chairforce_get_build_url( 'index.js' ),
			$script_asset['dependencies'],
			$script_asset['version']
		);

		/**
		 * Localize Scripts
		 * Passes translations to JavaScript.
		 */
		if ( function_exists( 'wp_set_script_translations' ) ) {
			/**
			 * May be extended to
			 * wp_set_script_translations( 'my-handle', 'my-domain', plugin_dir_path( MY_PLUGIN . 'languages' )
			 * For details see
			 * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
			 */
			wp_set_script_translations( $this->editor_script_handle, 'chairforce' );
		}

		wp_localize_script( $this->editor_script_handle, 'Chairforce', $this->get_localize_script_data() );

		/**
		 * Register CSS Style
		 */

		$editor_css = 'index.css';
		wp_enqueue_style(
			$this->editor_style_handle,
			chairforce_get_build_url( $editor_css ),
			[],
			filemtime( chairforce_get_build_dir( $editor_css ) )
		);

	}

	/**
	 *
	 */
	public function get_localize_script_data() {

		$localize_data = [
			'siteUrl' => get_site_url(),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		];


		return apply_filters( CHAIRFORCE_PREFIX . 'editor_script_localize_data', $localize_data );

	}

	/**
	 * Register Block Type: JSX Method
	 * @hooked init
	 */
	public function register_block_type() {

		/**
		 * If we do not have the function available, bail out early
		 * possible if the theme is installed on a site without very old WordPress version
		 */
		if ( ! function_exists( 'register_block_type' ) ) {
			return null;
		}

		$blocks_directories = $this->get_blocks_directories();


		if ( empty( $blocks_directories ) ) {
			return null;
		}


		foreach ( $blocks_directories as $block_directory ) :

			register_block_type( $block_directory );

		endforeach;


	}

	/**
	 * @return array block directories paths
	 */
	public function get_blocks_directories(): array {

		if ( ! $this->blocks_directories ) {
			$blocks_dir = get_stylesheet_directory() . '/build-jsx-blocks/';
			// Loop over each block folder in the blocks directory
			$dirs = glob( $blocks_dir . '*', GLOB_ONLYDIR );

			if ( ! empty( $dirs ) ) {
				$this->blocks_directories = $dirs;
			}
		}

		return apply_filters( CHAIRFORCE_PREFIX . 'jsx_blocks_directories', $this->blocks_directories );

	}

}
