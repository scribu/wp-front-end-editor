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
			array( 'ne_buttons', __( 'Editor Panel', $this->textdomain ), 'side' )
		);
	}

	function page_head() {
?>
<style type="text/css">
.inside table.checklist {
	clear: none;
	width: auto;
	float: left;
	margin-right: 1em !important;
}

.inside .checklist th input {
	margin: 0 0 0 4px !important;
}

.inside .checklist thead th {
	padding-top: 5px !important;
	padding-bottom: 5px !important;
}

.checklist thead th {
	background: #F1F1F1;
	padding: 5px 8px 8px;
	line-height: 1;
	font-size: 11px;
}

.checklist .check-column, .checklist th, .checklist td {
	padding-left: 0 !important
}

.submit {
	clear: both !important;
}
</style>
<?php
	}

	protected function checklist_wrap( $title, $tbody ) {
		$thead =
		html( 'tr',
			 html( 'th scope="col" class="check-column"', '<input type="checkbox" />' )
			.html( 'th scope="col"', $title )
		);

		$table =
		html( 'table class="checklist widefat"',
			 html( 'thead', $thead )
			.html( 'tbody', $tbody )
		);

		return $table;
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

		echo html( 'p', __( 'Enable or disable editable fields', $this->textdomain ) );

		$tables  = $this->fields_table( __( 'Post fields', $this->textdomain ), $post_fields );
		$tables .= $this->fields_table( __( 'Other fields', $this->textdomain ), $other_fields );

		echo $this->form_wrap( $tables, '', 'manage_fields' );
	}

	private function fields_table( $title, $fields ) {
		$tbody = '';
		foreach ( $fields as $field => $args )
			$tbody .=
			html( 'tr',
				html( 'th scope="row" class="check-column"',
					$this->input( array(
						'type' => 'checkbox',
						'name' => $field,
						'checked' => !in_array( $field, (array) $this->options->disabled )
					) )
				)
				.html( 'td', $args['title'] )
			);

		return $this->checklist_wrap( $title, $tbody );
	}

	function settings_handler() {
		if ( !isset( $_POST['save_settings'] ) )
			return;

		foreach ( array( 'rich', 'chunks', 'highlight', 'tooltip' ) as $key )
			$this->options->$key = (bool) @$_POST[$key];

		$this->admin_msg();
	}

	function settings_box() {
		$rows = array(
			array(
				'desc' => __( 'Enable the WYSIWYG editor', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'rich',
			),

			array(
				'desc' => __( 'Edit one paragraph at a time, instead of an entire post', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'chunks',
			),

			array(
				'desc' => __( 'Highlight editable elements', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'highlight',
			),

			array(
				'desc' => __( 'Display a tooltip above editable elements', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'tooltip',
			),
		);

		$out = '';
		foreach ( $rows as $row )
			$out .= html( 'p', $this->input( $row ) );

		echo $this->form_wrap( $out, '', 'save_settings' );
	}


	function ne_buttons_handler() {
		if ( !isset( $_POST['save_buttons'] ) )
			return;

		$this->options->ne_buttons = (array) @$_POST['ne_buttons'];

		$this->admin_msg();
	}

	function ne_buttons_box() {
		echo html( 'p', __( 'Enable or disable editor buttons', $this->textdomain ) );

		$ne_buttons = array(
			'bold', 'italic', 'underline',
			'left', 'center', 'right', 'justify',
			'ol', 'ul',
			'subscript', 'superscript', 'strikethrough', 'removeformat',
			'indent', 'outdent', 'hr',
			'fontFormat', 'fontFamily', 'forecolor',
			'link', 'image',
			'xhtml'
		);

		$tbody = '';
		foreach ( apply_filters( 'front_end_editor_nicedit', $ne_buttons ) as $button )
			$tbody .=
			html( 'tr',
				html( 'th scope="row" class="check-column"',
					$this->input( array(
						'type' => 'checkbox',
						'name' => 'ne_buttons[]',
						'value' => $button,
						'desc' => false,
						'checked' => in_array( $button, $this->options->ne_buttons )
					) )
				)
				.html( 'td', $button )
			);

		$table = $this->checklist_wrap( __( 'Button', $this->textdomain ), $tbody );

		echo $this->form_wrap( $table, '', 'save_buttons' );
	}
}

