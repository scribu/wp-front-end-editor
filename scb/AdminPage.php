<?php

/*
Creates an admin page

You must set $this->args and define the page_content() method
*/

abstract class scbAdminPage {
	/** Page args
	 * string $parent  (default: options-general.php)
	 * string $page_title  (mandatory)
	 * string $menu_title
	 * string $page_slug
	 * string $capability (default: 'manage_options')
	 * string $nonce
	 * array $action_link  (default: 'Settings')
	 */
	protected $args;

	// URL to the current plugin directory.
	// Useful for adding css and js files
	protected $plugin_url;

	// Created at page init
	protected $pagehook;

	// scbOptions object holder
	// Normally, it's used for storing formdata
	protected $options;

	// l10n
	protected $textdomain;

	// Formdata used for filling the form elements
	protected $formdata = array();


//  ____________MAIN METHODS____________


	// Constructor
	function __construct($file, $options = NULL) {
		if ( $options !== NULL ) {
			$this->options = $options;
			$this->formdata = $this->options->get();
		}

		$this->file = $file;
		$this->plugin_url = plugin_dir_url($file);

		$this->setup();
		$this->check_args();

		add_action('admin_menu', array($this, 'page_init'));
		add_filter('contextual_help', array($this, '_contextual_help'), 10, 2);

		if ( $this->args['action_link'] )
			add_filter('plugin_action_links_' . plugin_basename($file), array($this, '_action_link'));
	}

	// This is where all the page args can be set
	function setup(){}

	// This is where the css and js go
	// Both wp_enqueue_*() and inline code can be added
	function page_head(){}

	// This is where the contextual help goes
	// @return string
	function page_help(){}

	// A generic page header
	function page_header() {
		echo "<div class='wrap'>\n";
		echo "<h2>" . $this->args['page_title'] . "</h2>\n";
	}

	// This is where the page content goes
	abstract function page_content();

	// A generic page footer
	function page_footer() {
		echo "</div>\n";
	}

	// This is where the form data should be validated
	function validate($new_data, $old_data) {
		return $new_data;
	}

