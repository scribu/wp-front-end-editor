<?php

class scbUtil {
	// Force script enqueue
	static function do_scripts($handles) {
		global $wp_scripts;

		if ( ! is_a($wp_scripts, 'WP_Scripts') )
			$wp_scripts = new WP_Scripts();

		$wp_scripts->do_items((array) $handles);
	}

	// Force style enqueue
	static function do_styles($handles) {
		self::do_scripts('jquery');

		global $wp_styles;

		if ( ! is_a($wp_styles, 'WP_Styles') )
			$wp_styles = new WP_Styles();

		ob_start();
		$wp_styles->do_items((array) $handles);
		$content = str_replace(array('"', "\n"), array("'", ''), ob_get_clean());

		echo "<script type='text/javascript'>\n";
		echo "jQuery(document).ready(function($) {\n";
		echo "$('head').prepend(\"$content\");\n";
		echo "});\n";
		echo "</script>";
	}

	// Extract certain $keys from $array
	static function array_extract($array, $keys) {
		$r = array();

		foreach ( $keys as $key )
			if ( array_key_exists($key, $array) )
				$r[$key] = $array[$key];

		return $r;
	}

	// Extract a certain value from a list of arrays
	static function array_pluck($array, $key) {
		$r = array();

		foreach ( $array as $value ) {
			if ( is_object($value) )
				$value = get_object_vars($value);
			if ( array_key_exists($key, $value) )
				$r[] = $v[$key];
		}

		return $r;
	}

	// Transform a list of objects into an associative array
	static function objects_to_assoc($objects, $key, $value) {
		$r = array();

		foreach ( $objects as $obj )
			$r[$obj->$key] = $obj->$value;

		return $r;
	}

	// Prepare an array for an IN statement
	static function array_to_sql($values) {
		foreach ( $values as &$val )
			$val = "'" . esc_sql(trim($val)) . "'";

		return implode(',', $values);
	}

	// Have more than one uninstall hooks; also prevents an UPDATE query on each page load
	static function add_uninstall_hook($plugin, $callback) {
		register_uninstall_hook($plugin, '__return_false');	// dummy

		add_action('uninstall_' . plugin_basename($plugin), $callback);
	}
}

// _____Simple debug utility_____

if ( ! class_exists('scbDebug') ):
class scbDebug {
	private $args;

	function __construct($args) {
		$this->args = $args;

		register_shutdown_function(array($this, '_delayed'));
	}

	function _delayed() {
		if ( !current_user_can('administrator') )
			return;

		$this->raw($this->args);
	}

	static function raw($args) {
		echo "<pre>";
		foreach ( $args as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);
		echo "</pre>";	
	}
}
endif;

if ( ! function_exists('debug') ):
function debug() {
	$args = func_get_args();

	// integrate with FirePHP
	if ( class_exists('FirePHP') ) {
		$firephp = FirePHP::getInstance(true);
		$firephp->group('debug');
		foreach ( $args as $arg )
			$firephp->log($arg);
		$firephp->groupEnd();

		return;
	}

	new scbDebug($args);
}
endif;

if ( ! function_exists('debug_raw') ):
function debug_raw() {
	$args = func_get_args();

	scbDebug::raw($args);
}
endif;


// _____Minimalist HTML framework_____

if ( ! function_exists('html') ):
function html($tag, $content = '') {
	list($closing) = explode(' ', $tag, 2);

	return "<{$tag}>{$content}</{$closing}>";
}
endif;

// Generate an <a> tag
if ( ! function_exists('html_link') ):
function html_link($url, $title = '') {
	if ( empty($title) )
		$title = $url;

	return sprintf("<a href='%s'>%s</a>", $url, $title);
}
endif;


// _____Compatibility layer_____

// WP < 3.0
if ( ! function_exists('__return_false') ) :
function __return_false() {
	return false;
}
endif;

// WP < ?
if ( ! function_exists('__return_true') ) :
function __return_true() {
	return true;
}
endif;

// WP < ?
if ( ! function_exists('set_post_field') ) :
function set_post_field($field, $value, $post_id) {
	global $wpdb;

	$post_id = absint($post_id);
	$value = sanitize_post_field($field, $value, $post_id, 'db');

	return $wpdb->update($wpdb->posts, array($field => $value), array('ID' => $post_id));
}
endif;

