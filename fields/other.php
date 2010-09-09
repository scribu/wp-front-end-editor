<?php

// Handles comment_text field
class FEE_Field_Comment extends FEE_Field_Base {

	static function get_object_type() {
		return 'comment';
	}

	function wrap( $content ) {
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

		return $content;
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
		$this->field = str_replace( 'term_', '', $this->get_filter() );
	}

	function wrap( $content, $term_id, $taxonomy ) {
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

	function check( $data = 0 ) {
		extract( $data );

		return current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms );
	}
}

// Handles single_*_title fields
class FEE_Field_Single_Title extends FEE_Field_Term_Field {

	private $taxonomy;

	function setup() {
		remove_filter( $this->get_filter(), 'strip_tags' );

		list( $a, $tax, $b ) = explode( '_', $this->get_filter() );

		$translate = array(
			'cat' => 'category',
			'tag' => 'post_tag'
		);

		$this->taxonomy = $translate[$tax];
		$this->field = 'name';
	}

	function wrap( $title ) {
		if ( !$term = get_term_by( 'name', $title, $this->taxonomy ) )
			return $title;

		return parent::wrap( $title, $term->term_id, $this->taxonomy );
	}
}

// Handles the_author_description field
class FEE_Field_Author_Desc extends FEE_Field_Base {

	static function get_object_type() {
		return 'user';
	}

	function wrap( $content, $author_id = '' ) {
		if ( !$author_id )
			return $content;

		if ( !$this->check( $author_id ) )
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

	function check( $data = 0 ) {
		extract( $data );

		return current_user_can( 'edit_user', $author_id );
	}
}

// Handles dynamic_sidebar_params fields
class FEE_Field_Widget extends FEE_Field_Base {

	static function get_object_type() {
		return 'widget';
	}

	function wrap( $params ) {
		if ( !$this->check() )
			return $params;

		$p =& $params[0];

		$data = array( 'widget_id' => $p['widget_id'], 'sidebar_id' => $p['id'] );

		list( $before, $after ) = scbUtil::split_at( '</', parent::wrap( '', $data ) );

		$p['before_widget'] = $p['before_widget'] . $before;
		$p['after_widget'] = $after . $p['after_widget'];

		return $params;
	}

	function get( $data ) {
		return $this->do_( 'get', $data );
	}

	function save( $data, $content ) {
		return $this->do_( 'save', $data, $content );
	}

	private function do_( $action, $data, $content = '' ) {
		global $wp_widget_factory;

		extract( $data );

		// Get widget type and number
		$id_base = explode( '-', $widget_id );
		$widget_nr = array_pop( $id_base );
		$id_base = implode( '-', $id_base );

		// Get widget instance
		$widget_key = 'widget_' . $id_base;
		$widgets = get_option( $widget_key );
		$instance =& $widgets[ $widget_nr ];

		// Get widget class
		foreach ( $wp_widget_factory->widgets as $widget )
			if ( $widget->id_base == $id_base )
				break;

		if ( 'get' == $action ) {
			if ( 'text' == $id_base && FEE_Core::$options->rich )
				$instance['text'] = wpautop($instance['text']);

			$widget->form( $instance );
		}

		if ( 'save' == $action ) {
			$new_instance = stripslashes_deep( reset( $_POST[ 'widget-' . $id_base ] ) );

			$instance = $widget->update( $new_instance, $instance );

			update_option( $widget_key, $widgets );

			$this->data = compact( 'sidebar_id', 'widget_id' );
			add_filter( 'sidebars_widgets', array( $this, '_hack' ) );
			dynamic_sidebar( $sidebar_id );
			remove_filter( 'sidebars_widgets', array( $this, '_hack' ) );
		}

		die;
	}

	// temporarirly remove all other widgets from a specific sidebar
	function _hack( $sidebar_widgets ) {
		extract( $this->data );

		$sidebar_widgets[$sidebar_id] = array( $widget_id );

		return $sidebar_widgets;
	}

	function check( $data = 0 ) {
		return current_user_can( 'edit_themes' );
	}
}


// Handles bloginfo fields
class FEE_Field_Bloginfo extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	function wrap( $content, $show ) {
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

		return $content;
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

	function wrap( $content, $key, $type ) {
		extract( self::convert_type( $type ) );

		$data = compact( 'key', 'type', 'values' );

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

		$content = $this->placehold( $content );

		return $content;
	}

	function check( $data = 0 ) {
		extract( $data );

		$cap = ( 0 === strpos( $key, 'editable_option_' ) ) ? 'edit_themes' : 'manage_options';

		return current_user_can( $cap );
	}
}

/**
 * @param string $key The option key
 * @param bool $theme_option Wether it's arbitrary theme text, or a core site option like 'description' or 'time_format'
 * @param string $type The type of UI. Can be 'input', 'textarea' or 'rich'
 * @param bool $echo Wether to echo or return the result
 */
function editable_option( $key, $theme_option = true, $type = 'input', $echo = true ) {
	if ( $theme_option )
		$key = "editable_option_$key";

	$output = apply_filters( 'editable_option', get_option( $key ), $key, $type );

	if ( $echo )
		echo $output;

	return $output;
}

// Handles editable_image fields
class FEE_Field_Image extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	static function init( $file ) {
		register_uninstall_hook( $file, array( __CLASS__, 'uninstall' ) );
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_image!_%' ESCAPE '!'" );
	}

	function wrap( $img, $key ) {
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

		return $url;
	}

	private static function get_key( $data ) {
		extract( $data );

		return 'editable_image_' . trim( strip_tags( $key ) );
	}

	function check( $data = 0 ) {
		return current_user_can( 'edit_themes' );
	}
}

/**
 * @param string $key The option key
 * @param string $default_url An absolute URL to be used as the default
 * @param string|array $extra_attr Extra HTML attributes for the <img> tag
 * @param bool $echo Wether to echo or return the result
 */
function editable_image( $key, $default_url, $extra_attr = '', $echo = true ) {
	$attr = wp_parse_args( $extra_attr, array(
		'id' => $key
	) );

	if ( !$src = FEE_Field_Image::get( compact( 'key' ) ) )
		$src = $default_url;
	$attr['src'] = $src;

	$attr_str = '';
	foreach ( $attr as $a_key => $a_value ) {
		$a_key = trim( strip_tags( $a_key ) );
		$a_value = trim( esc_attr( $a_value ) );

		if ( empty( $a_key ) )
			continue;

		$attr_str .= " $a_key='$a_value'";
	}

	$attr_str = ltrim( $attr_str );

	$img = apply_filters( 'editable_image', "<img $attr_str />", $key, $default_url );

	if ( $echo )
		echo $img;

	return $img;
}

