<?php
/**
 * One-off: build the Chairs mega menu (Pattern A) in Primary Nav.
 *
 * @deprecated Use bin/setup-primary-nav-menu.php for the full Primary Nav tree.
 *
 * Usage: ddev wp eval-file wp-content/themes/chairforce/bin/setup-chairs-menu.php
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/setup-primary-nav-menu.php';
