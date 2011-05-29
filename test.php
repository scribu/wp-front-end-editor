<?php

// Acceptance test. Just drop this file into your mu-plugins folder.

add_action('loop_start', function( $wp_query ) {
	if ( $wp_query !== $GLOBALS['wp_the_query'] )
		return;

	echo '<strong>Input post meta:</strong><br>';
	echo html( 'div', editable_post_meta(get_the_ID(), 'price', 'input', false ) );

	echo '<strong>Rich post meta:</strong><br>';
	echo wpautop( editable_post_meta(get_the_ID(), 'degrees', 'rich', false ) );

	echo '<strong>Dropdown post meta:</strong><br>';
	echo html( 'div', editable_post_meta( get_the_ID(), 'my_key', array(
		'type' => 'select',
		'values' => array(
		    'val_1' => 'Title 1',
		    'val_2' => 'Title 2'
		),
		false
	) ) );

	echo '<strong>Input option:</strong><br>';
	echo html( 'div', editable_option( array(
		'key' => 'price',
		'type' => 'input',
		'echo' => false
	) ) );

	echo '<div style="overflow:hidden">';
	echo '<strong>Editable image:</strong><br>';
	editable_image( 'test', 'http://localhost/wp/wp-content/themes/twentyten/images/headers/fern.jpg' );
	echo '</div>';

	echo '<strong>Post thumbnail:</strong><br>';
	echo get_the_post_thumbnail( get_the_ID() );
});

