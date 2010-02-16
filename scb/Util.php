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

	// Better debug function
	static function debug() {
		// integrate with FirePHP
		if ( function_exists('FB') ) {
			foreach ( func_get_args() as $arg )
				FB($arg);

			return;
		}

		echo "<pre>";
		foreach ( func_get_args() as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);
		echo "</pre>";
	}

	// Minimalist HTML framework
	static function html($tag, $content = '', $indent = null) {
		list($closing) = explode(' ', $tag, 2);

		return "<{$tag}>{$content}</{$closing}>";
	}

	// Generate an <a> tag
	static function html_link($url, $title = '') {
		if ( empty($title) )
			$title = $url;

		return sprintf("<a href='%s'>%s</a>", $url, $title);
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

// Create shortcuts
foreach ( array('debug', 'html', 'html_link') as $func )
	if ( ! function_exists($func) )
		eval("
	function $func() {
		\$args = func_get_args();

		return call_user_func_array(array('scbUtil', '$func'), \$args);
	}
		");

