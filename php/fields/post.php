<?php

abstract class FEE_Field_Post extends FEE_Field_Base {

	protected $field;

	static function get_object_type() {
		return 'post';
	}

	protected function setup() {
		$this->field = str_replace( 'the_', 'post_', $this->filter );

		if ( FEE_Core::$options->group_post ) {
			add_action( 'post_class', array( __CLASS__, 'post_class' ) );
		}
	}

	static function post_class( $classes ) {
		$classes[] = 'fee-group';

		return $classes;
	}

	static function edit_post_link( $link ) {
		return str_replace( '"post-edit-link', '"post-edit-link fee-edit-button', $link );
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

		wp_update_post( (object) $postdata );
	}

	function check( $post_id = 0 ) {
		if ( is_array( $post_id ) ) {
			extract( $post_id );
		}

		return current_user_can( 'edit_post', $post_id );
	}

	protected function set_post_global( $post_id ) {
		global $post;

		$post = get_post( $post_id );
		setup_postdata( $post );
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
}


// Handles the_title field
class FEE_Field_Post_Title extends FEE_Field_Post {

	function get( $data ) {
		extract( $data );

		if ( 'auto-draft' == get_post_field( 'post_status', $post_id ) )
			return '';

		return get_post_field( $this->field, $post_id, 'edit' );
	}

	function get_filtered( $data ) {
		$this->set_post_global( $data['post_id'] );

		return $this->placehold( get_the_title() );
	}
}


// Handles the_content field
class FEE_Field_Post_Content extends FEE_Field_Post {

	function get_filtered( $data ) {
		$this->set_post_global( $data['post_id'] );

		ob_start();
		the_content();
		return $this->placehold( ob_get_clean() );
	}
}


// Handles the_excerpt field
class FEE_Field_Post_Excerpt extends FEE_Field_Post {

	function get( $data ) {
		$post = get_post( $data['post_id'] );

		$this->set_post_global( $data['post_id'] );

		return wp_trim_excerpt( $post->post_excerpt );
	}

	function save( $data, $excerpt ) {
		if ( $excerpt == $this->get( $data ) ) {
			return;
		}

		$postdata = array(
			'ID' => $data['post_id'],
			'post_excerpt' => $excerpt
		);

		wp_update_post( (object) $postdata );
	}

	function get_filtered( $data ) {
		$this->set_post_global( $data['post_id'] );

		ob_start();
		the_excerpt();
		return ob_get_clean();
	}
}


// Handles the_terms field
class FEE_Field_Terms extends FEE_Field_Post {

	function wrap( $content, $taxonomy, $before, $sep, $after ) {
		global $post;

		if ( !in_the_loop() ) {
			return $content;
		}

		$post_id = $post->ID;

		if ( !$post_id ) {
			return $content;
		}

		$data = compact( 'post_id', 'taxonomy', 'before', 'sep', 'after' );

		if ( !$this->check( $data ) ) {
			return $content;
		}

		$content = $this->placehold( $content );

		$data['type'] = is_taxonomy_hierarchical( $taxonomy ) ? FEE_Core::$options->taxonomy_ui : 'terminput';

		return FEE_Field_Base::wrap( $content, $data );
	}

	function get( $data ) {
		extract( $data );

		if ( 'terminput' == $type ) {
			$tags = get_terms_to_edit( $post_id, $taxonomy );
			$tags = str_replace( ',', ', ', $tags );

			return $tags;
		} else {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( empty( $terms ) )
				$selected = 0;
			else
				$selected = reset( $terms )->term_id;

			return wp_dropdown_categories( array(
				'taxonomy' => $taxonomy,
				'selected' => $selected,
				'hide_empty' => false,
				'hierarchical' => true,
				'show_option_none' => __( '&mdash; None &mdash;', 'front-end-editor' ),
				'echo' => false
			) );
		}
	}

	function save( $data, $terms ) {
		extract( $data );

		if ( 'termselect' == $type ) {
			wp_set_object_terms( $post_id, absint( $terms ), $taxonomy );
		} elseif ( !is_taxonomy_hierarchical( $taxonomy ) ) {
			wp_set_post_terms( $post_id, $terms, $taxonomy );
		} else {
			$term_ids = array();
			foreach ( explode( ',', $terms ) as $term_name ) {
				$term = get_term_by( 'name', trim( $term_name ), $taxonomy );
				if ( !$term ) {
					$r = wp_insert_term( $term_name, $taxonomy );

					if ( is_wp_error( $r ) )
						continue;

					$term_ids[] = (int) $r['term_id'];
				} else {
					$term_ids[] = (int) $term->term_id;
				}
			}

			wp_set_object_terms( $post_id, $term_ids, $taxonomy );
		}
	}

	function get_filtered( $data ) {
		extract( $data );

		$content = get_the_term_list( $post_id, $taxonomy, $before, $sep, $after );

		return $this->placehold( $content );
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
}


// Handles the post thumbnail
class FEE_Field_Post_Thumbnail extends FEE_Field_Post {

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
		} else {
			update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );
		}
	}

	function get_filtered( $data ) {
		extract( $data );

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( !$thumbnail_id )
			return -1;

		list( $url ) = image_downsize( $thumbnail_id, $size );

		return $url;
	}
}


// Handles post_meta field
class FEE_Field_Post_Meta extends FEE_Field_Post {

	function setup() {
		add_filter( 'post_meta', array( __CLASS__, 'prewrap' ), 9, 4 );
	}

	function prewrap( $data, $post_id, $key, $ui ) {
		if ( 'rich' == $ui )
			$data = wpautop( $data );

		return $data;
	}

	function wrap( $data, $post_id, $key, $ui, $single ) {
		if ( $this->check( $post_id ) ) {
			if ( $single ) {
				$data = array( $this->placehold( $data ) );
			}

			$r = array();
			foreach ( $data as $i => $val ) {
				$r[$i] = FEE_Field_Base::wrap( $val, compact( 'post_id', 'key', 'ui', 'i' ) );
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

		if ( !$new_value ) {
			delete_post_meta( $post_id, $key, $old_value );
		}
		else {
			update_post_meta( $post_id, $key, $new_value, $old_value );
		}
	}

	function get_filtered( $data ) {
		extract( $data );

		return $this->placehold( editable_post_meta( $post_id, $key, $type, false ) );
	}
}

