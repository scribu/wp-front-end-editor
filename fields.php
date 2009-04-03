<?php
// All field classes should extend from this one
class frontEd_field {

	// Mark the field as editable
	function wrap($content, $filter = '', $id = NULL) {
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

	function check($id) {
		return current_user_can('edit_post', $id) || current_user_can('edit_page', $id);
	}
}

// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field {
	function wrap($content) {
		global $post;

		if ( ! frontEd_basic::check($post->ID) )
			return $content;

		return frontEd_field::wrap($content, current_filter(), $post->ID);
	}

	function get($id, $filter) {
		global $wpdb;

		$field = frontEd_basic::_get_col($filter);

		$post = (array) get_post($id);

		echo $post[$field];
	}

	function save($id, $content, $filter) {
		global $wpdb;

		$field = frontEd_basic::_get_col($filter);

		$wpdb->update($wpdb->posts, array($field => $content), array('ID' => $id));

		echo apply_filters($filter, $content);
	}

	// Get wp_posts column
	function _get_col($filter) {
		return str_replace('the_', 'post_', $filter);
	}
}

// Handles widget_text
class frontEd_widget extends frontEd_field {
	function wrap($content) {
		global $post;

		if ( ! frontEd_widget::check($post->ID) )
			return $content;

		return frontEd_field::wrap($content, current_filter(), $post->ID);
	}

	function get($id, $filter) {
		$id = str_replace('text-', '', $id);
		$field = str_replace('widget_', '', $filter);

		$widgets = get_option('widget_text');
		echo $widgets[$id][$field];
	}

	function save($id, $content, $filter) {
		$id = str_replace('text-', '', $id);
		$field = str_replace('widget_', '', $filter);

		$widgets = get_option('widget_text');
		$widgets[$id][$field] = $content;
		update_option('widget_text', $widgets);

		echo apply_filters($filter, $content);
	}

	function check() {
		return current_user_can('edit_themes');
	}
}

// Handles the_tags field
class frontEd_tags extends frontEd_field {
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
			
		echo implode(', ', $tags);
	}

	function save($id, $tags) {
		wp_set_post_tags($id, $tags);

		echo get_the_term_list($id, 'post_tag', '', ', ');
	}
}

