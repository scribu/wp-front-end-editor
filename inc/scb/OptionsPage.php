<?php

class scbOptionsPage extends scbForms {
	// Page args
	var $args = array(
		'page_title' => '',
		'short_title' => '',
		'page_slug' => '',
		'type' => 'settings'
	);

	// Hook string created at page init
	var $pagehook;

	// Nonce string
	var $nonce;

	// Plugin dir url
	var $plugin_url;

	// scbOptions object holder
	var $options;

	// Form actions
	var $actions = array();


//_____MAIN METHODS_____


	// Constructor
	function __construct($file = '') {
		$this->setup();
		$this->_check_args();

		$this->_set_url($file);

		if ( isset($this->options) )
			$this->options->setup($file, $this->defaults);

		add_action('admin_menu', array(&$this, 'page_init'));
	}

	// PHP < 5
	function scbOptionsPage($file = '') {
		$this->__construct($file);
	}

	// This is where all the page args goes
	function setup() {
		trigger_error( "setup() must be over-ridden in a sub-class", E_USER_ERROR );
	}

	// This is where the css and js go
	function page_head() {}

	// This is where the page content goes
	function page_content() {
		trigger_error( "page_content() must be over-ridden in a sub-class", E_USER_ERROR );
	}

	// To be used in ::page_head()
	function admin_msg($msg, $class = "updated") {
		echo "<div class='$class fade'><p>$msg</p></div>\n";
	}

	// Wraps a string in a <script> tag
	function js_wrap($string) {
		return "\n<script language='text/javascript'>\n" . $string . "\n</script>\n";
	}

	// Wraps a string in a <style> tag
	function css_wrap($string) {
		return "\n<style type='text/css'>\n" . $string . "\n</style>\n";
	}

	// Generates a standard page head
	function page_header() {
		$this->form_handler();

		echo "<div class='wrap'>\n";
		echo "<h2>".$this->args['page_title']."</h2>\n";
	}

	// Generates a standard page footer
	function page_footer() {
		echo "</div>\n";
	}

	function form_wrap($content) {
		return parent::form_wrap($content, $this->nonce);
	}

	// Wrap a field in a table row
	function form_row($args, $options) {
		return "\n<tr>\n\t<th scope='row'>{$args['title']}</th>\n\t<td>\n\t\t". parent::input($args, $options) ."\n\t</td>\n\n</tr>";
	}

	// Generates multiple rows and wraps them in a form table
	function form_table($rows, $action = 'Save Changes') {
		$output .= "\n<table class='form-table'>";

		$options = $this->options->get();
		foreach ( $rows as $row )
			$output .= $this->form_row($row, $options);

		$output .= "\n</table>\n";
		$output .= $this->submit_button($action);

		return parent::form_wrap($output, $this->nonce);
	}

	// Generates a submit form button
	function submit_button($action = 'Save Changes', $class = "button") {
		if ( in_array($action, $this->actions) )
			trigger_error("Duplicate action for submit button: {$action}", E_USER_WARNING);

		$args = array(
			'type' => 'submit',
			'names' => 'action',
			'values' => $action,
			'extra' => '',
			'desc_pos' => 'none'
		);

		if ( ! empty($class) )
			$args['extra'] = "class='{$class}'";

		$this->actions[] = $action;
		$output .= "<p class='submit'>\n";
		$output .= parent::input($args);
		$output .= "</p>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Registers a page
	function page_init() {
		if ( !current_user_can('manage_options') )
			return false;

		extract($this->args);

		if ( 'settings' == $type )
			$this->pagehook = add_options_page($short_title, $short_title, 8, $page_slug, array(&$this, 'page_content'));
		elseif ( 'tools' == $type )
			$this->pagehook = add_management_page($short_title, $short_title, 8, $page_slug, array(&$this, 'page_content'));
		else
			trigger_error("Unknown page type: $page", E_USER_WARNING);

		add_action('admin_print_styles-' . $this->pagehook, array(&$this, 'page_head'));
	}

	// Update options
	function form_handler() {
		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->options->get() as $name => $value )
			$new_options[$name] = $_POST[$name];

		$this->options->update($new_options);

		$this->admin_msg('Settings <strong>saved</strong>.');
	}

	// Checks and sets default args
	function _check_args() {
		if ( empty($this->args['page_title']) )
			trigger_error('Page title cannot be empty', E_USER_ERROR);

		if ( empty($this->args['type']) )
			$this->args['type'] = 'settings';

		if ( empty($this->args['short_title']) )
			$this->args['short_title'] = $this->args['page_title'];

		if ( empty($this->args['page_slug']) )
			$this->args['page_slug'] = sanitize_title_with_dashes($this->args['short_title']);
			
		if ( empty($this->nonce) )
			$this->nonce = $this->args['page_slug'];
	}

	// Set plugin_dir
	function _set_url($file) {
		if ( function_exists('plugins_url') )
			$this->plugin_url = plugins_url(plugin_basename(dirname($file)));
		else
			// < WP 2.6
			$this->plugin_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname($file));
	}
}

