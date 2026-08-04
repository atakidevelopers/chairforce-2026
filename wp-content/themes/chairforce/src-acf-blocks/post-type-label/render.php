<?php
/**
 * Output the current post type label for query-loop search results.
 *
 * @package Chairforce
 */

$post_type = get_post_type();

if ( ! is_string( $post_type ) || '' === $post_type ) {
	return;
}

$post_type_object = get_post_type_object( $post_type );

if ( ! $post_type_object ) {
	return;
}

$label = $post_type_object->labels->singular_name ?? $post_type_object->label;

printf(
	'<div %s>%s</div>',
	get_block_wrapper_attributes(),
	esc_html( $label )
);
