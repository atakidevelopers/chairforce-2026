<?php

namespace Chairforce;
// exit if file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out.
if ( class_exists( 'Chairforce\Acf' ) ) {
	return;
}


/**
 * This class will set up the Acf options and Acf Blocks.
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Acf {


	/**
	 * @var array a list of removed blocks
	 */
	private array $removed_blocks = [];

	/**
	 * @var array a list of blocks directories
	 */
	private array $blocks_directories = [];

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
	public function register_hooks(): void {

		/**
		 * Register ACF Blocks
		 */
		add_action( 'acf/init', [ $this, 'register_blocks_with_acf' ] );
		add_action( 'acf/init', [ $this, 'register_acf_options' ] );

		add_filter( 'acf/blocks/wrap_frontend_innerblocks', [ $this, 'remove_innerblocks_wrapper_for_layout' ], 10, 2 );


		/**
		 * Dynamically update field choices
		 */
		add_filter( 'acf/load_field/name=registered_taxonomies', array( $this, 'acf_field_choices_taxonomies' ) );


		add_filter( 'acf/fields/select/query/key=field_block_acf_field_display_field_reference', array(
			$this,
			'acf_all_fields_select_query'
		), 10, 2 );
		add_filter( 'acf/prepare_field/key=field_block_acf_field_display_field_reference', array(
			$this,
			'acf_all_fields_prepare'
		) );

	}

	/**
	 * Register ACF Options
	 */
	public function register_acf_options() {


		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return null;
		}

		/** @noinspection SpellCheckingInspection */
		acf_add_options_page(
			[
				'page_title' => esc_html__( 'Theme Options', 'chairforce' ),
				'menu_title' => esc_html__( 'Theme Options', 'chairforce' ),
				'menu_slug'  => 'chairforce-theme-options', // after changing the slug, update Fields Group location as well
				'capability' => 'manage_options',
				// N&C infinity mark, derived from assets/images/nc-favicon.svg (cropped + recolored for the dark admin-menu background).
				'icon_url'   => 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( get_theme_file_path( 'assets/images/nc-menu-icon.svg' ) ) ),
			]
		);

	}

	/**
	 *
	 */
	public function remove_innerblocks_wrapper_for_layout( $wrap, $name ) {

		$block_name_starts_with = 'chairforce';

		if ( str_starts_with( $name, $block_name_starts_with ) ) {
			$wrap = false;
		}

		return $wrap;
	}

	/**
	 * Register ACF Blocks
	 * This is an autoloader for the blocks
	 * the block directories shall be auto registered under this path: wp-content/themes/chairforce/src-acf-blocks/
	 *
	 * @hooked acf/init
	 */
	public function register_blocks_with_acf() {

		/**
		 * If we do not have ACF activated, bail out early
		 */
		if ( ! function_exists( 'acf_register_block' ) ) {
			return null;
		}

		$remove_blocks = $this->get_removed_blocks();

		$blocks_directories = $this->get_blocks_directories();

		if ( empty( $blocks_directories ) ) {
			return null;
		}

		foreach ( $blocks_directories as $block_directory ) :
			$block_json = $block_directory . '/block.json';

			// Ensure that block.json exists for each block
			if ( file_exists( $block_json ) ) {
				// Register each block using its block.json metadata
				$block_dir_name = basename( $block_directory );
				/**
				 * If this block name is in $remove_blocks, continue
				 */
				if ( in_array( $block_dir_name, $remove_blocks, true ) ) {
					continue;
				}

				// Register each block using its block.json metadata
				register_block_type( $block_json );
			}

		endforeach;

	}

	/**
	 * Get removed blocks
	 * @return array directory names of blocks
	 */
	private function get_removed_blocks(): array {

		return apply_filters( CHAIRFORCE_PREFIX . 'remove_block_types', $this->removed_blocks );

	}

	/**
	 * @return array block directories paths
	 */
	public function get_blocks_directories(): array {

		if ( ! $this->blocks_directories ) {
			$blocks_dir = get_stylesheet_directory() . '/src-acf-blocks/';
			// Loop over each block folder in the blocks directory
			$dirs = glob( $blocks_dir . '*', GLOB_ONLYDIR );

			if ( ! empty( $dirs ) ) {
				$this->blocks_directories = $dirs;
			}
		}

		return apply_filters( CHAIRFORCE_PREFIX . 'acf_blocks_directories', $this->blocks_directories );

	}

	/**
	 * Populate ACF Field with choices
	 *
	 * @hooked 'acf/load_field/name=registered_taxonomies'
	 */
	public function acf_field_choices_taxonomies( $field ) {
		$field['choices'] = array();

		$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'labels' );

		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			$field['choices'][ $taxonomy_slug ] = $taxonomy->label;

		}

		return $field;

	}

	/**
	 * AJAX query provider (grouped by field group title).
	 *
	 * @hooked 'acf/fields/select/query/key=field_block_acf_field_display_field_reference'
	 */
	public function acf_all_fields_select_query( $response, $field ) {
		$search = '';
		if ( isset( $_POST['s'] ) ) {
			$search = (string) $_POST['s'];
		}
		if ( $search === '' && isset( $_POST['q'] ) ) {
			$search = (string) $_POST['q'];
		}
		$search = trim( sanitize_text_field( $search ) );

		$response = [
			'results' => [],
			'more'    => false,
		];

		$groups = function_exists( 'acf_get_field_groups' ) ? acf_get_field_groups() : [];
		if ( empty( $groups ) ) {
			return $response;
		}

		foreach ( $groups as $group ) {
			$group_key   = $group['key'];
			$group_title = $group['title'] ?: $group_key;

			$fields = function_exists( 'acf_get_fields' ) ? acf_get_fields( $group_key ) : [];
			if ( empty( $fields ) ) {
				continue;
			}

			$children = [];

			foreach ( $fields as $acf_field ) {
				$field_name = $acf_field['name'] ?? '';
				if ( $field_name === '' ) {
					continue;
				}

				if ( $search !== '' && stripos( $field_name, $search ) === false ) {
					continue;
				}

				$children[] = [
					'id'   => $field_name,
					'text' => $field_name,
				];
			}

			if ( ! empty( $children ) ) {
				$response['results'][] = [
					'text'     => $group_title,
					'children' => $children,
				];
			}
		}

		return $response;
	}

	/**
	 * Ensure saved value displays as selected after save (critical for ajax=1).
	 *
	 * @hooked 'acf/prepare_field/key=field_block_acf_field_display_field_reference'
	 */
	public function acf_all_fields_prepare( $field ) {
		$value = $field['value'] ?? '';
		if ( ! $value ) {
			return $field;
		}

		// Inject ONLY the selected key so Select2 can render it.
		$field['choices'] = [
			$value => $value,
		];

		return $field;
	}

}
