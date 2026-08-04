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

		add_filter( 'register_block_type_args', [ $this, 'enable_media_text_block_gap' ], 10, 2 );

		add_filter( 'render_block', [ $this, 'apply_media_text_split_section_content_gap' ], 10, 2 );

		/**
		 * Globally disable the Block Directory.
		 */
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

		/**
		 * Globally disable the Pattern Directory.
		 */
		add_filter( 'should_load_remote_block_patterns', '__return_false' );

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
	 * Enable block gap control on Media & Text for Split Section content spacing.
	 *
	 * @param array  $args       Block type registration arguments.
	 * @param string $block_type Block type name.
	 *
	 * @return array
	 */
	public function enable_media_text_block_gap( $args, $block_type ) {
		if ( 'core/media-text' !== $block_type ) {
			return $args;
		}

		if ( empty( $args['supports']['spacing'] ) || ! is_array( $args['supports']['spacing'] ) ) {
			$args['supports']['spacing'] = [];
		}

		$args['supports']['spacing']['blockGap'] = true;

		return $args;
	}

	/**
	 * Apply Split Section content gap from block attributes on the frontend.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block data.
	 *
	 * @return string
	 */
	public function apply_media_text_split_section_content_gap( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/media-text' !== $block['blockName'] ) {
			return $block_content;
		}

		$class_name = $block['attrs']['className'] ?? '';
		if ( false === strpos( $class_name, 'is-style-split-section' ) ) {
			return $block_content;
		}

		$gap_css = chairforce_resolve_block_gap_css( $block['attrs']['style']['spacing']['blockGap'] ?? null );
		if ( ! $gap_css ) {
			return $block_content;
		}

		if ( ! preg_match( '/<div class="wp-block-media-text__content"(\s[^>]*)?>/', $block_content, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $block_content;
		}

		$full_match = $matches[0][0];
		$attrs      = $matches[1][0] ?? '';
		$style_rule = '--cf-split-section-content-gap:' . $gap_css;

		if ( preg_match( '/style="([^"]*)"/', $attrs, $style_match ) ) {
			$new_style   = rtrim( $style_match[1], ';' ) . ';' . $style_rule;
			$replacement = str_replace( $style_match[0], 'style="' . esc_attr( $new_style ) . '"', $full_match );
		} else {
			$replacement = str_replace( '>', ' style="' . esc_attr( $style_rule ) . '">', $full_match );
		}

		return substr_replace( $block_content, $replacement, $matches[0][1], strlen( $full_match ) );
	}
}
