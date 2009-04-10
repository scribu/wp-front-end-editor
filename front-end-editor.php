<?php
/*
Plugin Name: Front-end Editor
Version: 0.8.4
Description: Allows you to edit your posts without going through the admin interface
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/front-end-editor
Text Domain: front-end-editor

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class frontEditor {
	public $fields;
	private $version = '0.8.4';
	private $nonce_action = 'front-editor';
	private $options;

	function __construct($options) {
		$this->register_default_fields();
		$this->options = $options;

		// Set core hooks
		add_action('template_redirect', array($this, 'add_scripts'));
		add_action('wp_ajax_front-editor', array($this, 'ajax_response'));
	}

	function register_default_fields() {
		$this->register('the_title', 'frontEd_basic');
		$this->register('the_content', 'frontEd_basic', 'type=rich');
		$this->register('the_tags', 'frontEd_tags', 'argc=4');

		$this->register('comment_text', 'frontEd_comment', 'type=textarea');

		$this->register('widget_title', 'frontEd_widget');
		$this->register('widget_text', 'frontEd_widget', 'type=textarea');
	}

	// Register a new editable field
	function register($filter, $class, $args = '') {
		$args = wp_parse_args($args, array(
			'title' => ucfirst(str_replace('_', ' ', $filter)),
			'type' => 'input',
			'priority' => 99,
			'argc' => 1
		));

		$args['class'] = $class;

		$this->fields[$filter] = $args;
	}

	function add_scripts() {
		if ( !is_user_logged_in() )
			return;

// DEBUG
// wp_enqueue_script('firebug-lite', 'http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js');

		$url = $this->_get_plugin_url() . '/inc/js';

		if ( $this->options->rich ) {
			wp_enqueue_style('jwysiwyg', $url . '/jwysiwyg/jquery.wysiwyg.css');
			wp_enqueue_script('jwysiwyg', $url . '/jwysiwyg/jquery.wysiwyg.js', array('jquery'));
		}

		wp_enqueue_script('autogrow', $url . '/autogrow.js', array('jquery'));
		wp_enqueue_script('front-editor', $url . '/editor.js', array('jquery'), $this->version);

		add_action('wp_head', array($this, 'add_filters'));
		add_action('wp_head', array($this, 'pass_to_js'));
	}

	function add_filters() {
		foreach ( $this->fields as $name => $args ) {
			if ( @in_array($name, $this->options->disabled) )
				continue;

			extract($args);
			if ( call_user_func(array($class, 'check')) )
				add_filter($name, array($class, 'wrap'), $priority, $argc);
		}
	}

	// Send necesarry info to JS land
	function pass_to_js() {
		foreach( $this->fields as $name => $args ) {
			$type = $args['type'];

			if ( $type == 'rich' && ! $this->options->rich )
				$type = 'textarea';

			$fields[] = array($name, $type);
		}

		$data = array(
			'save_text' => __('Save', 'front-end-editor'),
			'cancel_text' => __('Cancel', 'front-end-editor'),
			'fields' => $fields,
			'request' => get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php',
			'nonce' => wp_create_nonce($this->nonce_action)
		);
?>
<style type='text/css'>
textarea.front-editor-content {width: 100%; height: 250px}
button.front-editor-cancel {font-weight: bold; color:red}
.wysiwyg .panel li:before {content:'' !important}
.wysiwyg .panel li a {width:14px !important; height: 14px !important}
</style>
<script type='text/javascript'>
window.frontEd_data = <?php echo json_encode($data) ?>;
</script>
<?php
	}

	// Common response procedures
	function ajax_response() {
		// Is user trusted?
		check_ajax_referer($this->nonce_action, 'nonce');

		$id = $_POST['item_id'];
		$name = $_POST['name'];
		$action = $_POST['callback'];

		// Is the current field defined?
		if ( ! $args = $this->fields[$name] )
			die(-1);

		// Does the user have the right to do this?
		if ( ! call_user_func(array($args['class'], 'check'), $id) )
			die(-1);

		$callback = array($args['class'], $action);

		if ( $action == 'save' ) {
			$content = stripslashes_deep($_POST['content']);
			call_user_func($callback, $id, $content, $name, $args);
		} elseif ( $action == 'get' ) {
			call_user_func($callback, $id, $name, $args);
		}

		die;
	}

	function get_args($filter) {
		return $this->fields[$filter];
	}

	function _get_plugin_url() {
		// WP < 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}

function register_fronted_field($filter, $class, $args = '') {
	global $frontEditor;

	$frontEditor->register($filter, $class, $args);
}

// Init
add_action('plugins_loaded', 'fee_init', 20);

function fee_init() {
	// Load translations
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('front-end-editor', 'wp-content/plugins/'. $plugin_dir . '/lang', $plugin_dir.'/lang');

	// Load scbFramework
	require_once(dirname(__FILE__) . '/inc/scb/load.php');

	require_once(dirname(__FILE__) . '/inc/compat.php');
	require_once(dirname(__FILE__) . '/fields.php');
	require_once(dirname(__FILE__) . '/admin.php');

	// Load options
	$options = new scbOptions('front-end-editor', __FILE__, array(
		'disable' => array(),
		'rich' => true
	));

	$GLOBALS['frontEditor'] = new frontEditor($options);

	// Give other plugins a chance to register new fields
	do_action('front_ed_fields');

	if ( is_admin() )
		new frontEditorAdmin(__FILE__, $options);
}

