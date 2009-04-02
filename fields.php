<?php
class frontEd_field {
	function wrap($content, $filter = '') {
		global $post, $frontEditor;

		if ( empty($filter) )
			$filter = current_filter();

		if ( ! $frontEditor->check_perm($post->ID) )
			return $content;

		$class = 'front-ed-' . $frontEditor->get_field($filter) . ' front-ed';

		return "<span rel='{$post->ID}' class='{$class}'>{$content}</span>";
	}

	function get($id, $name) {
		global $wpdb;

		$field = 'post_' . $name;

		$post = (array) get_post($id);

		echo $post[$field];
	}

	function save($id, $content, $name) {
		global $wpdb;

		$field = 'post_' . $name;

		return $wpdb->update($wpdb->posts, array($field => $content), array('ID' => $id));
	}
}

class frontEd_tags extends frontEd_field {
	function wrap($content, $before = 'Tags: ', $sep = ', ', $after = '') {
/*
		// Figure out $before arg
		$before = substr($content, 0, strpos($content, '<a'));

		// Figure out $after arg
		$tmp = explode('</a>', $content);
		$after = $tmp[count($tmp)-1];
*/

		// Get the actual tags
		$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content, current_filter()) . $after;
	}

	function get($id, $name) {
		$tagsObj = get_the_tags($id);

		foreach ( $tagsObj as $tag )
			$tags[] = $tag->name;
			
		echo implode(', ', $tags);
	}

	function save($id, $content, $name) {

	}
}

