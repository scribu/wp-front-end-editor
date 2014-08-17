<?php

// Handles comment_text field
class FEE_Field_Comment extends FEE_Field_Base {

	static function get_object_type() {
		return 'comment';
	}

	function wrap( $content = null, $data = null ) {
		global $comment;

		$data = array( 'comment_id' => $comment->comment_ID );

		if ( !$this->check( $data ) )
			return $content;

		return parent::wrap( wpautop( $content ), $data );
	}

	function get( $data ) {
		extract( $data );

		return get_comment( $comment_id )->comment_content;
	}

	function save( $data, $content ) {
		extract( $data );

		wp_update_comment( array(
			'comment_ID' => $comment_id,
			'comment_content' => $content
		) );
	}

	function get_filtered( $data ) {

		ob_start();
		comment_text( $data['comment_id'] );
		return ob_get_clean();
	}

	function check( $data = 0 ) {
		extract( $data );

		if ( current_user_can( 'moderate_comments' ) )
			return true;

		global $user_ID;

		$comment = get_comment( $comment_id );

		return $user_ID == $comment->user_id;
	}
}


// Handles term_{$field} fields
class FEE_Field_Term_Field extends FEE_Field_Base {

	protected $field;

	static function get_object_type() {
		return 'term';
	}

	function setup() {
		$this->field = str_replace( 'term_', '', $this->filter );
	}

	function wrap( $content = null, $data = null, $term_id = null, $taxonomy = null ) {
		$data = compact( 'term_id', 'taxonomy' );

		if ( !$this->check( $data ) )
			return $content;

		return parent::wrap( $this->placehold( $content ), $data );
	}

	function get( $data ) {
		extract( $data );

		return get_term_field( $this->field, $term_id, $taxonomy, 'raw' );
	}

	function save( $data, $content ) {
		extract( $data );

		wp_update_term( $term_id, $taxonomy, array( $this->field => $content ) );

		return $content;
	}

	function get_filtered( $data ) {
		extract( $data );

		return get_term_field( $this->field, $term_id, $taxonomy );
	}

	function check( $data = 0 ) {
		extract( $data );

		return current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms );
	}
}


// Handles single_*_title fields
class FEE_Field_Single_Title extends FEE_Field_Term_Field {

	function setup() {
		$this->field = 'name';
		remove_filter( $this->filter, 'strip_tags' );
	}

	function wrap( $content = null, $data = null, $term_id = null, $taxonomy = null, $title = null ) {
		$term = get_queried_object();

		return parent::wrap( $title, $term->term_id, $term->taxonomy );
	}
}


// Handles the_author_description field
class FEE_Field_Author_Desc extends FEE_Field_Base {

	static function get_object_type() {
		return 'user';
	}

	function wrap( $content = null, $data = null, $author_id = '' ) {

		if ( !$author_id )
			$author_id = $GLOBALS['authordata']->ID;

		if ( !$this->check( compact( 'author_id' ) ) )
			return $content;

		$content = $this->placehold( $content );

		return parent::wrap( $content, compact( 'author_id' ) );
	}

	// Retrieve the current data for the field
	function get( $data ) {
		extract( $data );

		return get_user_meta( $author_id, 'description', true );
	}

	function save( $data, $content ) {
		extract( $data );

		update_user_meta( $author_id, 'description', $content );

		return $content;
	}

	function get_filtered( $data ) {
		extract( $data );

		return get_the_author_meta( 'description', $author_id );
	}

	function check( $data = 0 ) {
		extract( $data );

		return current_user_can( 'edit_user', $author_id );
	}
}


// Handles bloginfo fields
class FEE_Field_Bloginfo extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	function wrap( $content = null, $data = null, $show = '' ) {
		if ( !$this->check() )
			return $content;

		if ( empty( $show ) && get_option( 'blogname' ) == $content )
			$show = 'name';

		if ( !in_array( $show, array( 'name', 'description' ) ) )
			return $content;

		return parent::wrap( $content, compact( 'show' ) );
	}

	function get( $data ) {
		extract( $data );

		return get_option( 'blog' . $show );
	}

	function save( $data, $content ) {
		extract( $data );

		update_option( 'blog' . $show, $content );
	}

	function get_filtered( $data ) {
		return get_bloginfo( $data['show'] );
	}

	function check( $data = 0 ) {
		return current_user_can( 'manage_options' );
	}
}


// Handles editable_option fields
class FEE_Field_Option extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	static function init( $file ) {
		register_uninstall_hook( $file, array( __CLASS__, 'uninstall' ) );
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_option!_%' ESCAPE '!'" );
	}

	function wrap( $content = null, $data = null, $key = null, $ui = null ) {
		$data = compact( 'key', 'ui' );

		if ( !$this->check( $data ) )
			return $content;

		$content = $this->placehold( $content );

		return parent::wrap( $content, $data );
	}

	function get( $data ) {
		extract( $data );

		return get_option( $key );
	}

	function save( $data, $content ) {
		extract( $data );

		update_option( $key, $content );
	}

	function get_filtered( $data ) {
		extract( $data );

		return $this->placehold( get_option( $key ) );
	}

	function check( $data = 0 ) {
		extract( $data );

		$cap = ( 0 === strpos( $key, 'editable_option_' ) ) ? 'edit_theme_options' : 'manage_options';

		return current_user_can( $cap );
	}
}


// Handles editable_image fields
class FEE_Field_Image extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	static function init( $file ) {
		add_action( 'wp_ajax_fee_image_insert', array( __CLASS__, 'image_insert' ) );
		register_uninstall_hook( $file, array( __CLASS__, 'uninstall' ) );
	}

	static function image_insert() {
		add_filter( 'media_send_to_editor', array( __CLASS__, '_capture_html' ), 99 );
		media_upload_form_handler();
	}

	static function _capture_html( $html ) {
		die( $html );
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_image!_%' ESCAPE '!'" );
	}

	function wrap( $content = null, $data = null, $img = null, $key = null ) {
		if ( !$this->check() )
			return $img;

		return parent::wrap( $img, compact( 'key' ) );
	}

	function get( $data ) {
		return get_option( self::get_key( $data ) );
	}

	function save( $data, $url ) {
		if ( $url == -1 )
			delete_option( self::get_key( $data ) );
		else
			update_option( self::get_key( $data ), $url );
	}

	function get_filtered( $data ) {
		return $this->get( $data );
	}

	private static function get_key( $data ) {
		extract( $data );

		return 'editable_image_' . trim( strip_tags( $key ) );
	}

	function check( $data = 0 ) {
		return current_user_can( 'edit_theme_options' );
	}
}

