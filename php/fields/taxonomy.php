<?php

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

		$data['type'] = is_taxonomy_hierarchical( $taxonomy ) ? 'termselect' : 'terminput';

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

		if ( 'terminput' == $type ) {
			wp_set_post_terms( $post_id, $terms, $taxonomy );
		} else {
			wp_set_object_terms( $post_id, absint( $terms ), $taxonomy );
		}

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

