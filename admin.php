<?php

class frontEditorAdmin extends scbBoxesPage
{
	function setup()
	{
		$this->args = array('page_title' => 'Front-end Editor');

		$this->boxes = array(
			array('fields', __('Fields', 'front-end-editor'), 'normal'),
			array('settings', __('Settings', 'front-end-editor'), 'side'),
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

		foreach ( array_keys(frontEditor::$fields) as $field )
			if ( !isset($_POST[$field]) )
				$disabled[] = $field;

		$this->options->disabled = $disabled;

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function fields_box()
	{
?>
<p><?php _e('Enable or disable editable fields', 'front-end-editor'); ?>:</p>
<?php ob_start(); ?>
	<table class="widefat" style="width:auto">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col"><?php _e('Field name', 'front-end-editor') ?></th>
		</tr>
		</thead>
		<tbody>
<?php foreach ( frontEditor::$fields as $field => $args ) { ?>
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
		echo $this->form_wrap(ob_get_clean(), __('Save changes', 'front-end-editor'), 'manage_fields');
	}

	function settings_handler()
	{
		if ( !isset($_POST['save_settings']) )
			return;

		foreach ( array('rich', 'chunks') as $key )
			$this->options->$key = (bool) $_POST[$key];

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function settings_box()
	{
		$rows = array(
			array(
				'title' => __('Rich text editor', 'front-end-editor'),
				'desc' => __('Enable the WYSIWYG editor', 'front-end-editor'),
				'type' => 'checkbox',
				'name' => 'rich',
			),

			array(
				'title' => __('Edit paragraphs', 'front-end-editor'),
				'desc' => __('Edit one paragraph at a time, instead of an entire post', 'front-end-editor'),
				'type' => 'checkbox',
				'name' => 'chunks',
			)
		);

		echo $this->form_table($rows, $this->options->get(),  __('Save changes', 'front-end-editor'), 'save_settings');
	}
}

