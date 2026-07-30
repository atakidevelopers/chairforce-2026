<?php
/**
 * Required functions parts
 */
require get_stylesheet_directory() . '/includes/init.php';
/**
 * Theme Bootstrap.
 * @since    0.0.1
 */
function Chairforce() {

	return Chairforce\Init::get_instance();

}

Chairforce();


// Shortcode_display :  year    // like: show_post_list

function year_cb($atts = [], $content = null, $tag = '')
{
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);


	$atts = shortcode_atts( array(
        // Update the default Values
		'arg_1' => true,
		'arg_2' => 'arg Value',

	), $atts );

	// start output
	$output = '';


	// Update output
//	$output .= '<div class="">';
	$output .= date( 'Y');
//	$output .= '</div>';

	// return output
	return $output;
}
add_shortcode('year', 'year_cb');
