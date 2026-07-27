<?php
/**
 * ACF field hooks for menu item options.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'acf/load_field/name=utility_icon',
	function ( $field ) {

		$field['choices'] = chairforce_get_lucide_icon_choices();

		return $field;

	}
);

add_filter(
	'acf/load_field/key=field_chairforce_menu_utility_icon',
	function ( $field ) {

		$field['choices'] = chairforce_get_lucide_icon_choices();

		return $field;

	}
);
