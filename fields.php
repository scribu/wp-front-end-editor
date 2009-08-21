<?php

// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field
{
	function wrap($content, $filter = '', $post_id = 0)
	{
		if ( ! $post_id )
			$post_id = get_the_ID();

		if ( ! $post_id )
			return $content;

		if ( ! self::check($post_id) )
			return $content;

		return parent::wrap($content, $filter, $post_id);
	}

	function get($post_id, $filter)
	{
		$field = self::get_col($filter);

		return get_post_field($field, $post_id);
	}

	function save($post_id, $content, $filter)
	{
		$field = self::get_col($filter);

		wp_update_post(array(
			'ID' => $post_id,
			$field => $content
		));

		return $content;
	}

	function check($post_id = 0)
	{
		if ( ! $post_id )
			return current_user_can('edit_posts') or current_user_can('edit_pages');

		$type = get_post_field('post_type', $post_id);

		return current_user_can("edit_$type", $post_id);
	}

	// Get wp_posts column
	protected function get_col($filter)
	{
		return str_replace('the_', 'post_', $filter);
	}
}

// Handles <p> in the_content
class frontEd_chunks extends frontEd_basic
{
	const delim = "\n\n";

	function wrap($content, $filter = '')
	{
		$post_id = get_the_ID();

		if ( ! self::post_check($post_id) )
			return $content;

		$chunks = self::split($content);

		foreach ( $chunks as $i => $chunk )
			$chunks[$i] = '<p>' . frontEd_field::wrap($chunk, '', "$post_id#$i") . '</p>';

		return implode('', $chunks);
	}

	function get($post_id, $filter)
	{
		list($post_id, $chunk_id) = explode('#', $post_id);

		$field = get_post_field('post_content', $post_id);

		$chunks = self::split($field, true);

		return $chunks[$chunk_id];
	}

	function save($post_id, $content, $filter)
	{
		list($post_id, $chunk_id) = explode('#', $post_id);

		$field = get_post_field('post_content', $post_id);

		$chunks = self::split($field, true);

		$content = trim($content);

		if ( empty($content) )
			unset($chunks[$chunk_id]);
		else
			$chunks[$chunk_id] = $content;

		$new_content = implode(self::delim, $chunks);

		wp_update_post(array(
			'ID' => $post_id,
			'post_content' => $new_content
		));

		// Refresh the page if a new chunk is added
		if ( empty($content) || FALSE !== strpos($content, self::delim) )
			self::force_refresh();

		return $content;
	}

	// Split content into chunks
	protected function split($content, $autop = false)
	{
		if ( $autop )
			$content = wpautop($content);

		$chunks = explode('<p>', $content);

		$new_content = array();
		foreach ( $chunks as $chunk )
		{
			$chunk = trim(str_replace('</p>', '', $chunk));

			if ( !empty($chunk) )
				$new_content[] = $chunk . "\n";
		}

		return $new_content;
	}

	protected function force_refresh()
	{
		die("<script language='javascript'>location.reload(true)</script>");	
	}
}

// Handles the_excerpt field
class frontEd_excerpt extends frontEd_basic
{
	function get($post_id)
	{
		$post = get_post($post_id);

		$excerpt = $post->post_excerpt;

		if ( empty($excerpt) )
			$excerpt = self::trim_excerpt($post->post_content);

		return $excerpt;
	}

	function save($post_id, $excerpt)
	{
		$default_excerpt = self::get($post_id);

		if ( $excerpt == $default_excerpt )
			return $excerpt;

		wp_update_post(array(
			'ID' => $post_id,
			$field => $content
		));

		if ( empty($excerpt) )
			return $default_excerpt;

		return $excerpt;
	}

	// Copy-paste from wp_trim_excerpt()
	private function trim_excerpt($text)
	{
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length)
		{
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}

		return apply_filters('get_the_excerpt', $text);
	}
}

// Handles comment_text field
class frontEd_comment extends frontEd_field
{
	function wrap($content)
	{
		global $comment;
		return parent::wrap($content, '', $comment->comment_ID);
	}

	function get($comment_id)
	{
		$comment = get_comment($comment_id);
		return $comment->comment_content;
	}

	function save($comment_id, $content)
	{
		wp_update_comment(array(
			'comment_ID' => $comment_id,
			'comment_content' => $content
		));

		return $content;
	}

	function check($comment_id = 0)
	{
		global $user_ID;

		$comment = get_comment($comment_id);

debug($user_ID, $comment->user_id);
		return current_user_can('moderate_comments') || $user_ID == $comment->user_id;
	}
}

// Handles widget_text
class frontEd_widget extends frontEd_field
{
	function get($id, $filter)
	{
		$widget_id = self::get_id($id);
		$field = self::get_col($filter);

		$widgets = get_option('widget_text');

		return $widgets[$widget_id][$field];
	}

	function save($id, $content, $filter)
	{
		$widget_id = self::get_id($id);
		$field = self::get_col($filter);

		$widgets = get_option('widget_text');
		$widgets[$widget_id][$field] = $content;

		update_option('widget_text', $widgets);

		return $content;
	}

	protected function get_id($id)
	{
		return str_replace('text-', '', $id);
	}

	protected function get_col($filter)
	{
		return str_replace('widget_', '', $filter);
	}

	function check()
	{
		return current_user_can('edit_themes');
	}
}

