<?php

abstract class scbAdminPage extends scbForms
{
	/** Page args
	 * string $parent  (default: options-general.php)
	 * string $page_title  (mandatory)
	 * string $menu_title
	 * string $page_slug
	 * array $action_link  (default: Settings)
	 * string $nonce
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


//_____MAIN METHODS_____


	// Constructor
	function __construct($file, $options = NULL)
	{
		$this->setup();
		$this->_check_args();

		$this->file = $file;
		$this->plugin_url = plugins_dir_url($file);

		if ( $options !== NULL )
		{
			$this->options = $options;
			$this->formdata = $this->options->get();
		}

		add_action('admin_menu', array($this, 'page_init'));

		if ( $this->args['action_link'] )
			add_filter('plugin_action_links_' . plugin_basename($file), array($this, '_action_link'));
	}

	// This is where all the page args are set (DEPRECATED)
	function setup(){}

	// This is where the css and js go
	// Both wp_enqueue_*() and inline code can be added
	function page_head(){}

	// A generic page header
	function page_header()
	{
		$this->form_handler();

		echo "<div class='wrap'>\n";
		echo "<h2>" . $this->args['page_title'] . "</h2>\n";
	}


	// This is where the page content goes
	abstract function page_content();


	// A generic page footer
	function page_footer()
	{
		echo "</div>\n";
	}


	// This is where the form data is validated
	function validate($formdata)
	{
		return $formdata;
	}

	// A generic form handler
	function form_handler()
	{
		if ( empty($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->formdata as $name => $value )
			$new_data[$name] = $_POST[$name];

		$this->formdata = $this->validate($new_data);

		if ( isset($this->options) )
			$this->options->update($this->formdata);

		$this->admin_msg(__('Settings <strong>saved</strong>.', $this->textdomain));
	}


//_____HELPER METHODS_____


	// See scbForms::input()
	function input($args, $options = NULL)
	{
		if ( $options === NULL )
			$options = $this->formdata;

		return parent::input($args, $options);
	}

	// See scbForms::form()
	function form($rows, $options = NULL)
	{
		if ( $options === NULL )
			$options = $this->formdata;

		return parent::form($rows, $options, $this->nonce);
	}

	// See scbForms::table()
	function table($rows, $options = NULL)
	{
		if ( $options === NULL )
			$options = $this->formdata;

		return parent::table($rows, $options);
	}

	// See scbForms::table_row()
	function table_row($row, $options = NULL)
	{
		if ( $options === NULL )
			$options = $this->formdata;

		return parent::table_row($row, $options);
	}

	// Mimics scbForms::form_table()
	function form_table($rows, $options = NULL)
	{
		$output = $this->table($rows, $options);

		$args = array_slice(func_get_args(), 2);
		array_unshift($args, $output);

		return call_user_func_array(array($this, 'form_wrap'), $args);
	}

	// Generates a form submit button
	function submit_button($value = 'Save Changes', $action = 'action', $class = "button")
	{
		if ( empty($value) )
			return;

		$args = array(
			'type' => 'submit',
			'names' => $action,
			'values' => $value,
			'extra' => '',
			'desc' => false
		);

		if ( ! empty($class) )
			$args['extra'] = "class='{$class}'";

		$output = "<p class='submit'>\n" . parent::input($args) . "</p>\n";

		return $output;
	}

	/* 
	Mimics scbForms::form_wrap()
	Second argument can be:
		- bool: 
			true	- add a submit button with the default arguments
			false	- don't add a submit button at all
		- string:
			- <input ... />	(backwards compat)
			- the value of the submit button
			  In this last case, additional arguments will be transmitted to the
			  submit_button() method
	*/
	function form_wrap($content, $submit_button = true)
	{
		if ( true === $submit_button )
			$content .= $this->submit_button();
		elseif ( false !== strpos($submit_button, '<input') )
			$content .= $submit_button;
		else
		{
			$button_args = array_slice(func_get_args(), 1);
			$content .= call_user_func_array(array($this, 'submit_button'), $button_args);
		}

		return parent::form_wrap($content, $this->nonce);
	}

	// Mimics scbForms::form_table_wrap()
	function form_table_wrap($content)
	{
		$output = self::table_wrap($content);

		$args = array_slice(func_get_args(), 1);
		array_unshift($args, $output);

		return call_user_func_array(array($this, 'form_wrap'), $args);
	}

	// Generates a standard admin notice
	function admin_msg($msg, $class = "updated")
	{
		echo "<div class='$class fade'><p>$msg</p></div>\n";
	}

	// Wraps a string in a <script> tag
	function js_wrap($string)
	{
		return "\n<script type='text/javascript'>\n" . $string . "\n</script>\n";
	}

	// Wraps a string in a <style> tag
	function css_wrap($string)
	{
		return "\n<style type='text/css'>\n" . $string . "\n</style>\n";
	}


//_____INTERNAL METHODS (DON'T WORRY ABOUT THESE)_____


	// Registers a page
	function page_init()
	{
		extract($this->args);
		$this->pagehook = add_submenu_page($parent, $page_title, $menu_title, $capability, $page_slug, array($this, '_page_content_hook'));

		add_action('admin_print_styles-' . $this->pagehook, array($this, 'page_head'));
	}

	function _page_content_hook()
	{
		$this->page_header();
		$this->page_content();
		$this->page_footer();
	}

	function _action_link($links)
	{
		$url = add_query_arg('page', $this->args['page_slug'], admin_url($this->args['parent']));
		$links[] = "<a href='$url'>" . $this->args['action_link'] . "</a>";

		return $links;
	}

	function _check_args()
	{
		if ( empty($this->args['page_title']) )
			trigger_error('Page title cannot be empty', E_USER_ERROR);

		$this->args = wp_parse_args($this->args, array(
			'menu_title' => $this->args['page_title'],
			'page_slug' => '',
			'action_link' => __('Settings', $this->textdomain),
			'parent' => 'options-general.php',
			'capability' => 'manage_options',
			'nonce' => ''
		));

		if ( empty($this->args['page_slug']) )
			$this->args['page_slug'] = sanitize_title_with_dashes($this->args['menu_title']);
			
		if ( empty($this->args['nonce']) )
			$this->nonce = $this->args['page_slug'];
	}
}

// WP < 2.8
if ( !function_exists('plugins_dir_url') ) :
function plugins_dir_url($file) 
{
	// WP < 2.6
	if ( !function_exists('plugins_url') )
		return trailingslashit(get_option('siteurl') . '/wp-content/plugins/' . plugin_basename($file));

	return trailingslashit(plugins_url(plugin_basename(dirname($file))));
}
endif;
