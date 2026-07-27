<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Mega_Menu' ) ) {
	return;
}

/**
 * Registers menu infrastructure: walker dependencies, hooks, and helpers.
 */
class Mega_Menu {

	/**
	 * Mega_Menu constructor.
	 */
	public function __construct() {

		$this->load_dependencies();

	}

	/**
	 * Load menu PHP dependencies.
	 */
	private function load_dependencies(): void {

		require_once get_theme_file_path( 'includes/menu/chairforce-lucide-icon-choices.php' );
		require_once get_theme_file_path( 'includes/menu/menu-functions.php' );
		require_once get_theme_file_path( 'includes/menu/walker/class-primary-walker.php' );
		require_once get_theme_file_path( 'includes/menu/menu-hooks.php' );
		require_once get_theme_file_path( 'includes/menu/menu-acf.php' );

	}

}