	// A generic form handler
	function form_handler() {
		if ( empty($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->formdata as $name => $value )
			$new_data[$name] = @$_POST[$name];

		$this->formdata = $this->validate($new_data, $this->formdata);

		if ( isset($this->options) )
			$this->options->update($this->formdata);

		$this->admin_msg(__('Settings <strong>saved</strong>.', $this->textdomain));
	}


//  ____________UTILITIES____________


	// Generates a form submit button
	function submit_button($value = '', $action = 'action', $class = "button") {
		if ( is_array($value) ) {
			extract(wp_parse_args($value, array(
				'value' => __('Save Changes', $this->textdomain),
				'action' => 'action',
				'class' => 'button',
				'ajax' => true
			)));

			if ( ! $ajax )
				$class .= ' no-ajax';
		}
		else {
			if ( empty($value) )
				$value = __('Save Changes', $this->textdomain);
		}

		$input_args = array(
			'type' => 'submit',
			'names' => $action,
			'values' => $value,
			'extra' => '',
			'desc' => false
		);

		if ( ! empty($class) )
			$input_args['extra'] = "class='{$class}'";

		$output = "<p class='submit'>\n" . scbForms::input($input_args) . "</p>\n";

		return $output;
	}

	/*
	Mimics scbForms::form_wrap()

	$this->form_wrap($content);	// generates a form with a default submit button

	$this->form_wrap($content, false); // generates a form with no submit button

	// the second argument is sent to submit_button()
	$this->form_wrap($content, array(
		'text' => 'Save changes', 
		'name' => 'action', 
		'ajax' => true,
	));
	*/
	function form_wrap($content, $submit_button = true) {
		if ( is_array($submit_button) ) {
			$content .= call_user_func(array($this, 'submit_button'), $submit_button);
		} elseif ( true === $submit_button ) {
			$content .= $this->submit_button();
		} elseif ( false !== strpos($submit_button, '<input') ) {
			$content .= $submit_button;
		} elseif ( false !== $submit_button ) {
			$button_args = array_slice(func_get_args(), 1);
			$content .= call_user_func_array(array($this, 'submit_button'), $button_args);
		}

		return scbForms::form_wrap($content, $this->nonce);
	}

	// See scbForms::input()
	function input($args, $options = NULL) {
		if ( $options === NULL )
			$options = $this->formdata;

		return scbForms::input($args, $options);
	}

	// See scbForms::form()
	function form($rows, $options = NULL) {
		if ( $options === NULL )
			$options = $this->formdata;

		return scbForms::form($rows, $options, $this->nonce);
	}

	// See scbForms::table()
	function table($rows, $options = NULL) {
		if ( $options === NULL )
			$options = $this->formdata;

		return scbForms::table($rows, $options);
	}

	// See scbForms::table_row()
	function table_row($row, $options = NULL) {
		if ( $options === NULL )
			$options = $this->formdata;

		return scbForms::table_row($row, $options);
	}

	// Mimics scbForms::form_table()
	function form_table($rows, $options = NULL) {
		$output = $this->table($rows, $options);

		$args = array_slice(func_get_args(), 2);
		array_unshift($args, $output);

		return call_user_func_array(array($this, 'form_wrap'), $args);
	}
	
	// Mimics scbForms::form_table_wrap()
	function form_table_wrap($content) {
		$output = self::table_wrap($content);

		$args = array_slice(func_get_args(), 1);
		array_unshift($args, $output);

		return call_user_func_array(array($this, 'form_wrap'), $args);
	}

	// Generates a standard admin notice
	function admin_msg($msg, $class = "updated") {
		echo "<div class='$class fade'><p>$msg</p></div>\n";
	}

	// Wraps a string in a <script> tag
	function js_wrap($string) {
		return "\n<script type='text/javascript'>\n" . $string . "\n</script>\n";
	}

	// Wraps a string in a <style> tag
	function css_wrap($string) {
		return "\n<style type='text/css'>\n" . $string . "\n</style>\n";
	}


//  ____________INTERNAL METHODS____________

	function __call($method, $args) {
		return call_user_func_array(array('scbForms', $method), $args);
	}


	// Registers a page
	function page_init() {
		extract($this->args);

		if ( ! $toplevel ) {
			$this->pagehook = add_submenu_page($parent, $page_title, $menu_title, $capability, $page_slug, array($this, '_page_content_hook'));
		} else {
			$func = 'add_' . $toplevel . '_page';
			$this->pagehook = $func($page_title, $menu_title, $capability, $page_slug, array($this, '_page_content_hook'), $icon_url);
		}

		if ( ! $this->pagehook )
			return;

		$this->ajax_response();

		add_action('admin_print_styles-' . $this->pagehook, array($this, 'page_head'));

		add_action('admin_footer', array($this, 'ajax_submit'), 20);
	}

	private function check_args() {
		if ( empty($this->args['page_title']) )
			trigger_error('Page title cannot be empty', E_USER_WARNING);

		$this->args = wp_parse_args($this->args, array(
			'menu_title' => $this->args['page_title'],
			'page_slug' => '',
			'toplevel' => '',
			'icon' => '',
			'parent' => 'options-general.php',
			'action_link' => __('Settings', $this->textdomain),
			'capability' => 'manage_options',
			'nonce' => ''
		));

		if ( empty($this->args['page_slug']) )
			$this->args['page_slug'] = sanitize_title_with_dashes($this->args['menu_title']);

		if ( empty($this->args['nonce']) )
			$this->nonce = $this->args['page_slug'];
	}

	function _contextual_help($help, $screen) {
		if ( is_object($screen) )
			$screen = $screen->id;

		if ( $screen == $this->pagehook && $actual_help = $this->page_help() )
			return $actual_help;

		return $help;
	}

	function ajax_response() {
		if ( ! isset($_POST['_ajax_submit']) || $_POST['_ajax_submit'] != $this->pagehook )
			return;

		$this->form_handler();
		die;
	}

	function ajax_submit() {
		global $page_hook;

		if ( $page_hook != $this->pagehook )
			return;
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	var $spinner = $(new Image()).attr('src', '<?php echo admin_url("images/wpspin_light.gif"); ?>');

	$(':submit').click(function(ev){
		var $submit = $(this);
		var $form = $submit.parents('form');

		if ( $submit.hasClass('no-ajax') || $form.attr('method').toLowerCase() != 'post' )
			return true;

		var $this_spinner = $spinner.clone();

		$submit.before($this_spinner).hide();

		var data = $form.serializeArray();
		data.push({name: $submit.attr('name'), value: $submit.val()});
		data.push({name: '_ajax_submit', value: '<?php echo $this->pagehook; ?>'});

		$.post(location.href, data, function(response){
			var $prev = $('.wrap > .updated, .wrap > .error');
			var $msg = $(response).hide().insertAfter($('.wrap h2'));
			if ( $prev.length > 0 )
				$prev.fadeOut('slow', function(){ $msg.fadeIn('slow'); });
			else
				$msg.fadeIn('slow');

			$this_spinner.hide();
			$submit.show();
		});

		ev.stopPropagation();
		ev.preventDefault();
	});
});
</script>
<?php
		$this->page_head();
	}

	function _page_content_hook() {
		$this->form_handler();

		$this->page_header();
		$this->page_content();
		$this->page_footer();
	}

	function _action_link($links) {
		$url = add_query_arg('page', $this->args['page_slug'], admin_url($this->args['parent']));
		$links[] = "<a href='$url'>" . $this->args['action_link'] . "</a>";

		return $links;
	}
}

