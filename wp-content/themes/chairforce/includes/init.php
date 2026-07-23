<?php
/**
 * Composer Auto Loader
 */
if ( ! file_exists( get_stylesheet_directory() . '/vendor/autoload.php' ) ) {
	wp_die( 'vendor directory missing. Please execute `composer install` to create vendor directory.' );
}
require_once get_stylesheet_directory() . '/vendor/autoload.php';

/**
 * Get Theme Constants
 */
require_once get_stylesheet_directory() . '/includes/constants.php';

/**
 * Get Helper functions
 */
require_once get_stylesheet_directory() . '/includes/helper-functions.php';

/**
 * Register CPTs
 */
require_once get_stylesheet_directory() . '/includes/register-cpt.php';

/**
 * Remove Comment System
 */
require_once get_stylesheet_directory() . '/includes/disable-comments.php';

