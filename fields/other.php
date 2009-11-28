<?php

// Handles comment_text field
class frontEd_comment extends frontEd_field {
	static function get_object_type() {
		return 'comment';
	}

	function wrap($content) {
		global $comment;

		if ( ! $this->check($comment->comment_ID) )
			return $content;

		return parent::wrap($content, $comment->comment_ID);
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
class frontEd_single_title extends frontEd_field {
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
class frontEd_author_desc extends frontEd_field {
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

		if ( empty($content) )
			$content = $this->placeholder();

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
class frontEd_widget extends frontEd_field {
	protected $field;

	static function get_object_type() {
		return 'widget';
	}

	function wrap($content) {
		return parent::wrap($content, 0);
	}

	protected function setup() {
		$this->field = str_replace('widget_', '', $this->get_filter());
	}

	function get($id) {
		$widget_id = $this->get_id($id);

		$widgets = get_option('widget_text');

		return $widgets[$widget_id][$this->field];
	}

	function save($id, $content) {
		$widget_id = $this->get_id($id);

		$widgets = get_option('widget_text');
		$widgets[$widget_id][$this->field] = $content;

		update_option('widget_text', $widgets);

		if ( $this->field == 'text' && $widgets[$widget_id]['filter'] )
			$content = wpautop($content);

		return $content;
	}

	function check($id = 0) {
		return current_user_can('edit_themes');
	}

	protected function get_id($id) {
		return str_replace('text-', '', $id);
	}
}

// Handles bloginfo fields
class frontEd_bloginfo extends frontEd_field {
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

	function check($id = 0) {
		return current_user_can('manage_options');
	}
}

frontEd_image::init();

class frontEd_image extends frontEd_field {
	static function init() {
		add_action('admin_print_styles', array(__CLASS__, 'styles'));

		register_uninstall_hook(FEE_PLUGIN_FILE, array(__CLASS__, 'uninstall'));
	}

	static function get_object_type() {
		return 'option';
	}

	function wrap($img, $id) {
		if ( ! $this->check() )
			return $img;

		return parent::wrap($img, $id);
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

	static function styles() {
		if ( $GLOBALS['pagenow'] != 'media-upload.php' )
			return;

		wp_enqueue_style('editable-image-box', plugins_url('style.css', FEE_PLUGIN_FILE));
	}

	static function uninstall() {
		global $wpdb;

		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'editable!_image!_%' ESCAPE '!'");
	}
}

function editable_image($key, $default_url, $extra_attr = '', $echo = true) {
	$attr = wp_parse_args($extra_attr);

	if ( ! $src = frontEd_image::get($key) )
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

	if ( ! $echo )
		return $img;

	echo $img;
}

