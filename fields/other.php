<?php

// Handles comment_text field
class FEE_Field_Comment extends FEE_Field_Base {

	static function get_object_type() {
		return 'comment';
	}

	function wrap($content) {
		global $comment;

		if ( ! $this->check($comment->comment_ID) )
			return $content;

		return parent::wrap(wpautop($content), $comment->comment_ID);
	}

	function get($comment_id) {
		$comment = get_comment($comment_id);
		return $comment->comment_content;
	}

	function save($comment_id, $content) {
		wp_update_comment(array(
			'comment_ID' => $comment_id,
			'comment_content' => $content
		));

		return $content;
	}

	function check($comment_id = 0) {
		if ( current_user_can('moderate_comments') )
			return true;

		global $user_ID;

		$comment = get_comment($comment_id);

		return $user_ID == $comment->user_id;
	}
}

// Handles single_*_title fields
class FEE_Field_Single_Title extends FEE_Field_Base {

	static function get_object_type() {
		return 'term';
	}

	private $taxonomy;

	protected function setup() {
		remove_filter($this->get_filter(), 'strip_tags');

		list($a, $tax, $b) = explode('_', $this->get_filter());

		$translate = array(
			'cat' => 'category',
			'tag' => 'post_tag'
		);

		$this->taxonomy = $translate[$tax];
	}

	function wrap($title) {
		if ( ! $this->check() )
			return $title;

		if ( ! $term = get_term_by('name', $title, $this->taxonomy) )
			return $title;

		return parent::wrap($title, $term->term_id);
	}

	function get($term_id) {
		return get_term_field('name', $term_id, $this->taxonomy, 'edit');
	}

	function save($term_id, $title) {
		wp_update_term($term_id, $this->taxonomy, array('name' => $title));

		return $title;
	}

	function check($id = 0) {
		return current_user_can('manage_categories');
	}
}

// Handles the_author_description field
class FEE_Field_Author_Desc extends FEE_Field_Base {

	static function get_object_type() {
		return 'user';
	}

	function wrap($content, $author_id = '') {
		if ( ! $author_id )
			$author_id = $this->guess_author_id();

		if ( ! $author_id )
			return $content;

		if ( ! $this->check($author_id) )
			return $content;

		$content = $this->placehold($content);

		return parent::wrap($content, $author_id);
	}

	// Retrieve the current data for the field
	function get($author_id) {
		return get_usermeta($author_id, 'description');
	}

	function save($author_id, $content) {
		update_usermeta($author_id, 'description', $content);

		return $content;
	}

	function check($author_id = 0) {
		if ( current_user_can('edit_users') )
			return true;

		global $user_ID;

		return $user_ID == $author_id;
	}

	// WP < 2.9
	function guess_author_id() {
		if ( function_exists('get_the_author_meta') )
			return get_the_author_meta('id');

		global $authordata;

		return $authordata->ID;
	}
}

// Handles widget_text and widget_title fields
class FEE_Field_Widget extends FEE_Field_Base {

	protected $field;

	static function get_object_type() {
		return 'widget';
	}

	protected function setup() {
		$this->field = str_replace('widget_', '', $this->get_filter());
	}

	function wrap($content) {
		if ( ! $this->check() )
			return $content;

		if ( 'text' == $this->field )
			$content = $this->placehold($content);

		return parent::wrap($content, 0);
	}

	function get($id) {
		return $this->do_('get', $id);
	}

	function save($id, $content) {
		return $this->do_('save', $id, $content);
	}

	private function do_($action, $id, $content = '') {
		list($widget_type, $widget_id) = explode('-', $id);

		$widget_key = 'widget_' . $widget_type;
		$widgets = get_option($widget_key);
		$data =& $widgets[$widget_id][$this->field]; 

		if ( 'get' == $action ) {
			return $data;
		}

		if ( 'save' == $action ) {
			$data = $content;

			update_option($widget_key, $widgets);

			if ( 'text' == $widget_type
			  && 'text' == $this->field
			  && $widgets[$widget_id]['filter'] )
				$content = wpautop($content);

			if ( 'text' == $this->field )
				$content = $this->placehold($content);

			return $content;
		}
	}

	function check($id = 0) {
		return current_user_can('edit_themes');
	}
}

// Handles bloginfo fields
class FEE_Field_Bloginfo extends FEE_Field_Base {

	static function get_object_type() {
		return 'option';
	}

	function wrap($content, $show) {
		if ( ! $this->check() )
			return $content;

		if ( empty($show) && $content == get_option('blogname') )
			$show = 'name';

		if ( $show != 'description' && $show != 'name' )
			return $content;

		return parent::wrap($content, $show);
	}

	function get($show) {
		return get_option('blog' . $show);
	}

	function save($show, $content) {
		update_option('blog' . $show, $content);

		return $content;
	}

	function check($key = 0) {
		return current_user_can('manage_options');
	}
}

// Handles editable_option fields
class FEE_Field_Option extends FEE_Field_Base {

	static function init($file) {
		register_uninstall_hook($file, array(__CLASS__, 'uninstall'));
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_option!_%' ESCAPE '!'");
	}

	static function get_object_type() {
		return 'option';
	}

	function wrap($content, $key, $type) {
		if ( ! $this->check($key) )
			return $content;

		$content = $this->placehold($content);

		$id = implode('#', array($key, $type));

		return parent::wrap($content, $id);
	}

	function get($id) {
		list($key, $type) = explode('#', $id);

		return get_option($key);
	}

	function save($id, $content) {
		list($key, $type) = explode('#', $id);

		update_option($key, $content);

		$content = $this->placehold($content);

		return $content;
	}

	function check($key = 0) {
		return current_user_can('manage_options');
	}
}

function editable_option($key, $safety = true, $type = 'input', $echo = true) {
	if ( $safety )
		$key = "editable_option_$key";

	$output = apply_filters('editable_option', get_option($key), $key, $type);

	if ( $echo )
		echo $output;

	return $output;
}

// Handles editable_image fields
class FEE_Field_Image extends FEE_Field_Base {

	static function init($file) {
		register_uninstall_hook($file, array(__CLASS__, 'uninstall'));
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_image!_%' ESCAPE '!'");
	}

	static function get_object_type() {
		return 'option';
	}

	function wrap($img, $key) {
		if ( ! $this->check() )
			return $img;

		return parent::wrap($img, $key);
	}

	function get($id) {
		return get_option(self::get_key($id));
	}

	function save($id, $url) {
		if ( $url == -1 )
			delete_option(self::get_key($id));
		else
			update_option(self::get_key($id), $url);

		return $url;
	}

	function check($id = 0) {
		return current_user_can('edit_themes');
	}

	private static function get_key($key) {
		return 'editable_image_' . trim(strip_tags($key));
	}
}

function editable_image($key, $default_url, $extra_attr = '', $echo = true) {
	$attr = wp_parse_args($extra_attr, array(
		'id' => $key
	));

	if ( ! $src = FEE_Field_Image::get($key) )
		$src = $default_url;
	$attr['src'] = $src;

	$attr_str = '';
	foreach ( $attr as $a_key => $a_value ) {
		$a_key = trim(strip_tags($a_key));
		$a_value = trim(esc_attr($a_value));

		if ( empty($a_key) )
			continue;

		$attr_str .= " $a_key='$a_value'";
	}

	$attr_str = ltrim($attr_str);

	$img = apply_filters('editable_image', "<img $attr_str />", $key, $default_url);

	if ( $echo )
		echo $img;
	
	return $img;
}

