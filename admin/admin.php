<?php

class FEE_Admin extends scbBoxesPage {

	function setup() {
		$this->textdomain = 'front-end-editor';

		$this->args = array(
			'page_title' => __( 'Front-end Editor', $this->textdomain ),
			'page_slug' => 'front-end-editor'
		);

		$this->boxes = array(
			array( 'fields', __( 'Fields', $this->textdomain ), 'normal' ),
			array( 'settings', __( 'Settings', $this->textdomain ), 'side' ),
		);

		$this->settings_fields = array(
			array(
				'name' => 'rich',
				'type' => 'checkbox',
				'desc' => __( 'Enable the WYSIWYG editor.', $this->textdomain ),
				'wrap' => html( 'p id="fee-rich"', scbForms::TOKEN )
			),

			array(
				'name' => 'group_post',
				'type' => 'checkbox',
				'desc' => __( 'Edit all post fields at once.', $this->textdomain ),
				'wrap' => html( 'p id="fee-group-post"', scbForms::TOKEN )
			),

			array(
				'name' => 'taxonomy_ui',
				'type' => 'radio',
				'values' => array(
					'termselect' => __( 'dropdown', $this->textdomain ),
					'terminput' => __( 'text field', $this->textdomain ),
				),
				'desc' => __( 'To edit categories, use a:', $this->textdomain ),
				'desc_pos' => 'before',
				'wrap' => html( 'p id="fee-taxonomy-ui"', scbForms::TOKEN )
			)
		);
	}

	function page_head() {
		wp_enqueue_style( 'fee-admin', $this->plugin_url . 'admin/admin.css', array(), FEE_VERSION );
	}

	function fields_handler() {
		if ( !isset( $_POST['manage_fields'] ) )
			return;

		$disabled = array();
		foreach ( array_keys( FEE_Core::get_fields() ) as $field )
			if ( !isset( $_POST[$field] ) )
				$disabled[] = $field;

		$this->options->disabled = $disabled;

		$this->admin_msg();
	}

	function fields_box() {
		// Separate fields
		$post_fields = $other_fields = array();
		foreach ( FEE_Core::get_fields() as $field => $args )
			if ( 'post' == call_user_func( array( $args['class'], 'get_object_type' ) ) )
				$post_fields[$field] = $args;
			else
				$other_fields[$field] = $args;

		echo html( 'p', __( 'Enable or disable editable fields:', $this->textdomain ) );

		$tables  = $this->fields_table( __( 'Post fields', $this->textdomain ), $post_fields );
		$tables .= $this->fields_table( __( 'Other fields', $this->textdomain ), $other_fields );

		echo $this->form_wrap( $tables, '', 'manage_fields' );
	}

	private function fields_table( $title, $fields ) {
		$data = array(
			'col-title' => $title
		);

		foreach ( $fields as $field => $args ) {
			if ( empty( $args['title'] ) )
				continue;

			$data['fields'][] = array(
				'name' => $field,
				'checked' => ( !in_array( $field, (array) $this->options->disabled ) ) ? 'checked' : false,
				'title' => $args['title']
			);
		}

		return self::mustache_render( 'fields-table.html', $data );
	}

	private static function mustache_render( $file, $data ) {
		if ( !class_exists( 'Mustache' ) )
			require dirname(FEE_MAIN_FILE) . '/lib/mustache/Mustache.php';

		$template_path = dirname(__FILE__) . '/' . $file;

		$m = new Mustache;
		return $m->render( file_get_contents( $template_path ), $data );
	}

	function settings_handler() {
		if ( !isset( $_POST['save_settings'] ) )
			return;

		$to_update = scbForms::validate_post_data( $this->settings_fields );

		$this->options->update( $to_update );

		$this->admin_msg();
	}

	function settings_box() {
		$out = implode( ' ', array_map( array( $this, 'input' ), $this->settings_fields ) );

		echo $this->form_wrap( $out, '', 'save_settings' );
	}
}