// Handles the_tags field
class frontEd_tags extends frontEd_basic
{
	function wrap($content, $before, $sep, $after)
	{
		if ( version_compare($GLOBALS['wp_version'], '2.7.1', '<') )
		{
			// Figure out $before arg
			$before = substr($content, 0, strpos($content, '<a'));

			// Figure out $after arg
			$tmp = explode('</a>', $content);
			$after = $tmp[count($tmp)-1];
		}

		if ( empty($content) )
			$content = self::placeholder();
		else
			$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content, '') . $after;
	}

	function get($post_id)
	{
		$tags = get_the_tags($post_id);

		if ( empty($tags) )
			return;

		foreach ( $tags as &$tag )
			$tag = $tag->name;

		return implode(', ', $tags);
	}

	function save($post_id, $tags)
	{
		wp_set_post_tags($post_id, $tags);

		$response = get_the_term_list($post_id, 'post_tag', '', ', ');

		if ( empty($response) )
			return self::placeholder();

		return $response;
	}
}

// Handles the_terms field
class frontEd_terms extends frontEd_basic
{
	function wrap($content, $taxonomy, $before, $sep, $after)
	{
		$post_id = implode('#', array(get_the_ID(), $taxonomy));

		if ( empty($content) )
			$content = self::placeholder();
		else
			$content = str_replace(array($before, $after), '', $content);

		return $before . parent::wrap($content, '', $post_id) . $after;
	}

	function get($id)
	{
		list($post_id, $taxonomy) = explode('#', $id);

		return get_terms_to_edit($post_id, $taxonomy);
	}

	function save($id, $terms)
	{
		list($post_id, $taxonomy) = explode('#', $id);

		wp_set_post_terms($post_id, $terms, $taxonomy);

		$response = get_the_term_list($post_id, $taxonomy, '', ', ');

		if ( empty($response) )
			return self::placeholder();

		return $response;
	}
}

// Handles post_meta field
class frontEd_meta extends frontEd_basic
{
	function wrap($content, $post_id, $key, $type)
	{
		$id = implode('#', array($post_id, $key, $type));

		return parent::wrap($content, '', $id);
	}

	function get($id)
	{
		list($post_id, $key) = explode('#', $id);

		return get_post_meta($post_id, $key, true);
	}

	function save($id, $content, $filter)
	{
		list($post_id, $key) = explode('#', $id);

		update_post_meta($post_id, $key, $content);

		return $content;
	}
}

function editable_post_meta($post_id, $key, $type = 'input')
{
	$data = get_post_meta($post_id, $key, true);

	echo apply_filters('post_meta', $data, $post_id, $key, $type);
}

// Handles the_author_description field
class frontEd_author_desc extends frontEd_field
{
	function wrap($content, $author_id = '')
	{
		if ( ! $author_id )
			$author_id = self::guess_author_id();

		if ( ! $author_id )
			return $content;

		if ( ! self::author_check($author_id) )
			return $content;

		if ( empty($content) )
			$content = self::placeholder();

		parent::wrap($content, '', $author_id);
	}

	// Retrieve the current data for the field
	function get($author_id)
	{
		return get_usermeta($author_id, 'description');
	}

	function save($author_id, $content)
	{
		update_usermeta($author_id, 'description', $content);

		return $content;
	}

	function check($author_id = 0)
	{
		if ( ! $author_id )
			return true;

		global $user_ID;

		return current_user_can('edit_users') || $user_ID == $author_id;
	}

	// WP < 2.9 ?
	function guess_author_id()
	{
		if ( function_exists('get_the_author_meta') )
			return get_the_author_meta('id');

		global $authordata;

		return $authordata->ID;
	}
}


add_action('plugins_loaded', 'fee_register_defaults');
function fee_register_defaults()
{
	$fields = array(
		'the_title' => array(
			'class' => 'frontEd_basic',
			'type' => 'input',
			'title' => __('Post/page title', 'front-end-editor')
		),
		
		'the_content' => array(
			'class' => frontEditor::$options->chunks ? 'frontEd_chunks' : 'frontEd_basic',
			'type' => frontEditor::$options->rich ? 'rich' : 'textarea',
			'title' => __('Post/page content', 'front-end-editor')
		),

		'the_excerpt' => array(
			'class' => 'frontEd_excerpt',
			'type' => 'textarea',
			'title' => __('Post/page excerpt', 'front-end-editor')
		),

		'the_tags' => array(
			'class' => 'frontEd_tags',
			'argc' => 4,
			'title' => __('Post tags', 'front-end-editor')
		),

		'the_terms' => array(
			'class' => 'frontEd_terms',
			'argc' => 5,
			'title' => __('Post terms', 'front-end-editor')
		),

		'post_meta' => array(
			'class' => 'frontEd_meta',
			'argc' => 4,
			'title' => __('Post/page custom fields', 'front-end-editor')
		),

		'comment_text' => array(
			'class' => 'frontEd_comment',
			'type' => 'textarea',
			'title' => __('Comment text', 'front-end-editor')
		),

		'the_author_description' => array(
			'class' => 'frontEd_author_desc',
			'type' => 'textarea',
			'argc' => 2,
			'title' => __('Author description', 'front-end-editor')
		),

		'widget_text' => array(
			'class' => 'frontEd_widget',
			'type' => 'textarea',
			'title' => __('Text widget content', 'front-end-editor')
		),

		'widget_title' => array(
			'class' => 'frontEd_widget',
			'title' => __('Text widget title', 'front-end-editor')
		),
	);

	foreach ( $fields as $filter => $args )
		register_fronted_field($filter, $args);

	// Safe hook for new editable fields to be registered
	do_action('front_ed_fields');
}

