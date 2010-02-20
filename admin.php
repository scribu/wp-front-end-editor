<?php

class FEE_Admin extends scbBoxesPage {
	function setup() {
		$this->textdomain = 'front-end-editor';

		$this->args = array('page_title' => __('Front-end Editor', $this->textdomain));

		$this->boxes = array(
			array('fields', __('Fields', $this->textdomain), 'normal'),
			array('settings', __('Settings', $this->textdomain), 'side'),
		);
	}

	function page_head() {
?>
<style type="text/css">
#fields table {
	clear: none;
	width: auto;
	float: left;
	margin-right: 1em !important;
}

#fields thead th {
	background: #F1F1F1;
	padding: 5px 8px 8px;
	line-height: 1;
	font-size: 11px;
}
#fields .check-column, #fields th, #fields td {padding-left: 0 !important}

#fields .submit {
	clear: both !important;
}
</style>
<?php
	}

	function fields_handler() {
		if ( ! isset($_POST['manage_fields']) )
			return;

		$disabled = array();
		foreach ( array_keys( FEE_Core::get_fields() ) as $field )
			if ( ! isset($_POST[$field]) )
				$disabled[] = $field;

		$this->options->disabled = $disabled;

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function fields_box() {
		// Separate fields
		$post_fields = $other_fields = array();
		foreach ( FEE_Core::get_fields() as $field => $args )
			if ( 'post' == call_user_func(array($args['class'], 'get_object_type') ) )
				$post_fields[$field] = $args;
			else
				$other_fields[$field] = $args;

		echo html('p', __('Enable or disable editable fields', $this->textdomain));

		$tables  = self::fields_table(__('Post fields', $this->textdomain), $post_fields);
		$tables .= self::fields_table(__('Other fields', $this->textdomain), $other_fields);

		echo $this->form_wrap($tables, '', 'manage_fields');
	}

	private function fields_table($title, $fields) {
		$thead = 
		html('thead',
			html('tr',
				html('th scope="col" class="check-column"', '<input type="checkbox" />')
				.html('th scope="col"', $title)
			)
		);

		$tbody = '';
		foreach ( $fields as $field => $args )
			$tbody .=
			html('tr', 
				html('th scope="row" class="check-column"', 
					$this->input(array(
						'type' => 'checkbox',
						'name' => $field,
						'checked' => ! @in_array($field, $this->options->disabled)
					))
				)
				.html('td', $args['title'])
			);
			
		return html('table class="widefat"', $thead . $tbody);
	}

	function settings_handler() {
		if ( !isset($_POST['save_settings']) )
			return;

		foreach ( array('rich', 'chunks', 'reset_date', 'highlight') as $key )
			$this->options->$key = (bool) @$_POST[$key];

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function settings_box() {
		$rows = array(
			array(
				'desc' => __('Enable the WYSIWYG editor', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'rich',
			),

			array(
				'desc' => __('Edit one paragraph at a time, instead of an entire post', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'chunks',
			),

			array(
				'desc' => __('Reset the post date on each edit', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'reset_date',
			),

			array(
				'title' => __('Highlighting', $this->textdomain),
				'desc' => __('Highlight editable elements', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'highlight',
			),
		);
		
		$out = '';
		foreach ( $rows as $row )
			$out .= html('p', $this->input($row));

		echo $this->form_wrap($out, '', 'save_settings');
	}
}

