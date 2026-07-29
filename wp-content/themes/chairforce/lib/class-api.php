<?php

namespace Chairforce;
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\Api' ) ) {
	return;
}


/**
 * This class deals with API offered by the plugin
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Api {


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

		require_once get_theme_file_path( 'includes/rest-api/product-search.php' );
		require_once get_theme_file_path( 'includes/rest-api/quick-view.php' );

	}

}
