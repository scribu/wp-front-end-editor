<?php
/*
Plugin Name: Front-end Editor
Version: 0.6.1
Description: Allows you to edit your posts without going through the admin interface
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/front-end-editor

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
	var $nonce_action = 'front-editor';
	var $fields;

	function __construct() {
		$this->register('the_content', 'frontEd_basic', 'type=textarea');
		$this->register('widget_text', 'frontEd_widget', 'type=textarea');
		$this->register('the_title', 'frontEd_basic');
		$this->register('the_tags', 'frontEd_tags', 'argc=4');

		// Give other plugins a chance to register new fields
		do_action('front_ed_fields');

		// Set core hooks
		add_action('template_redirect', array($this, 'add_scripts'));
		add_action('wp_ajax_front-editor', array($this, 'ajax_response'));
	}

	// Register a new editable field
	function register($filter, $class, $args = '') {
		$args = wp_parse_args($args, array(
			'type' => 'input',
			'priority' => 10,
			'argc' => 1
		));

		$args['class'] = $class;

		$this->fields[$filter] = $args;
	}

	// PHP < 4
	function frontEditor() {
		$this->__construct();
	}

	function add_scripts() {
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
			return;

		$url = $this->_get_plugin_url() . '/js';

//		wp_enqueue_style('jwysiwyg', $url . '/jwysiwyg/jquery.wysiwyg.css');
//		wp_enqueue_script('jwysiwyg', $url . '/jwysiwyg/jquery.wysiwyg.js', array('jquery'));
		wp_enqueue_script('autogrow', $url . '/autogrow.js', array('jquery'));
		wp_enqueue_script('front-editor', $url . '/editor.js', array('jquery'), '0.6.1');

		add_action('wp_head', array($this, 'pass_to_js'));
		add_action('wp_head', array($this, 'add_filters'));
	}

	function add_filters() {
		foreach ( $this->fields as $name => $args ) {
			extract($args);
			add_filter($name, array($class, 'wrap'), $priority, $argc);
		}
	}

	// Send necesarry info to JS land
	function pass_to_js() {
		foreach( $this->fields as $name => $args )
			$fields[] = array($name, $args['type']);

		$data = array(
			'fields' => $fields,
			'request' => get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php',
			'nonce' => wp_create_nonce($this->nonce_action)
		);
?>
<style type='text/css'>
textarea.front-editor-content {width: 100%; height: 250px}
button.front-editor-cancel {font-weight: bold; color:red}
</style>
<script type='text/javascript'>window.frontEd_data = <?php echo json_encode($data) ?>;</script>
<?php
	}

	// Common response procedures
	function ajax_response() {
		// Is user trusted?
		check_ajax_referer($this->nonce_action, 'nonce');

		$id = $_POST['item_id'];
		$name = $_POST['name'];
		$action = $_POST['callback'];

		// Can user edit current post?
		if ( ! $this->check_perm($post_id) )
			die(-1);

		// Is the current field defined?
		if ( ! $args = $this->fields[$name] )
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

	function check_perm($id) {
		return current_user_can('edit_post', $id) || current_user_can('edit_page', $id);
	}

	function _get_plugin_url() {
		// WP < 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}

// Init
fee_init();

function fee_init() {
	require_once(dirname(__FILE__) . '/compat.php');
	require_once(dirname(__FILE__) . '/fields.php');

	$GLOBALS['frontEditor'] = new frontEditor();
}

function register_fronted_field($filter, $class, $args = '') {
	global $frontEditor;

	$frontEditor->register($filter, $class, $args = '');
}

