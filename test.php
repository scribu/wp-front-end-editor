<?php

// Acceptance test. Just drop this file into your mu-plugins folder.

class FEE_Tests {

	function init() {
		if ( !function_exists( 'editable_image' ) )
			return;

		add_action( 'loop_start', array( __CLASS__, 'input_types' ) );
		add_action( 'template_redirect', array( __CLASS__, 'create_post' ) );
	}

	function input_types( $wp_query ) {
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

		echo '<strong>Post excerpt:</strong><br>';
		the_excerpt();

		echo '<div style="overflow:hidden">';
		echo '<strong>Editable image:</strong><br>';
		editable_image( 'test', 'http://localhost/wp/wp-content/themes/twentyten/images/headers/fern.jpg' );
		echo '</div>';

		if ( function_exists( 'get_the_post_thumbnail' ) ) {
			echo '<strong>Post thumbnail:</strong><br>';
			echo get_the_post_thumbnail( get_the_ID() );
		}
	}

	function create_post() {
		if ( is_page('create-post') ) {
			fee_inject_dummy_post();
		}
	}
}

add_action( 'init', array( 'FEE_Tests', 'init' ) );

