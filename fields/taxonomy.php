<?php

// Handles the_terms field
class FEE_Field_Terms extends FEE_Field_Post {

	function wrap( $content, $taxonomy, $before, $sep, $after ) {
		global $post;
	
		if ( !in_the_loop() )
			return $content;

		$post_id = $post->ID;

		if ( !$post_id )
			return $content;

		$data = compact( 'post_id', 'taxonomy', 'before', 'sep', 'after' );

		if ( !$this->check( $data ) )
			return $content;

		$content = $this->placehold( $content );

		return FEE_Field_Base::wrap( $content, $data );
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

		$content = get_the_term_list( $post_id, $taxonomy, '', ', ' );

		return $this->placehold( $content );
	}
}

