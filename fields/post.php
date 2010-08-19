<?php

// Handles the_title and the_content fields
class FEE_Field_Post extends FEE_Field_Base {

	protected $field;

	protected function setup() {
		$this->field = str_replace( 'the_', 'post_', $this->get_filter() );
	}

	static function get_object_type() {
		return 'post';
	}

	function wrap( $content, $post_id = 0 ) {
		if ( !$post_id = $this->_get_id( $post_id ) )
			return $content;

		return parent::wrap( $content, compact( 'post_id' ) );
	}

	protected function _get_id( $post_id = 0, $in_loop = true ) {
		global $post;
	
		if ( $in_loop && !in_the_loop() )
			return false;

		if ( !$post_id )
			$post_id = $post->ID;

		if ( !$post_id || !$this->check( $post_id ) )
			return false;

		return $post_id;
	}

	function get( $data ) {
		extract( $data );
	
		return get_post_field( $this->field, $post_id );
	}

	function save( $data, $content ) {
		extract( $data );

		$postdata = array(
			'ID' => $post_id,
			$this->field => $content
		);

		// reset slug
		if ( $this->field == 'post_title' ) {
			$current_slug = get_post_field( 'post_name', $post_id );
			$current_title = get_post_field( 'post_title', $post_id );

			// update only if not explicitly set
			if ( empty( $current_slug ) || $current_slug == sanitize_title_with_dashes( $current_title ) ) {
				$new_slug = sanitize_title_with_dashes( $content );
				$postdata['post_name'] = $new_slug;
			}
		}

		wp_update_post( (object) $postdata );

		$this->set_post_global( $post_id );

		return $content;
	}

	function check( $post_id = 0 ) {
		if ( is_array( $post_id ) )
			extract( $post_id );

		return current_user_can( 'edit_post', $post_id );
	}

	protected function set_post_global( $post_id ) {
		$GLOBALS['post'] = get_post( $post_id );
	}
}

// Handles <p> tags in the_content
class FEE_Field_Chunks extends FEE_Field_Post {

	const delim = "\n\n";

	function wrap( $content, $post_id = 0 ) {
		if ( !$post_id = $this->_get_id( $post_id ) )
			return $content;

		$chunks = $this->split( $content );

		$replacements = array();
		foreach ( $chunks as $i => $chunk )
			$replacements[] = FEE_Field_Base::wrap( $chunk, compact( 'post_id', 'i' ), true );

		return $this->replace_exact( $chunks, $replacements, $content );
	}

	function get( $data ) {
		extract( $data );

		$field = get_post_field( 'post_content', $post_id );

		$chunks = $this->split( $field, true );

		return @$chunks[$chunk_id];
	}

	function save( $data, $chunk_content ) {
		extract( $data );

		$chunk_content = trim( $chunk_content );

		$content = get_post_field( 'post_content', $post_id );

		$chunks = $this->split( $content, true );
		$replacement = $chunks;
		$replacement[$chunk_id] = $chunk_content;

		$content = $this->replace_exact( $chunks, $replacement, $content );

		$postdata = array(
			'ID' => $post_id,
			'post_content' => $content
		);

		wp_update_post( (object) $postdata );

		$this->set_post_global( $post_id );

		// Refresh the page if a new chunk is added
		if ( empty( $chunk_content ) || FALSE !== strpos( $chunk_content, self::delim ) )
			$this->force_refresh();

		die( $chunk_content );
	}

	protected function split( $content, $autop = false ) {
		if ( $autop )
			$content = wpautop( $content );

debug(esc_html($content));

		preg_match_all( "#<p.*?>(.*?)</p>#", $content, $matches );

		return $matches[1];
	}

	protected function replace_exact( $old, $new, $subject ) {
		$tmp = array();

		$index = array_keys( $old );

		foreach ( $index as $i )
			$tmp[] = '__' . md5( $i ) . '__';

		foreach ( $index as $i )
			$subject = $this->replace_first( $old[$i], $tmp[$i], $subject );

		foreach ( $index as $i )
			$subject = $this->replace_first( $tmp[$i], $new[$i], $subject );

		return $subject;
	}

