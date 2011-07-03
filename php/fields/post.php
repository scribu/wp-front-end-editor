<?php

// Handles the_title and the_content fields
class FEE_Field_Post extends FEE_Field_Base {

	protected $field;

	static function get_object_type() {
		return 'post';
	}

	protected function setup() {
		$this->field = str_replace( 'the_', 'post_', $this->get_filter() );
	}

	function wrap( $content, $post_id = 0 ) {
		if ( !$post_id = $this->_get_id( $post_id ) ) {
			return $content;
		}

		$content = $this->placehold( $content );

		return parent::wrap( $content, compact( 'post_id' ) );
	}

	protected function _get_id( $post_id = 0, $in_loop = true ) {
		global $post;

		if ( $in_loop ) {
			if ( !in_the_loop() ) {
				return false;
			}

			if ( $post_id && $post->ID != $post_id ) {
				return false;
			}

			$post_id = $post->ID;
		}

		if ( !$post_id || !$this->check( $post_id ) ) {
			return false;
		}

		return $post_id;
	}

	function get( $data ) {
		extract( $data );

#		$this->handle_locking( $post_id );

		return get_post_field( $this->field, $post_id );
	}

	function save( $data, $content ) {
		extract( $data );

#		$this->handle_locking( $post_id );

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

		return $this->placehold( $content );
	}

	protected function handle_locking( $post_id ) {
		$last_user = wp_check_post_lock( $post_id );

		if ( $last_user ) {
			$message = __( 'Error: %s is currently editing this.', 'front-end-editor' );
			$message = sprintf( $message, esc_html( get_userdata( $last_user )->display_name ) );

			throw new Exception( $message );
		}

		wp_set_post_lock( $post_id );
	}

	function check( $post_id = 0 ) {
		if ( is_array( $post_id ) ) {
			extract( $post_id );
		}

		return current_user_can( 'edit_post', $post_id );
	}

	protected function set_post_global( $post_id ) {
		$GLOBALS['post'] = get_post( $post_id );
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

		if ( $excerpt == $default_excerpt ) {
			return $excerpt;
		}

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

// Handles the post thumbnail
class FEE_Field_Thumbnail extends FEE_Field_Post {

	function wrap( $html, $post_id, $post_thumbnail_id, $size ) {
		if ( !$post_id = $this->_get_id( $post_id, false ) ) {
			return $html;
		}

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
		extract( self::expand_input_type( $type ) );

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

		if ( 'checkbox' == $type ) {
			$new_value = (bool) $new_value;
		}

		if ( !$new_value ) {
			delete_post_meta( $post_id, $key, $old_value );
		}
		else {
			update_post_meta( $post_id, $key, $new_value, $old_value );
		}

		return $this->placehold( $new_value );
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

	if ( !$echo ) {
		return $data;
	}


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

