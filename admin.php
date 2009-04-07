<?php

class frontEditorAdmin extends scbOptionsPage {
	function setup() {
		$this->fields = array_keys($GLOBALS['frontEditor']->fields);

		$this->args = array(
			'page_title' => 'Front-end Editor',
			'short_title' => 'Front-end Editor',
			'page_slug' => 'front-end-editor'
		);

		$this->nonce = 'fee-settings';
	}

	function page_head() {
		echo $this->css_wrap('.widefat tbody th.check-column {padding-bottom: 7px !important}');
	}

	function form_handler() {
		if ( empty($_POST) )
			return;

		if ( isset($_POST['manage_fields']) ) {
			foreach($this->fields as $field)
				if ( !isset($_POST[$field]) )
					$disabled[] = $field;

			$this->options->update_part(array('disabled' => $disabled));
		}

		if ( isset($_POST['save_settings']) )
			$this->options->update_part(array('rich' => (bool) $_POST['rich']));

		$this->admin_msg(__('Settings saved', 'front-end-editor'));
	}

	function page_content() {
		$this->page_header();
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
<?php foreach ( $this->fields as $field ) { ?>
			<tr>
				<th scope='row' class='check-column'>
					<input type="checkbox" name="<?php echo $field ?>"
						<?php if ( ! @in_array($field, $this->options->disabled) ) echo "checked='checked' "; ?>
					/>
				</th>
				<td><?php _e(ucfirst(str_replace('_', ' ', $field)), 'front-end-editor') ?></td>
			</tr>
<?php } ?>
		</tbody>
	</table>
<?php
		echo $this->submit_button('manage_fields', __('Save changes', 'front-end-editor'));
		echo $this->form_wrap(ob_get_clean());

		$rows = array(
			array(
				'title' => __('Rich text editor', 'front-end-editor'),
				'desc' => __('Enable the WYSIWYG editor', 'front-end-editor'),
				'type' => 'checkbox',
				'names' => 'rich',
			)
		);

		echo "<h3>" . __('Settings', 'front-end-editor') . "</h3>\n";
		echo $this->form_table($rows, 'save_settings', __('Save changes', 'front-end-editor'));

		$this->page_footer();
	}
}