	protected function replace_first( $old, $new, $subject ) {
		return implode( $new, explode( $old, $subject, 2 ) );
	}

	protected function force_refresh() {
		die( "<script language='javascript'>location.reload( true )</script>" );
	}
}

// Handles the_excerpt field
class FEE_Field_Excerpt extends FEE_Field_Post {

	function get( $data ) {
		extract( $data );

		$post = get_post( $post_id );

		$excerpt = $post->post_excerpt;

		if ( empty( $excerpt ) ) {
			$this->set_post_global( $post_id );
			$excerpt = $this->trim_excerpt( $post->post_content );
		}

		return $excerpt;
	}

	function save( $data, $excerpt ) {
		extract( $data );

		$default_excerpt = $this->get( $data );

		if ( $excerpt == $default_excerpt )
			return $excerpt;

		$postdata = array(
			'ID' => $post_id,
			'post_excerpt' => $excerpt
		);

		wp_update_post( (object) $postdata );

		$this->set_post_global( $post_id );

		if ( empty( $excerpt ) )
			return $default_excerpt;

		return $excerpt;
	}

	// Copy-paste from wp_trim_excerpt()
	private function trim_excerpt( $text ) {
		$text = apply_filters( 'the_content', $text );
		$text = str_replace( ']]>', ']]&gt;', $text );
		$text = strip_tags( $text );
		$excerpt_length = apply_filters( 'excerpt_length', 55 );
		$words = explode( ' ', $text, $excerpt_length + 1 );
		if ( count( $words ) > $excerpt_length ) {
			array_pop( $words );
			array_push( $words, '[...]' );
			$text = implode( ' ', $words );
		}

		return apply_filters( 'get_the_excerpt', $text );
	}
}

// Handles the_terms field
class FEE_Field_Terms extends FEE_Field_Post {

	function wrap( $content, $taxonomy, $before, $sep, $after ) {
		global $post;
	
		if ( !in_the_loop() )
			return $content;

		$post_id = $post->ID;

		if ( !$post_id )
			return $content;

		$data = compact( 'post_id', 'taxonomy' );

		if ( !$this->check( $data ) )
			return $content;

		$content = $this->placehold( str_replace( array( $before, $after ), '', $content ) );

		return $before . FEE_Field_Base::wrap( $content, $data ) . $after;
	}

	function get( $data ) {
		extract( $data );

		$tags = get_terms_to_edit( $post_id, $taxonomy );
		$tags = str_replace( ',', ', ', $tags );

		return $tags;
	}

	function save( $data, $terms ) {
		extract( $data );

		wp_set_post_terms( $post_id, $terms, $taxonomy );

		$response = get_the_term_list( $post_id, $taxonomy, '', ', ' );	// todo: store $sep somehow

		return $this->placehold( $response );
	}

	function check( $data = 0 ) {
		extract( $data );

		return current_user_can( 'edit_post', $post_id ) && current_user_can( get_taxonomy( $taxonomy )->cap->assign_terms );
	}
}

// Handles the_tags field
class FEE_Field_Tags extends FEE_Field_Terms {

	function wrap( $content, $before, $sep, $after ) {
		return parent::wrap( $content, 'post_tag', $before, $sep, $after );
	}
}

// Handles the_category field
class FEE_Field_Category extends FEE_Field_Terms {

	function wrap( $content, $sep, $parents ) {
		return parent::wrap( $content, 'category', '', $sep, '' );
	}

