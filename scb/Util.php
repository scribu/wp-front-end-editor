<?php

class scbUtil {

	// Extract $keys from $array
	static function array_extract($array, $keys) {
		$r = array();
		foreach ( $keys as $key )
			if ( isset($array[$key]) )
				$r[$key] = $array[$key];

	   return $r;
	}

	// Prepare an array for an IN statement
	static function array_to_sql($values) {
		foreach ( $values as &$val )
			$val = "'" . esc_sql(trim($val)) . "'";

		return implode(',', $values);
	}

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

		$wp_styles->do_items((array) $handles);
	}

	// Minimalist HTML framework
	static function html($tag, $content = '', $indent = '') {
		list($closing) = explode(' ', $tag);

		return "{$indent}<{$tag}>{$content}{$indent}</{$closing}>";
	}

	// Generate an <a> tag
	static function html_link($url, $title = '') {
		if ( empty($title) )
			$title = $url;

		return sprintf("<a href='%s'>%s</a>", $url, $title);
	}

	// Better debug function
	static function debug() {
		echo "<pre>";
		foreach ( func_get_args() as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);
		echo "</pre>";
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

