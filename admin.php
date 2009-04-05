<?php

class settingsFEE extends scbOptionsPage {

	function setup() {
		$this->options = $GLOBALS['FEE_options']; 
		$this->fields = array_keys($GLOBALS['frontEditor']->fields);
	
		$this->defaults = array(
			'disable' => array()
		);

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
		if ( $_POST['action'] != 'Save Changes' )
			return;

		foreach($this->fields as $field)
			if ( !isset($_POST[$field]) )
				$disabled[] = $field;
				
		$this->options->update_part(array('disabled' => $disabled));
		
		$this->admin_msg('Settings saved');
	}

	function page_content() {
		$this->page_header();
?>
<p>Enable or disable editable fields:</p>
<?php ob_start(); ?>
	<table class="widefat" style="width:auto">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col">Field name</th>
		</tr>
		</thead>
		<tbody>
<?php foreach ( $this->fields as $field ) { ?>
			<tr>
				<th scope='row' class='check-column'>
					<input type="checkbox" name="<?php echo $field ?>"
						<?php if ( ! @in_array($field, $this->options->get('disabled')) ) echo "checked='checked' "; ?>
					/>
				</th>
				<td><?php echo ucfirst(str_replace('_', ' ', $field)); ?></td>
			</tr>
<?php } ?>
		</tbody>
	</table>
<?php
		echo $this->submit_button();
		echo $this->form_wrap(ob_get_clean());
		$this->page_footer();
	}
}

