<?php

namespace Chairforce;
// exit if file is called directly
use WP_Block_Editor_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\Editor_Curation' ) ) {
	return;
}


/**
 * This class deals with editor Curation Experience to provide a better experience to the user
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Editor_Curation {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->register_hooks();

	}

	/**
	 * Register required hooks
	 */
	public function register_hooks() {


		add_filter( 'block_editor_settings_all', [ $this, 'disable_openverse' ], 10, 2 );

		add_filter( 'block_editor_settings_all', [ $this, 'set_default_image_size' ], 10, 2 );


		add_filter( 'block_editor_settings_all', [ $this, 'restrict_settings_to_administrator_only' ], 10, 2 );

		/**
		 * Globally disable the Block Directory.
		 */
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

		/**
		 * Globally disable the Pattern Directory.
		 */
		add_filter( 'should_load_remote_block_patterns', '__return_false' );

		add_filter( 'register_block_type_args', [ $this, 'curate_chairforce_blocks' ], 10, 2 );

	}

	/**
	 * Set the default image size when inserted into the Editor.
	 *
	 * @param array $settings The current block editor settings.
	 * @param array $context The current editor context, including post information.
	 *
	 * @return array          The modified block editor settings.
	 */
	public function set_default_image_size( $settings, $context ) {
		if (
			! empty( $context->post ) &&
			'post' === $context->post->post_type
		) {
			// Set the default image size to full.
			$settings['imageDefaultSize'] = 'full';
		}

		return $settings;
	}


	/**
	 * Disable Openverse.
	 *
	 * @param array $settings The current block editor settings.
	 * @param array $context The current editor context, including post information.
	 *
	 * @return array          The modified block editor settings.
	 */
	public function disable_openverse( $settings, $context ) {
		$settings['enableOpenverseMediaCategory'] = false;

		return $settings;
	}

	/**
	 * Allow only Administrators to access the block locking user interface.
	 *
	 * @param array $settings Default editor settings.
	 * @param WP_Block_Editor_Context $context The current block editor context.
	 */
	public function restrict_settings_to_administrator_only( $settings, $context ) {
		$is_administrator = current_user_can( 'edit_theme_options' );

		if ( ! $is_administrator ) {
			// Allow only Administrators to access the block locking user interface.
			$settings['canLockBlocks']      = false;
			$settings['codeEditingEnabled'] = false;
		}

		return $settings;
	}

	/**
	 * Theme block registration tweaks (inserter visibility, etc.).
	 *
	 * @param array  $args       Block type registration args.
	 * @param string $block_type Block name.
	 *
	 * @return array
	 */
	public function curate_chairforce_blocks( array $args, string $block_type ): array {

		if (
			'chairforce/editor-placeholder' === $block_type
			|| 'chairforce/site-header' === $block_type
			|| 'chairforce/product-filters' === $block_type
		) {
			$args['supports']['inserter'] = false;
		}

		return $args;
	}
}
