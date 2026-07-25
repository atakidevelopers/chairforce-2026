<?php

namespace Chairforce;

/**
 * The core theme class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this theme as well as the current
 * version of the theme.
 *
 * @since      0.0.1
 * @package    Chairforce
 * @subpackage Chairforce/lib
 * @author     N&C <rao@nextandco.com.au>
 */
class Init {

	/**
	 * The instance of this class
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      object $instance The instance of the current class
	 */
	protected static $instance;
	/**
	 * @var Api
	 * @noinspection PhpPropertyOnlyWrittenInspection
	 */
	private $api;
	/**
	 * @var Blocks_Jsx
	 * @noinspection PhpPropertyOnlyWrittenInspection
	 */
	private $blocks;


	/**
	 * Define the core functionality of the theme.
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->after_setup_theme();
		$this->define_acf_hooks();
		$this->define_api_hooks();
		$this->define_public_hooks();
		$this->define_blocks_jsx_hooks();
		$this->define_editor_curation_hooks();
		$this->define_lucide_icons_hooks();

		do_action( 'chairforce_init_construct' );

	}

	/**
	 * Register hooks related to the after_setup_theme
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function after_setup_theme() {

		new After_Setup_Theme();
		new Theme_Style_Switcher();

	}


	/**
	 * Register hooks related to the ACF Support
	 * of the theme.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_acf_hooks() {

		new Acf();

	}

	/**
	 * Register hooks related to the API functionality
	 * of the theme.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_api_hooks() {

		$this->api = new Api();

	}

	/**
	 * Register hooks related to the public-facing functionality
	 * of the theme.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_public_hooks() {

		new Front();

	}

	/**
	 * Register all the hooks related to Gutenberg
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_blocks_jsx_hooks() {

		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		$this->blocks = new Blocks_Jsx();

	}

	/**
	 * Register all the hooks related to Editing Experience Enhancements
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_editor_curation_hooks() {

		new Editor_Curation();

	}

	/**
	 * Register all the hooks related to the Lucide icon font
	 * (Button block icon picker).
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_lucide_icons_hooks() {

		new Lucide_Icons();

	}

	/**
	 * get the instance of the main theme class
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
