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
		echo "$('head').append(\"$content\");\n";
		echo "});\n";
		echo "</script>";
	}

	// Extract $keys from $array
	static function array_extract($array, $keys) {
		$r = array();
		foreach ( $keys as $key )
			if ( array_key_exists($key, $array) )
				$r[$key] = $array[$key];

	   return $r;
	}

	// Prepare an array for an IN statement
	static function array_to_sql($values) {
		foreach ( $values as &$val )
			$val = "'" . esc_sql(trim($val)) . "'";

		return implode(',', $values);
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

	static function raw($args) {
		echo "<pre>";
		foreach ( $args as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);
		echo "</pre>";	
	}

	function _delayed() {
		if ( !current_user_can('administrator') )
			return;

		$this->raw($this->args);
	}
}
endif;

if ( ! function_exists('debug') ):
function debug() {
	$args = func_get_args();

	// integrate with FirePHP
	if ( function_exists('FB') ) {
		fb($args);

		return;
	}

	new scbDebug($args);
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

