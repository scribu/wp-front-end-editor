<?php

// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field {
	protected $field;

	protected function setup() {
		$this->field = str_replace('the_', 'post_', $this->get_filter());
	}

	protected function get_object_type() {
		return 'post';
	}

	function wrap($content, $post_id = 0) {
		if ( ! $post_id ) {
			if ( ! in_the_loop() )
				return $content;

			$post_id = get_the_ID();
		}

		if ( ! $this->check($post_id) )
			return $content;

		return parent::wrap($content, $post_id);
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

	function wrap($content) {
		$post_id = get_the_ID();

		if ( ! $this->check($post_id) )
			return $content;

		$chunks = $this->split($content);

		foreach ( $chunks as $i => $chunk )
			$content = str_replace($chunk, frontEd_field::wrap($chunk, "$post_id#$i"), $content);

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
	function wrap($content, $post_id, $key, $type) {
		$this->input_type = $type;

		$id = implode('#', array($post_id, $key, $type));

		return parent::wrap($content, $id);
	}

	function get($id) {
		list($post_id, $key) = explode('#', $id);

		return get_post_meta($post_id, $key, true);
	}

	function save($id, $content) {
		list($post_id, $key) = explode('#', $id);

		update_post_meta($post_id, $key, $content);

		return $content;
	}
}

function editable_post_meta($post_id, $key, $type = 'input', $echo = true) {
	$data = get_post_meta($post_id, $key, true);
	$data = apply_filters('post_meta', $data, $post_id, $key, $type);

	if ( ! $echo )
		return $data;

	echo $data;
}

// Handles comment_text field
class frontEd_comment extends frontEd_field {
	protected function get_object_type() {
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
	protected function get_object_type() {
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
	protected function get_object_type() {
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

	protected function get_object_type() {
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

		return wpautop($content);
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
	private static $wraps = array();

	protected function get_object_type() {
		return 'option';
	}

	function wrap($content, $show) {
		if ( ! $this->check() )
			return $content;

		if ( $content == get_option('blogname') )
			$show = 'name';

		// Limit to only name and description
		if ( $show != 'description' && $show != 'name' )
			return $content;

		$result = parent::wrap($content, $show);

		self::$wraps[$show] = $result;

		return $result;
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

add_action('init', 'fee_register_defaults');
function fee_register_defaults() {
	$fields = array(
		'the_title' => array(
			'title' => __('Post title', 'front-end-editor'),
			'class' => 'frontEd_basic',
			'type' => 'input',
		),

		'the_content' => array(
			'title' => __('Post content', 'front-end-editor'),
			'class' => frontEditor::$options->chunks ? 'frontEd_chunks' : 'frontEd_basic',
			'type' => frontEditor::$options->rich ? 'rich' : 'textarea',
		),

		'the_excerpt' => array(
			'title' => __('Post excerpt', 'front-end-editor'),
			'class' => 'frontEd_excerpt',
			'type' => 'textarea',
		),

		'the_tags' => array(
			'title' => __('Post tags', 'front-end-editor'),
			'class' => 'frontEd_tags',
			'argc' => 4,
		),

		'the_terms' => array(
			'title' => __('Post terms', 'front-end-editor'),
			'class' => 'frontEd_terms',
			'argc' => 5,
		),

		'post_meta' => array(
			'title' => __('Post custom fields', 'front-end-editor'),
			'class' => 'frontEd_meta',
			'argc' => 4,
		),

		'comment_text' => array(
			'title' => __('Comment text', 'front-end-editor'),
			'class' => 'frontEd_comment',
			'type' => 'textarea',
		),

		'single_cat_title' => array(
			'title' => __('Category title', 'front-end-editor'),
			'class' => 'frontEd_single_title',
		),

		'single_tag_title' => array(
			'title' => __('Tag title', 'front-end-editor'),
			'class' => 'frontEd_single_title',
		),

		'the_author_description' => array(
			'title' => __('Author description', 'front-end-editor'),
			'class' => 'frontEd_author_desc',
			'type' => 'textarea',
			'argc' => 2,
		),

		'widget_text' => array(
			'title' => __('Text widget content', 'front-end-editor'),
			'class' => 'frontEd_widget',
			'type' => 'textarea',
		),

		'widget_title' => array(
			'title' => __('Text widget title', 'front-end-editor'),
			'class' => 'frontEd_widget',
		),
		
		'bloginfo' => array(
			'title' => __('Site title and description', 'front-end-editor'),
			'class' => 'frontEd_bloginfo',
			'argc' => 2,
		),
	);

	foreach ( $fields as $filter => $args )
		register_fronted_field($filter, $args);

	// Safe hook for new editable fields to be registered
	do_action('front_ed_fields');
}

