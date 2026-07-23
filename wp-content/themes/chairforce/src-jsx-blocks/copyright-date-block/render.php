<?php
$attributes = $attributes ?? [];

$block_props   = get_block_wrapper_attributes();
$starting_year = $attributes['startingYear'] ?: '';

printf( '
	<span %s>
		<span>Copyright &copy; %s%s%s</span>
	</span>',
	$block_props,
	$starting_year ?: '',
	( $starting_year && $starting_year < date( 'Y' ) ) ? ' - ' : '',
	date( 'Y' )
);
