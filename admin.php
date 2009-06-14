<?php

class frontEditorAdmin extends scbBoxesPage
{
	function setup()
	{
		$this->args = array('page_title' => 'Front-end Editor');

		$this->boxes = array(
			array('settings', __('Settings', 'front-end-editor'), 'normal'),
			array('fields', __('Fields', 'front-end-editor'), 'normal'),
		);
	}

	function page_head()
	{
		echo $this->css_wrap(<<<EOB
.widefat tbody th.check-column {padding-bottom: 7px !important}
#fields table {margin: 0 0 10px 10px}
EOB
		);
	}

	function settings_handler()
	{
		if ( !isset($_POST['save_settings']) )
			return;

		foreach ( array('rich', 'chunks') as $key )
			$this->options->$key = (bool) $_POST[$key];

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
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
		echo $this->form_wrap(ob_get_clean(), $this->submit_button('manage_fields', __('Save changes', 'front-end-editor')));
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

		echo $this->form_table($rows, $this->options->get(), $this->submit_button('save_settings', __('Save changes', 'front-end-editor')));
	}
}

