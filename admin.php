<?php

class frontEditorAdmin extends scbAdminPage
{
	function setup()
	{
		$this->args = array('page_title' => 'Front-end Editor');
	}

	function page_head()
	{
		echo $this->css_wrap('.widefat tbody th.check-column {padding-bottom: 7px !important}');
	}

	function form_handler()
	{
		if ( empty($_POST) )
			return;

		if ( isset($_POST['manage_fields']) )
		{
			foreach(array_keys(frontEditor::$fields) as $field)
				if ( !isset($_POST[$field]) )
					$disabled[] = $field;

			$this->options->disabled = $disabled;
		}

		if ( isset($_POST['save_settings']) )
			$this->options->rich = (bool) $_POST['rich'];

		$this->admin_msg(__('Settings saved', 'front-end-editor'));
	}

	function page_content()
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

		echo "<h3>" . __('Settings', 'front-end-editor') . "</h3>\n";

		$rows = array(
			array(
				'title' => __('Rich text editor', 'front-end-editor'),
				'desc' => __('Enable the WYSIWYG editor', 'front-end-editor'),
				'type' => 'checkbox',
				'name' => 'rich',
			)
		);

		echo $this->form_table($rows, $this->options->get(), $this->submit_button('save_settings', __('Save changes', 'front-end-editor')));
	}
}

