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
			array( 'cleditor_controls', __( 'Editor Panel', $this->textdomain ), 'side' )
		);
	}

	function page_head() {
		wp_enqueue_style( 'fee-admin', $this->plugin_url . "admin/admin.css", array(), '2.0-alpha' );
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

		echo html( 'p', __( 'Enable or disable editable fields:', $this->textdomain ) );

		$tables  = $this->fields_table( __( 'Post fields', $this->textdomain ), $post_fields );
		$tables .= $this->fields_table( __( 'Other fields', $this->textdomain ), $other_fields );

		echo $this->form_wrap( $tables, '', 'manage_fields' );
	}

	private function fields_table( $title, $fields ) {
		$tbody = '';
		foreach ( $fields as $field => $args ) {
			if ( empty( $args['title'] ) )
				continue;

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
		}

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
				'desc' => __( 'Enable the WYSIWYG editor.', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'rich',
			),

			array(
				'desc' => __( 'Edit one paragraph at a time, instead of an entire post.', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'chunks',
			),

			array(
				'desc' => __( 'Highlight editable elements.', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'highlight',
			),

			array(
				'desc' => __( 'Display a tooltip above editable elements.', $this->textdomain ),
				'type' => 'checkbox',
				'name' => 'tooltip',
			),
		);

		$out = '';
		foreach ( $rows as $row )
			$out .= html( 'p', $this->input( $row ) );

		echo $this->form_wrap( $out, '', 'save_settings' );
	}


	function cleditor_controls_handler() {
		if ( !isset( $_POST['save_buttons'] ) )
			return;

		// TODO: validation
		$this->options->cleditor_controls = trim( @$_POST['cleditor_controls'] );

		$this->admin_msg();
	}

	function cleditor_controls_box() {
		echo html( 'p', __( 'Customize WYSIWYG editor controls:', $this->textdomain ) );

		$out = $this->input( array(
			'type' => 'textarea',
			'name' => 'cleditor_controls',
			'extra' => array( 'id' => false )
		), $this->options->get() );

		$out .= 
		html( 'div', array( 'id' => 'cleditor_controls_profiles' ),
			html( 'a', array( 'data-profile' => 'full', 'href' => '#' ), __( 'All', $this->textdomain ) ),
			' | ',
			html( 'a', array( 'data-profile' => 'default', 'href' => '#' ), __( 'Default', $this->textdomain ) )
		);

		echo $this->form_wrap( $out, '', 'save_buttons' );
?>

<script>
jQuery(document).ready(function ($) {
	var profiles = {
		'full':
			"bold italic underline strikethrough subscript superscript | font size " +
			"style | color highlight removeformat | bullets numbering | outdent " +
			"indent | alignleft center alignright justify | undo redo | " +
			"rule wpimage link unlink | cut copy paste pastetext | print source",
		'default':
			"<?php echo $this->options->get_defaults( 'cleditor_controls' ); ?>"
	}

	$('#cleditor_controls_profiles').delegate( 'a', 'click', function () {
		$('#cleditor_controls textarea').val( profiles[ $(this).attr( 'data-profile' ) ] );

		return false;
	});
});
</script>
<?php
	}
}