	function save( $data, $categories ) {
		extract( $data );

		$cat_ids = array();
		foreach ( explode( ',', $categories ) as $cat_name ) {
			if ( !$cat = get_cat_ID( trim( $cat_name ) ) ) {
				$args = wp_insert_term( $cat_name, $taxonomy );

				if ( is_wp_error( $args ) )
					continue;

				$cat = $args['term_id'];
			}

			$cat_ids[] = $cat;
		}

		wp_set_post_categories( $post_id, $cat_ids );

		$response = get_the_term_list( $post_id, $taxonomy, '', ', ' );

		return $this->placehold( $response );
	}
}

// Handles the post thumbnail
class FEE_Field_Thumbnail extends FEE_Field_Post {

	function wrap( $html, $post_id, $post_thumbnail_id, $size ) {
		if ( !$post_id = $this->_get_id( $post_id, false ) )
			return $content;

		return FEE_Field_Base::wrap( $html, compact( 'post_id', 'size' ) );
	}

	function get( $data ) {
		extract( $data );

		return get_post_thumbnail_id( $post_id );
	}

	function save( $data, $thumbnail_id ) {
		extract( $data );

		if ( -1 == $thumbnail_id ) {
			delete_post_meta( $post_id, '_thumbnail_id' );
			return -1;
		}

		update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );

		list( $url ) = image_downsize( $thumbnail_id, $size );

		return $url;
	}
}

// Handles post_meta field
class FEE_Field_Meta extends FEE_Field_Post {

	function wrap( $data, $post_id, $key, $type, $single ) {
		extract( self::convert_type( $type ) );

		if ( $this->check( $post_id ) ) {
			if ( $single ) {
				if ( 'checkbox' == $type ) {
					$value = (bool) get_post_meta( $post_id, $key, true );
					$data = $values[ $value ];
				}
				else {
					$data = $this->placehold( $data );
				}
				$data = array( $data );
			}

			$r = array();
			foreach ( $data as $i => $val ) {
				$r[$i] = FEE_Field_Base::wrap( $val, compact( 'post_id', 'key', 'type', 'values', 'i' ) );
			}
		}
		else {
			$r = (array) $data;
		}

		if ( $single )
			return $r[0];

		return $r;
	}

	function get( $data ) {
		extract( $data );

		$data = get_post_meta( $post_id, $key );

		return @$data[$i];
	}

	function save( $data, $new_value ) {
		extract( $data );

		$data = get_post_meta( $post_id, $key );

		$old_value = @$data[$i];

		if ( 'checkbox' == $type )
			$new_value = (bool) $new_value;

		if ( !$new_value )
			delete_post_meta( $post_id, $key, $old_value );
		else
			update_post_meta( $post_id, $key, $new_value, $old_value );

		return $new_value;
	}
}

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string $type The type of UI. Can be 'input', 'checkbox', 'select', 'textarea' or 'rich'
 * @param bool $echo Wether to echo or return the result
 */
function editable_post_meta( $post_id, $key, $type = 'input', $echo = true ) {
	$data = get_editable_post_meta( $post_id, $key, $type, true );

	if ( !$echo )
		return $data;

	echo $data;
}

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string $type The type of UI. Can be 'input', 'textarea' or 'rich'
 * @param bool $single Wether it's a custom field with a single value or multiple values
 */
function get_editable_post_meta( $post_id, $key, $type = 'input', $single = false ) {
	$content = get_post_meta( $post_id, $key, $single );

	return apply_filters( 'post_meta', $content, $post_id, $key, $type, $single );
}

/*
editable_post_meta( $post_id, $key, 'checkbox', false );
editable_post_meta( $post_id, $key, array( 'type' => 'checkbox', 'values' => array( 'no', 'yes' ) ), false );
editable_post_meta( $post_id, $key, array( 'type' => 'checkbox', 'values' => array( true => 'yes', false => 'no' ) ), false );

editable_post_meta( $post_id, $key, array( 'type' => 'select', 'values' => array( 'foo', 'bar' ) ), false );
editable_post_meta( $post_id, $key, array( 'type' => 'select', 'values' => array( 'foo' => 'Foo', 'bar' => 'Bar' ) ), false );
*/

