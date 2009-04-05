<?php

class scbForms {
	/* Generates one or more input fields, with labels
	$args =	array (
		'type' => any valid <input> type
		'names' => string | array
		'values' => string | array (default: 1 or $options['name'])
		'check' => true | false (default: true)
		'extra' => string (default: class="widefat")
		'desc' => string (default: name)
		'desc_pos' => 'before' | 'after' | 'none' (default: after)
	);
	$options = array('name' => 'value'...)
	*/

	function input($args, $options = array()) {
		extract(wp_parse_args($args, array(
			'desc_pos' => 'after',
			'check' => true,
			'extra' => 'class="widefat"'
		)));

		// Check required fields
		if ( empty($type) )
			trigger_error('No type specified', E_USER_WARNING);

		if ( empty($names) ) {
			trigger_error('No name specified', E_USER_WARNING);
			return;
		}

		// Check for defined options
		if ( $check && 'submit' != $type && !empty($options) )
			scbForms::_check_names($names, $options);

		$f1 = is_array($names);
		$f2 = is_array($values);

		// Set default values
		if ( !isset($values) )
			if ( 'text' == $type && !$f1 )
				$values = stripslashes(wp_specialchars($options[$names], ENT_QUOTES));
			elseif ( in_array($type, array('checkbox', 'radio')) )
				$values = true;

		// Expand names or values
		if ( !$f1 && !$f2 )
			$a = array($names => $values);
		elseif ( $f1 && !$f2 )
			$a = array_fill_keys($names, $values);
		elseif ( !$f1 && $f2 )
			$a = array_fill_keys($values, $names);
		else
			$a = array_combine($names, $values);

		// Determine what goes where
		if ( !$f1 && $f2 ) {
			$i1 = 'val';
			$i2 = 'name';
		} else {
			$i1 = 'name';
			$i2 = 'val';
		}

		if ( $f1 || $f2 )
			$l1 = 'name';
		else
			$l1 = 'desc';

		$token = '%input%';

		// Generate output
		foreach ( $a as $name => $val ) {
			// Build extra string
			$extra_s = $extra;

			if ( in_array($type, array('checkbox', 'radio')) && $options[$$i1] == $$i2)
				$extra_s .= " checked='checked'";

			// Build the item
			$input = "<input name='{$$i1}' value='{$$i2}' type='{$type}' {$extra_s}/> ";

			// Add description
			$desc = $$l1;
			$desc = str_replace('[]', '', $desc);
			if ( FALSE == stripos($desc, $token) )
				if ( 'before' == $desc_pos )
					$desc .= ' ' . $token;
				elseif ( 'after' == $desc_pos )
					$desc = $token . ' ' . $desc;
			$desc = trim(str_replace($token, $input, $desc));

			// Add label
			if ( 'none' == $desc_pos || empty($desc) )
				$output[] = $input . "\n";
			else
				$output[] = "<label for='{$$i1}'>{$desc}</label>\n";
		}

		return implode("\n", $output);
	}

	// Creates a <select> (static)
	function select($args, $options) {
		extract(wp_parse_args($args, array(
			'name' => '', 
			'selected' => NULL, 
			'extra' => ''
		)));

		if ( empty($name) )
			trigger_error('No name specified', E_USER_NOTICE);

		if ( !is_array($options) ) {
			trigger_error("Second argument is expected to be an associative array", E_USER_WARNING);
			return;
		}

		foreach ( $options as $key => $value ) {
			$extra_s = $extra;
			if ( $name === $selected )
				$extra_s = " selected='selected'";
			else
				$extra_s = "";

			$opts .= "\t<option value='{$key}'{$extra_s}>{$value}</option>\n";
		}

		return "<select name='{$name}'>\n{$opts}</select>\n";
	}

	// Creates a <textarea> (static)
	function textarea($args, $content) {
		extract(wp_parse_args($args, array(
			'name' => '', 
			'extra' => 'class="widefat"',
			'escaped' => 'false'
		)));

		if ( !$escaped )
			$content = stripslashes(wp_specialchars($content, ENT_QUOTES));

		if ( empty($name) )
			trigger_error('No name specified', E_USER_NOTICE);

		return "<textarea name='{$name}'{$extra}>\n{$content}\n</textarea>\n";
	}

	// Adds a form around the $content, including a hidden nonce field
	function form_wrap($content, $nonce = 'update_options') {
		$output .= "\n<form method='post' action=''>\n";
		$output .= $content;
		$output .= wp_nonce_field($action = $nonce, $name = "_wpnonce", $referer = true , $echo = false);
		$output .= "\n</form>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Checks if selected $names have equivalent in $options. Used by form_row() (static)
	function _check_names($names, $options) {
		$names = (array) $names;

		foreach ( $names as $i => $name )
			$names[$i] = str_replace('[]', '', $name);

		foreach ( array_diff($names, array_keys($options)) as $key )
			trigger_error("Option not defined: {$key}", E_USER_WARNING);
	}
}

// PHP < 5.2
if ( !function_exists('array_fill_keys') ) :
function array_fill_keys($keys, $value) {
	if ( !is_array($keys) )
		trigger_error('First argument is expected to be an array.' . gettype($keys) . 'given', E_USER_WARNING);

	foreach($keys as $key)
		$r[$key] = $value;

	return $r;
}
endif;

// PHP < 5
if ( !function_exists('array_combine') ) :
function array_combine($keys, $values) {
	if ( !is_array($keys) )
		trigger_error('First argument is expected to be an array.' . gettype($keys) . 'given', E_USER_WARNING);

	if ( !is_array($keys) )
		trigger_error('Second argument is expected to be an array' . gettype($values) . 'given', E_USER_WARNING);

	foreach ( $keys as $i => $key )
		$r[$key] = $values[$i];

	return $r;
}
endif;

