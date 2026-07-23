<?php
$block              = $block ?? [];
$wrapper_attributes = get_block_wrapper_attributes();
$backend            = isset( $is_preview ) && $is_preview;

$field_id = get_field( 'acf_field_reference' );
$tag      = get_field( 'display_tag' );

$allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ];
$tag          = in_array( $tag, $allowed_tags, true ) ? $tag : 'div';

if ( empty( $field_id ) ) {
	return;
}

$value = get_field( $field_id, get_the_ID() );

if ( $value === null || $value === '' ) {

	if($backend){
		printf(
			'<div class="alert-box warning"><%1$s %2$s>%3$s</%1$s></div>',
			esc_html( $tag ), $wrapper_attributes,
			esc_html__( 'ACF display field: ', 'briks' ). '<code>' . $field_id . '</code>'
		);
	}


	return;
}

if ( is_array( $value ) || is_object( $value ) ) {
	$value = esc_html( wp_json_encode( $value ) );
} else {
	$value = wp_kses_post( do_blocks( do_shortcode( $value ) ) );
}

printf( '<%1$s %2$s>%3$s</%1$s>', esc_html( $tag ), $wrapper_attributes, $value );
