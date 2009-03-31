<?php
/*
Plugin Name: Front-end Editor
Version: 0.5.2a
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
	var $fields = array(
		'title' => array('filter' => 'the_title', 'type' => 'input'),
		'content' => array('filter' => 'the_content', 'type' => 'textarea')
	);

	function __construct() {
		$this->fields = apply_filters('front_ed_fields', $this->fields);

		add_action('template_redirect', array($this, 'add_scripts'));
		add_action('wp_ajax_front-editor', array($this, 'ajax_response'));
	}

	// PHP < 4
	function frontEditor() {
		$this->__construct();
	}

	function add_scripts() {
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
			return;

		$url = $this->_get_plugin_url() . '/js';
		wp_enqueue_script('front-editor', $url . '/editor.js', array('jquery'));
		wp_enqueue_script('autogrow', $url . '/autogrow.js', array('jquery'));

		add_action('wp_head', array($this, 'pass_to_js'));
		add_action('wp_head', array($this, 'add_filters'));
	}

	function add_filters() {
		foreach( $this->fields as $field )
			add_filter($field['filter'], array($this, 'wrap'));
	}

	function wrap($content) {
		global $post;

		if( !$this->_check_perm($post->ID) )
			return $content;

		$class = 'front-ed-' . $this->_get_field('filter', current_filter()) . ' front-ed';

		return "<span rel='{$post->ID}' class='{$class}'>{$content}</span>";
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
textarea.front-editor-content {width: 100%}
button.front-editor-cancel {font-weight: bold; color:red}
</style>
<script type='text/javascript'>window.frontEd_data = <?php echo json_encode($data) ?>;</script>
<?php
	}

	// Common response procedures
	function ajax_response() {
		// Is user trusted?
		check_ajax_referer($this->nonce_action, 'nonce');

		// Can user edit current post?
		$post_id = $_POST['post_id'];
		if( !$this->_check_perm($post_id) )
			die(-1);

		// Is the current field defined?
		if ( !$c_field = $this->fields[$_POST['name']] )
			die(-1);

		$name = $_POST['name'];
		$type = $_POST['callback'];

		if( !$callback = $c_field[$type . '_callback'] )
			$callback = array($this, $type . '_callback');

		if ( $type == 'save' ) {
			$content = stripslashes_deep($_POST['content']);
			call_user_func($callback, $post_id, $content, $name);
			echo apply_filters($this->fields[$name]['filter'], $content);
		} elseif ( $type == 'get' ) {
			call_user_func($callback, $post_id, $name);
		}

		die;
	}

	function get_callback($id, $name) {
		global $wpdb;

		$field = 'post_' . $name;

		$post = (array) get_post($id);

		echo $post[$field];
	}

	function save_callback($id, $content, $name) {
		global $wpdb;

		$field = 'post_' . $name;

		return $wpdb->update($wpdb->posts, array($field => $content), array('ID' => $id));
	}

	// Return $name where $args[key] = $value
	function _get_field($key, $value) {
		foreach ( $this->fields as $name => $args )
			if ( $args[$key] == $value )
				return $name;
	}

	function _check_perm($id) {
		return current_user_can('edit_post', $id) || current_user_can('edit_page', $id);
	}

	function _get_plugin_url() {
		// WP < 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}


// PHP < 5.2
if( !function_exists('json_encode') ) :
function json_encode($array) {
	if( !is_array( $array ) )
		return false;

	$associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));

	if( $associative ) {
		$construct = array();
		foreach( $array as $key => $value ) {
			// We first copy each key/value pair into a staging array,
			// formatting each key and value properly as we go.

			// Format the key:
			if( is_numeric($key) ){
				$key = "key_$key";
			}
			$key = "'".addslashes($key)."'";

			// Format the value:
			if( is_array( $value )) {
				$value = json_encode($value);
			} else if( !is_numeric( $value ) || is_string( $value ) ) {
				$value = "'".addslashes($value)."'";
			}

			// Add to staging array:
			$construct[] = "$key: $value";
		}

		// Then we collapse the staging array into the JSON form:
		$result = "{ " . implode( ", ", $construct ) . " }";

	} else { // If the array is a vector (not associative):

		$construct = array();
		foreach( $array as $value ){

			// Format the value:
			if( is_array( $value )){
				$value = json_encode($value);
			} else if( !is_numeric( $value ) || is_string( $value ) ){
				$value = "'".addslashes($value)."'";
			}

			// Add to staging array:
			$construct[] = $value;
		}

		// Then we collapse the staging array into the JSON form:
		$result = "[ " . implode( ", ", $construct ) . " ]";
	}

	return $result;
}
endif;

new frontEditor();

