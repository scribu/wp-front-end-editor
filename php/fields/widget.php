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

		$data = array( 'widget_id' => $p['widget_id'], 'sidebar_id' => $p['id'] );

		list( $before, $after ) = scbUtil::split_at( '</', parent::wrap( '', $data ) );

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

	private function do_( $action, $data, $content = '' ) {
		global $wp_widget_factory;

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
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget->id_base == $id_base )
				break;
		}

		// Get response
		ob_start();

		if ( 'get' == $action ) {
			if ( 'text' == $id_base && FEE_Core::$options->rich )
				$instance['text'] = wpautop( $instance['text'] );

			$widget->form( $instance );
		}

		if ( 'save' == $action ) {
			$new_instance = stripslashes_deep( reset( $_POST[ 'widget-' . $id_base ] ) );

			$instance = $widget->update( $new_instance, $instance );

			update_option( $widget_key, $widgets );

			$this->data = compact( 'sidebar_id', 'widget_id' );
			add_filter( 'sidebars_widgets', array( $this, '_hack' ) );
			dynamic_sidebar( $sidebar_id );
			remove_filter( 'sidebars_widgets', array( $this, '_hack' ) );
		}

		return ob_get_clean();
	}

	// temporarirly remove all other widgets from a specific sidebar
	function _hack( $sidebar_widgets ) {
		extract( $this->data );

		$sidebar_widgets[$sidebar_id] = array( $widget_id );

		return $sidebar_widgets;
	}

	function check( $data = 0 ) {
		return current_user_can( 'edit_theme_options' );
	}
}

