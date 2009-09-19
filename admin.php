<?php

class frontEditorAdmin extends scbBoxesPage
{
	function setup()
	{
		$this->textdomain = 'front-end-editor';

		$this->args = array('page_title' => __('Front-end Editor', $this->textdomain));

		$this->boxes = array(
			array('fields', __('Fields', $this->textdomain), 'normal'),
			array('settings', __('Settings', $this->textdomain), 'side'),
		);
	}

	function page_head() 
	{ 
?>
<style type="text/css">
#fields thead th {
	background: #F1F1F1;
	padding: 5px 8px 8px;
	line-height: 1;
	font-size: 11px;
}
#fields .check-column, #fields th, #fields td {padding-left: 0 !important}
</style>
<?php
	}

	function fields_handler()
	{
		if ( !isset($_POST['manage_fields']) )
			return;

		foreach ( array_keys( frontEditor::get_fields() ) as $field )
			if ( !isset($_POST[$field]) )
				$disabled[] = $field;

		$this->options->disabled = $disable;

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function fields_box()
	{
?>
<p><?php _e('Enable or disable editable fields', $this->textdomain); ?>:</p>
<?php ob_start(); ?>
	<table class="widefat" style="width:auto">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col"><?php _e('Field name', $this->textdomain) ?></th>
		</tr>
		</thead>
		<tbody>
<?php foreach ( frontEditor::get_fields() as $field => $args ) { ?>
			<tr>
				<th scope='row' class='check-column'>
					<?php
						echo $this->input(array(
							'type' => 'checkbox',
							'name' => $field,
							'checked' => ! @in_array($field, $this->options->disabled)
						));
					?>
				</th>
				<td><?php echo $args['title'] ?></td>
			</tr>
<?php } ?>
		</tbody>
	</table>
<?php
		echo $this->form_wrap(ob_get_clean(), '', 'manage_fields');
	}

	function settings_handler()
	{
		if ( !isset($_POST['save_settings']) )
			return;

		foreach ( array('rich', 'chunks', 'highlight') as $key )
			$this->options->$key = (bool) $_POST[$key];

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function settings_box()
	{
		$rows = array(
			array(
				'title' => __('Rich text editor', $this->textdomain),
				'desc' => __('Enable the WYSIWYG editor', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'rich',
			),

			array(
				'title' => __('Edit paragraphs', $this->textdomain),
				'desc' => __('Edit one paragraph at a time, instead of an entire post', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'chunks',
			),
			
			array(
				'title' => __('Highlighting', $this->textdomain),
				'desc' => __('Highlight editable elements', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'highlight',
			),
		);

		echo $this->form_table($rows, $this->options->get(), '', 'save_settings');
	}
}

