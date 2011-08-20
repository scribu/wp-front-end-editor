<?php

// Handles dynamic_sidebar_params fields
class FEE_Field_Widget extends FEE_Field_Base {

	static function get_object_type() {
		return 'widget';
	}

	function wrap( $params ) {
		if ( !$this->check() )
			return $params;

		$p =& $params[0];

		// Text widgets are handled differently
		if ( 0 === strpos( $p['widget_id'], 'text-' ) )
			return $params;

		$wrap = parent::wrap( '', array( 'widget_id' => $p['widget_id'], 'sidebar_id' => $p['id'] ) );

		list( $before, $after ) = scbUtil::split_at( '</', $wrap );

		$p['before_widget'] = $p['before_widget'] . $before;
		$p['after_widget'] = $after . $p['after_widget'];

		return $params;
	}

	function get( $data ) {
		return $this->do_( 'get', $data );
	}

	function save( $data, $content ) {
		return $this->do_( 'save', $data, $content );
	}

	function get_filtered( $data ) {
		extract( $data );

		ob_start();

		$this->data = compact( 'sidebar_id', 'widget_id' );
		add_filter( 'sidebars_widgets', array( $this, '_hack' ) );
		dynamic_sidebar( $sidebar_id );
		remove_filter( 'sidebars_widgets', array( $this, '_hack' ) );

		return ob_get_clean();
	}

	protected function do_( $action, $data, $content = '' ) {
		extract( $data );

		// Get widget type and number
		$id_base = explode( '-', $widget_id );
		$widget_nr = array_pop( $id_base );
		$id_base = implode( '-', $id_base );

		// Get widget instance
		$widget_key = 'widget_' . $id_base;
		$widgets = get_option( $widget_key );
		$instance =& $widgets[ $widget_nr ];

		// Get widget class
		foreach ( $GLOBALS['wp_widget_factory']->widgets as $widget_obj ) {
			if ( $widget_obj->id_base == $id_base )
				break;
		}

		// Get response
		if ( 'get' == $action ) {
			ob_start();
			$widget_obj->form( $instance );
			return ob_get_clean();
		}

		if ( 'save' == $action ) {
			$new_instance = stripslashes_deep( reset( $_POST[ 'widget-' . $id_base ] ) );

			$instance = $widget_obj->update( $new_instance, $instance );

			update_option( $widget_key, $widgets );
		}
	}

	// temporarily remove all other widgets from a specific sidebar
	function _hack( $sidebar_widgets ) {
		extract( $this->data );

		$sidebar_widgets[$sidebar_id] = array( $widget_id );

		return $sidebar_widgets;
	}

	function check( $data = 0 ) {
		return current_user_can( 'edit_theme_options' );
	}
}

class FEE_Field_Widget_Text extends FEE_Field_Widget {
	protected $field;

	protected function setup() {
		$this->field = str_replace( 'widget_', '', $this->filter );
	}

	function wrap( $content, $instance = null, $id_base = null ) {
		// Only target text widgets
		if ( 'title' == $this->field && ( !$id_base || 'text' != $id_base ) )
			return $content;

		if ( 'text' == $this->field )
			$content = $this->placehold( $content );

		return FEE_Field_Base::wrap( $content, array() );
	}

	protected function do_( $action, $data, $content = '' ) {
		extract( $data );

		// Get widget type and number
		$id_base = explode( '-', $widget_id );
		$widget_nr = array_pop( $id_base );
		$id_base = implode( '-', $id_base );

		// Get widget instance
		$widget_key = 'widget_' . $id_base;
		$widgets = get_option( $widget_key );

		$old_content =& $widgets[$widget_nr][$this->field];

		if ( 'get' == $action ) {
			return $old_content;
		}

		if ( 'get_filtered' == $action ) {
			if ( 'text' == $this->field ) {
				$old_content = wpautop( $this->placehold( $old_content ) );
			}

			return $old_content;
		}

		if ( 'save' == $action ) {
			$old_content = $content;
			$widgets[$widget_nr]['filter'] = true;

			update_option( $widget_key, $widgets );
		}
	}

	function get_filtered( $data ) {
		return $this->do_( 'get_filtered', $data );
	}
}

