<?php

// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field {

	protected $field;

	protected function setup() {
		$this->field = str_replace('the_', 'post_', $this->get_filter());
	}

	static function get_object_type() {
		return 'post';
	}

	function wrap($content, $post_id = 0) {
		if ( ! $post_id = $this->_get_id($post_id) )
			return $content;

		return parent::wrap($content, $post_id);
	}

	protected function _get_id($post_id) {
		if ( ! in_the_loop() )
			return false;

		if ( ! $post_id )
			$post_id = get_the_ID();

		if ( ! $post_id || ! $this->check($post_id) )
			return false;

		return $post_id;
	}

	function get($post_id) {
		return get_post_field($this->field, $post_id);
	}

	function save($post_id, $content) {
		$fields = array(
			'ID' => $post_id,
			$this->field => $content
		);

		// check slug
		if ( $this->field == 'post_title' ) {
			$current_slug = get_post_field('post_name', $post_id);
			$current_title = get_post_field('post_title', $post_id);

			// update only if not explicitly set
			if ( empty($current_slug) || $current_slug == sanitize_title_with_dashes($current_title) ) {
				$new_slug = sanitize_title_with_dashes($content);
				$fields['post_name'] = $new_slug;
			}
		}

		wp_update_post($fields);

		return $content;
	}

	function check($post_id = 0) {
		$type = get_post_field('post_type', $post_id);

		return current_user_can("edit_$type", $post_id);
	}
}

// Handles <p> in the_content
class frontEd_chunks extends frontEd_basic {

	const delim = "\n\n";

	function wrap($content, $post_id = 0) {
		if ( ! $post_id = $this->_get_id($post_id) )
			return $content;

		$chunks = $this->split($content);

		foreach ( $chunks as $i => $chunk )
			$content = str_replace($chunk, frontEd_field::wrap($chunk, "$post_id#$i", true), $content);

		return $content;
	}

	function get($post_id) {
		list($post_id, $chunk_id) = explode('#', $post_id);

		$field = get_post_field('post_content', $post_id);

		$chunks = $this->split($field, true);

		return $chunks[$chunk_id];
	}

	function save($post_id, $chunk_content) {
		list($post_id, $chunk_id) = explode('#', $post_id);

		$content = get_post_field('post_content', $post_id);

		$chunks = $this->split($content, true);

		$chunk_content = trim($chunk_content);

		$content = str_replace($chunks[$chunk_id], $chunk_content, $content);

		wp_update_post(array(
			'ID' => $post_id,
			'post_content' => $content
		));

		// Refresh the page if a new chunk is added
		if ( empty($chunk_content) || FALSE !== strpos($chunk_content, self::delim) )
			$this->force_refresh();

		return $chunk_content;
	}

	// Split content into chunks
	protected function split($content, $autop = false) {
		if ( $autop )
			$content = wpautop($content);

		preg_match_all("#<p>(.*?)</p>#", $content, $matches);

		return $matches[1];
	}

	protected function force_refresh() {
		die("<script language='javascript'>location.reload(true)</script>");
	}
}

// Handles the_excerpt field
class frontEd_excerpt extends frontEd_basic {

	function get($post_id) {
		$post = get_post($post_id);

		$excerpt = $post->post_excerpt;

		if ( empty($excerpt) )
			$excerpt = $this->trim_excerpt($post->post_content);

		return $excerpt;
	}

	function save($post_id, $excerpt) {
		$default_excerpt = $this->get($post_id);

		if ( $excerpt == $default_excerpt )
			return $excerpt;

		wp_update_post(array(
			'ID' => $post_id,
			'post_excerpt' => $excerpt
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

		return apply_filters('get_the_excerpt', $text);
	}
}

// Handles the_tags field
class frontEd_tags extends frontEd_basic {

	function wrap($content, $before, $sep, $after) {
		if ( empty($content) )
			$content = $this->placeholder();
		else
			$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content) . $after;
	}

	function get($post_id) {
		$tags = get_the_tags($post_id);

		if ( empty($tags) )
			return;

		foreach ( $tags as &$tag )
			$tag = $tag->name;

		return implode(', ', $tags);
	}

	function save($post_id, $tags) {
		wp_set_post_tags($post_id, $tags);

		$response = get_the_term_list($post_id, 'post_tag', '', ', ');

		if ( empty($response) )
			return $this->placeholder();

		return $response;
	}
}

// Handles the_terms field
class frontEd_terms extends frontEd_basic {

	function wrap($content, $taxonomy, $before, $sep, $after) {
		$post_id = implode('#', array(get_the_ID(), $taxonomy));

		if ( empty($content) )
			$content = $this->placeholder();
		else
			$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content, $post_id) . $after;
	}

	function get($id) {
		list($post_id, $taxonomy) = explode('#', $id);

		return get_terms_to_edit($post_id, $taxonomy);
	}

	function save($id, $terms) {
		list($post_id, $taxonomy) = explode('#', $id);

		wp_set_post_terms($post_id, $terms, $taxonomy);

		$response = get_the_term_list($post_id, $taxonomy, '', ', ');

		if ( empty($response) )
			return $this->placeholder();

		return $response;
	}
}

// Handles post_meta field
class frontEd_meta extends frontEd_basic {

	function wrap($data, $post_id, $key, $type) {
		$this->input_type = $type;

		$r = array();
		foreach ( $data as $i => $val ) {
			$id = implode('#', array($post_id, $key, $i));
			$r[$i] = parent::wrap($content, $id);
		}

		return $r;
	}

	function get($id) {
		list($post_id, $key, $i) = explode('#', $id);

		$data = get_post_meta($post_id, $key);

		return $data[$i];
	}

	function save($id, $content) {
		$old_value = $this->get($id);

		update_post_meta($post_id, $key, $content, $old_value);

		return $content;
	}
}

function editable_post_meta($post_id, $key, $type = 'input', $echo = true) {
	$data = get_editable_post_meta($post_id, $key, $type, true);

	if ( ! $echo )
		return $data;

	echo $data;
}

function get_editable_post_meta($post_id, $key, $type = 'input', $single = false) {
	$data = get_post_meta($post_id, $key, $single);
	$data = apply_filters('post_meta', (array) $data, $post_id, $key, $type);

	if ( $single )
		return $data[0];

	return $data;
}

