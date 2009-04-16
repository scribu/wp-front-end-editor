<?php

// All field classes should extend from this one
class frontEd_field {
	// Mark the field as editable
	function wrap($content, $filter = '', $id = NULL) {
		if ( is_feed() )
			return $content;

		if ( empty($filter) )
			$filter = current_filter();

		if ( ! isset($id) )
			$id = $GLOBALS['post']->ID;

		$class = 'front-ed-' . $filter . ' front-ed';

		return "<span rel='{$id}' class='{$class}'>{$content}</span>";
	}

	// Retrieve the current data for the field
	function get($post_id, $name, $args) {
		trigger_error("This method must be implemented in a subclass", E_USER_ERROR);
	}

	// Save the data retrieved from the field
	function save($post_id, $content, $name, $args) {
		trigger_error("This method must be implemented in a subclass", E_USER_ERROR);
	}

	function check() {
		return current_user_can('edit_posts') or current_user_can('edit_pages');
	}
}


// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field {
	function wrap($content, $filter = '') {
		if ( ! self::check($GLOBALS['post']->ID) )
			return $content;

		if ( empty($filter) )
			$filter = current_filter();

		return parent::wrap($content, $filter);
	}

	function get($id, $filter) {
		$field = self::get_col($filter);

		$post = get_post($id, ARRAY_A);

		return $post[$field];
	}

	function save($id, $content, $filter) {
		$field = self::get_col($filter);

		wp_update_post(array(
			'ID' => $id,
			$field => $content
		));

		return $content;
	}

	function check($id = NULL) {
		// will be checked again from self::wrap
		if ( !isset($id) )
			return true;

		$post = get_post($id);

		return
			( $post->post_type == 'post' and current_user_can('edit_post', $id) ) or
			( $post->post_type == 'page' and current_user_can('edit_page', $id) )
	}

	// Get wp_posts column
	private function get_col($filter) {
		return str_replace('the_', 'post_', $filter);
	}
}

// Handles the_excerpt field
class frontEd_excerpt extends frontEd_basic {
	function get($id) {
		$post = get_post($id);

		$excerpt = $post->post_excerpt;

		if ( empty($excerpt) )
			$excerpt = self::trim_excerpt($post->post_content);

		return $excerpt;
	}

	function save($id, $excerpt) {
		$default_excerpt = self::get($id);

		if ( $excerpt == $default_excerpt )
			return $excerpt;

		wp_update_post(array(
			'ID' => $id,
			$field => $content
		));

		if ( empty($excerpt) )
			return $default_excerpt;

		return $excerpt;
	}

	// Copy-paste from wp_trim_excerpt()
	private function trim_excerpt($text) {
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}

		return $text;
	}
}

// Handles the_tags field
class frontEd_tags extends frontEd_basic {
	function wrap($content, $before = 'Tags: ', $sep = ', ', $after = '') {
		// Reverse engineer args for WP < 2.8
		if ( version_compare($GLOBALS['wp_version'], '2.7.1', '<') ) {
			// Figure out $before arg
			$before = substr($content, 0, strpos($content, '<a'));

			// Figure out $after arg
			$tmp = explode('</a>', $content);
			$after = $tmp[count($tmp)-1];
		}

		// Get the actual tags
		$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content, current_filter()) . $after;
	}

	function get($id) {
		$tagsObj = get_the_tags($id);

		foreach ( $tagsObj as $tag )
			$tags[] = $tag->name;
			
		return implode(', ', $tags);
	}

	function save($id, $tags) {
		wp_set_post_tags($id, $tags);

		return get_the_term_list($id, 'post_tag', '', ', ');
	}
}


// Handles comment_text field
class frontEd_comment extends frontEd_field {
	function wrap($content) {
		global $comment;
		return parent::wrap($content, current_filter(), $comment->comment_ID);
	}

	function get($id) {
		$comment = get_comment($id);
		return $comment->comment_content;
	}

	function save($id, $content, $filter) {
		wp_update_comment(array(
			'comment_ID' => $id,
			'comment_content' => $content
		));

		return $content;
	}

	function check() {
		return current_user_can('moderate_comments');
	}
}


// Handles widget_text
class frontEd_widget extends frontEd_field {
	function get($id, $filter) {
		$id = str_replace('text-', '', $id);
		$field = str_replace('widget_', '', $filter);

		$widgets = get_option('widget_text');
		return $widgets[$id][$field];
	}

	function save($id, $content, $filter) {
		$id = str_replace('text-', '', $id);
		$field = str_replace('widget_', '', $filter);

		$widgets = get_option('widget_text');
		$widgets[$id][$field] = $content;
		update_option('widget_text', $widgets);

		return $content;
	}

	function check() {
		return current_user_can('edit_themes');
	}
}

class frontEd_meta extends frontEd_field {
	function wrap($content, $post_id, $key, $type) {
		if ( ! isset($post_id) )
			$post_id = $GLOBALS['post']->ID;

		$id = implode('#', array($post_id, $key, $type));

		return parent::wrap($content, current_filter(), $id);
	}

	function get($id) {
		$args = explode('#', $id);
		$post_id = $args[0];
		$key = $args[1];

		return get_post_meta($post_id, $key, true);
	}

	function save($id, $content, $filter) {
		$args = explode('#', $id);
		$post_id = $args[0];
		$key = $args[1];

		update_post_meta($post_id, $key, $content);

		return $content;
	}
}

function editable_post_meta($post_id, $key, $type = 'input') {
	$data = get_post_meta($post_id, $key, true);

	echo apply_filters('post_meta', $data, $post_id, $key, $type);
}

add_action('plugins_loaded', 'fee_register_defaults', 9);
function fee_register_defaults() {
	register_fronted_field('the_title', 'frontEd_basic', array(
		'type' => 'input',
		'title' => __('Post/page title', 'front-end-editor')
	));

	register_fronted_field('the_content', 'frontEd_basic', array(
		'type' => 'rich',
		'title' => __('Post/page content', 'front-end-editor')
	));

	register_fronted_field('the_excerpt', 'frontEd_excerpt', array(
		'type' => 'textarea',
		'title' => __('Post/page excerpt', 'front-end-editor')
	));

	register_fronted_field('the_tags', 'frontEd_tags', array(
		'argc' => 4,
		'title' => __('Post tags', 'front-end-editor')
	));

	register_fronted_field('post_meta', 'frontEd_meta', array(
		'argc' => 4,
		'title' => __('Post/page custom fields', 'front-end-editor')
	));

	register_fronted_field('comment_text', 'frontEd_comment', array(
		'type' => 'textarea',
		'title' => __('Comment text', 'front-end-editor')
	));

	register_fronted_field('widget_text', 'frontEd_widget', array(
		'type' => 'textarea',
		'title' => __('Text widget content', 'front-end-editor')
	));

	register_fronted_field('widget_title', 'frontEd_widget', array(
		'title' => __('Text widget title', 'front-end-editor')
	));

	// Safe hook for new editable fields to be registered
	do_action('front_ed_fields');
}

