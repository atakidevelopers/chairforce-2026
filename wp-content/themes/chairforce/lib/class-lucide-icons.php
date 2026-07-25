<?php

namespace Chairforce;

/**
 * Lucide Icons.
 *
 * Centralises all PHP related to the theme's Lucide icon font (currently
 * used by the Button block's icon picker, see src/js-admin/button-icons.js
 * for the editor UI and src/sass/blocks/_button.scss for the icon classes).
 *
 * The icon font stylesheet is a static, hand-authored asset (not part of
 * the webpack/Sass build pipeline - see assets/css/button-icon-font.css
 * for why) and is enqueued globally via `enqueue_block_assets`, which
 * fires on both the front end and inside the block editor's iframed
 * canvas. It is loaded on every screen since icons are used throughout
 * the site, not just where the Button block happens to be present.
 *
 * @since      2.0.0
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Lucide_Icons {

	/**
	 * The handle used to enqueue the icon font stylesheet.
	 *
	 * @var string
	 */
	private $style_handle = 'chairforce-lucide-icon-font';

	/**
	 * Lucide_Icons constructor.
	 */
	public function __construct() {

		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @access private
	 */
	private function register_hooks() {

		add_action( 'enqueue_block_assets', [ $this, 'enqueue_icon_font' ] );

	}

	/**
	 * Enqueue the static Lucide icon font stylesheet.
	 *
	 * Fires on `enqueue_block_assets`, so this loads globally - on the
	 * front end and inside the block editor (incl. its iframed canvas).
	 */
	public function enqueue_icon_font() {

		$css_path = 'assets/css/button-icon-font.css';
		$css_file = get_theme_file_path( $css_path );

		if ( ! file_exists( $css_file ) ) {
			return;
		}

		wp_enqueue_style(
			$this->style_handle,
			get_theme_file_uri( $css_path ),
			[],
			filemtime( $css_file )
		);

	}

}
