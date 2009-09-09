<?php

// Handles the_title and the_content fields
class frontEd_basic extends frontEd_field
{
	protected $field;

	function setup()
	{
		$this->field = str_replace('the_', 'post_', $this->filter);
	}

	function wrap($content, $post_id = 0)
	{
		if ( ! $post_id )
			$post_id = get_the_ID();

		if ( ! $post_id )
			return $content;

		if ( ! self::check($post_id) )
			return $content;

		return parent::wrap($content, $post_id);
	}

	function get($post_id)
	{
		return get_post_field($this->field, $post_id);
	}

	function save($post_id, $content)
	{
		wp_update_post(array(
			'ID' => $post_id,
			$this->field => $content
		));

		return $content;
	}

	function check($post_id)
	{
		$type = get_post_field('post_type', $post_id);

		return current_user_can("edit_$type", $post_id);
	}
}

// Handles <p> in the_content
class frontEd_chunks extends frontEd_basic
{
	const delim = "\n\n";

	function wrap($content)
	{
		$post_id = get_the_ID();

		if ( ! self::check($post_id) )
			return $content;

		$chunks = self::split($content);

		foreach ( $chunks as $i => $chunk )
			$chunks[$i] = '<p>' . frontEd_field::wrap($chunk, "$post_id#$i") . '</p>';

		return implode('', $chunks);
	}

	function get($post_id)
	{
		list($post_id, $chunk_id) = explode('#', $post_id);

		$field = get_post_field('post_content', $post_id);

		$chunks = self::split($field, true);

		return $chunks[$chunk_id];
	}

	function save($post_id, $content)
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
			'post_excerpt' => $excerpt
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
		
		if ( ! self::check($comment->comment_ID) )
			return $content;

		return parent::wrap($content, $comment->comment_ID);
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

	function check($comment_id)
	{
		if ( current_user_can('moderate_comments') )
			return true;

		global $user_ID;

		$comment = get_comment($comment_id);

		return $user_ID == $comment->user_id;
	}
}

// Handles widget_text
class frontEd_widget extends frontEd_field
{
	function wrap($content)
	{
		return parent::wrap($content, 0);
	}

	function setup()
	{
		$this->field = str_replace('widget_', '', $this->filter);
	}

	function get($id)
	{
		$widget_id = self::get_id($id);

		$widgets = get_option('widget_text');

		return $widgets[$widget_id][$this->field];
	}

	function save($id, $content)
	{
		$widget_id = self::get_id($id);

		$widgets = get_option('widget_text');
		$widgets[$widget_id][$this->field] = $content;

		update_option('widget_text', $widgets);

		return $content;
	}

	function check()
	{
		return current_user_can('edit_themes');
	}

	protected function get_id($id)
	{
		return str_replace('text-', '', $id);
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

		return $before . parent::wrap($content) . $after;
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

		return $before . parent::wrap($content, $post_id) . $after;
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
		$this->type = $type;

		$id = implode('#', array($post_id, $key, $type));

		return parent::wrap($content, $id);
	}

	function get($id)
	{
		list($post_id, $key) = explode('#', $id);

		return get_post_meta($post_id, $key, true);
	}

	function save($id, $content)
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

		if ( ! self::check($author_id) )
			return $content;

		if ( empty($content) )
			$content = self::placeholder();

		return parent::wrap($content, $author_id);
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

	function check($author_id)
	{
		if ( current_user_can('edit_users') )
			return true;

		global $user_ID;

		return $user_ID == $author_id;
	}

	// WP < 2.9
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
			'title' => __('Post/page title', 'front-end-editor'),
			'class' => 'frontEd_basic',
			'type' => 'input',
		),
		
		'the_content' => array(
			'title' => __('Post/page content', 'front-end-editor'),
			'class' => frontEditor::$options->chunks ? 'frontEd_chunks' : 'frontEd_basic',
			'type' => frontEditor::$options->rich ? 'rich' : 'textarea',
		),

		'the_excerpt' => array(
			'title' => __('Post/page excerpt', 'front-end-editor'),
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
			'title' => __('Post/page custom fields', 'front-end-editor'),
			'class' => 'frontEd_meta',
			'argc' => 4,
		),

		'comment_text' => array(
			'title' => __('Comment text', 'front-end-editor'),
			'class' => 'frontEd_comment',
			'type' => 'textarea',
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
	);

	foreach ( $fields as $filter => $args )
		register_fronted_field($filter, $args);

	// Safe hook for new editable fields to be registered
	do_action('front_ed_fields');
}

