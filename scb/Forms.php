<?php

class scbForms
{
	/* Generates one or more form elements of the same type,
	   including <select>s and <textarea>s.

		$args =	array (
			'type' => string  (mandatory)
			'name' => string | array  (mandatory)
			'value' => string | array
			'desc' => string | array | false
			'desc_pos' => 'before' | 'after' | 'foo %input% bar'  (default: after)
			'extra' => string  (default: class="regular-text")
		);

		$formdata = associative array with the formdata with which to fill the elements
	*/
	function input($args, $formdata = array())
	{
		// Backwards compat
		foreach ( array('name', 'value') as $key )
		{
			$old = $key . 's';
			if ( isset($args[$old]) )
				$args[$key] = $args[$old];
		}

		// Check required fields
		$error = false;
		foreach ( array('name', 'type') as $key )
		{
			if ( isset($args[$key]) )
				continue;

			$error = true;
			trigger_error("No $key specified", E_USER_WARNING);
		}

		if ( $error )
			return;

		switch ( $args['type'] )
		{
			case 'select':  	return self::_select($args, $formdata);
			case 'textarea':	return self::_textarea($args, $formdata);
		}

		return self::_input($args, $formdata);
	}

	static $token = '%input%';

	// Deprecated
	function select($args, $options = array())
	{
		if ( !empty($options) )
			$args['value'] = $options;

		return self::_select($args);
	}

	// Deprecated
	function textarea($args, $content = '')
	{
		if ( !empty($content) )
			$args['value'] = $content;

		return self::_textarea($args);
	}


// ____________UTILITIES____________


	function form($inputs, $formdata = NULL, $nonce)
	{
		$output = '';
		foreach ( $inputs as $input )
			$output .= self::input($input, $formdata);

		$output = self::form_wrap($output, $nonce);

		return $output;
	}

	function table($rows, $formdata = NULL)
	{
		$output = '';
		foreach ( $rows as $row )
			$output .= self::table_row($row, $formdata);

		$output = self::table_wrap($output);

		return $output;
	}

	// Generates multiple rows and wraps them in a form table
	function form_table($rows, $formdata = NULL)
	{
		$output = '';
		foreach ( $rows as $row )
			$output .= self::table_row($row, $formdata);

		$output = self::form_table_wrap($output);

		return $output;
	}

	function table_row($args, $formdata = NULL)
	{
		return self::row_wrap($args['title'], self::input($args, $formdata));
	}


// ____________WRAPPERS____________


	function table_wrap($content)
	{
		$output = "\n<table class='form-table'>\n" . $content . "\n</table>\n";

		return $output;
	}

	function form_wrap($content, $nonce = 'update_options')
	{
		$output = "\n<form method='post' action=''>\n";
		$output .= $content;
		$output .= wp_nonce_field($action = $nonce, $name = "_wpnonce", $referer = true , $echo = false);
		$output .= "\n</form>\n";

		return $output;
	}

	function form_table_wrap($content, $nonce = 'update_options')
	{
		$output = self::table_wrap($content);
		$output = self::form_wrap($output, $nonce);

		return $output;
	}

	function row_wrap($title, $content)
	{
		return "\n<tr>\n\t<th scope='row'>" . $title . "</th>\n\t<td>\n\t\t" . $content . "\t</td>\n\n</tr>";
	}


// ____________PRIVATE METHODS____________


	// From multiple inputs to single inputs
	private static function _input($args, $formdata)
	{
		extract(wp_parse_args($args, array(
			'name' => NULL,
			'value' => NULL,
			'desc' => NULL,
			'checked' => NULL,
		)), EXTR_SKIP);

		// Expand names or values
		if ( !is_array($name) && !is_array($value) )
			$a = array($name => $value);
		elseif ( is_array($name) && !is_array($value) )
			$a = array_fill_keys($name, $value);
		elseif ( !is_array($name) && is_array($value) )
			$a = array_fill_keys($value, $name);
		else
			$a = array_combine($name, $value);

		// Determine what goes where
		if ( !is_array($name) && is_array($value) )
		{
			$i1 = 'val';
			$i2 = 'name';
		}
		else
		{
			$i1 = 'name';
			$i2 = 'val';
		}

		$func = in_array($type, array('checkbox', 'radio')) ? '_checkbox_single' : '_input_single';

		// Set constant args
		$const_args = self::array_slice_assoc($args, array('type', 'desc_pos', 'checked'));
		if ( isset($extra) )
			$const_args['extra'] = explode(' ', $extra);

		$i = 0;
		foreach ( $a as $name => $val )
		{
			$cur_args = $const_args;

			if ( $$i1 !== NULL )
				$cur_args['name'] = $$i1;

			if ( $$i2 !== NULL )
				$cur_args['value'] = $$i2;

			// Set desc
			if ( is_array($desc) )
				$cur_args['desc'] = $desc[$i];
			elseif ( isset($desc) )
				$cur_args['desc'] = $desc;

			// Find relevant formdata
			if ( $checked === NULL )
			{
				$match = @$formdata[str_replace('[]', '', $$i1)];
				if ( is_array($match) )
					$match = $match[$i];
			}
			else if ( is_array($checked) )
				$cur_args['checked'] = $checked[$i];

			$output[] = self::$func($cur_args, $match);

			$i++;
		}

		return implode("\n", $output);
	}

	// Handle args for checkboxes and radio inputs
	private static function _checkbox_single($args, $data)
	{
		$args = wp_parse_args($args, array(
			'name' => NULL,
			'value' => true,
			'desc_pos' => 'after',
			'desc' => NULL,
			'checked' => NULL,
			'extra' => array(),
		));

		foreach ( $args as $key => &$val )
			$$key = &$val;
		unset($val);

		if ( $checked === NULL && $name !== NULL && $value == $data )
			$checked = true;

		if ( $checked )
			$extra[] = "checked='checked'";

		if ( $desc === NULL && !is_bool($value) )
			$desc = str_replace('[]', '', $value);

		return self::_input_gen($args);
	}

	// Handle args for text inputs
	private static function _input_single($args, $data)
	{
		foreach ( $args as $key => &$value )
			$$key = &$value;

		$args = wp_parse_args($args, array(
			'value' => stripslashes(esc_html($data)),
			'desc_pos' => 'after',
			'extra' => array('class="regular-text"'),
		));

		if ( FALSE === strpos($name, '[]') )
			$extra[] = "id='{$name}'";

		return self::_input_gen($args);
	}

	// Generate html with the final args
	function _input_gen($args)
	{
		extract(wp_parse_args($args, array(
			'name' => NULL,
			'value' => NULL,
			'desc' => NULL,
		)), EXTR_SKIP);

		$extra = implode(' ', $extra);

		// Build the item
		$input = "<input name='{$name}' value='{$value}' type='{$type}'{$extra} /> ";

		// Set label
		if ( FALSE === strpos($desc, self::$token) )
			switch ($desc_pos)
			{
				case 'before': $label = $desc .' ' . self::$token; break;
				case 'after': $label = self::$token . ' ' . $desc;
			}
		else
			$label = $desc;

		$label = trim(str_replace(self::$token, $input, $label));

		// Add label
		if ( empty($label) || $desc === false )
			$output = $input . "\n";
		else
			$output = "<label>{$label}</label>\n";

		return $output;
	}

	private static function _select($args, $formdata)
	{
		extract(wp_parse_args($args, array(
			'name' => '',
			'value' => array(),
			'text' => '',
			'selected' => array('foo'),	// hack to make default blank
			'extra' => '',
			'numeric' => false	// use numeric array instead of associative
		)), EXTR_SKIP);

		$cur_val = $selected;
		if ( isset($formdata[$name]) )
			$cur_val = $formdata[$name];

		if ( !is_array($value) )
			return trigger_error("Second argument is expected to be an array", E_USER_WARNING);

		if ( empty($value) )
			$value = array('' => '');

		if ( !self::is_associative($value) && !$numeric )
			$value = array_combine($value, $value);

		if ( FALSE === $text )
			$opts = '';
		else
		{
			$opts = "\t<option";
			if ( $cur_val === array('foo') )
				$opts .= " selected='selected'";
			$opts .= ">{$text}</option>\n";
		}

		foreach ( $value as $key => $value )
		{
			if ( empty($key) && empty($value) )
				continue;

			$cur_extra = array();
			if ( $key == $cur_val )
				$cur_extra[] = "selected='selected'";

			$cur_extra = implode(' ', $cur_extra);

			$opts .= "\t<option value='{$key}'{$cur_extra}>{$value}</option>\n";
		}

		$extra = self::validate_extra($extra, $name);

		return "<select name='{$name}' $extra>\n{$opts}</select>\n";
	}

	private static function _textarea($args, $formdata)
	{
		extract(wp_parse_args($args, array(
			'name' => '', 
			'extra' => 'class="widefat"',
			'value' => '',
			'escaped' => FALSE,
		)), EXTR_SKIP);

		if ( !$escaped )
			$value = stripslashes(esc_html($value));

		$extra = self::validate_extra($extra, $name);

		return "<textarea name='{$name}'{$extra}>\n{$value}\n</textarea>\n";
	}

	private static function validate_extra($extra, $name, $implode = true)
	{
		$extra = explode(' ', $extra);
		if ( FALSE === strpos($name, '[]') )
			$extra[] = " id='{$name}'";
		$extra = implode(' ', $extra);

		return $extra;
	}

// Sugar

	private static function is_associative($array)
	{
		if ( !is_array($array) || empty($array) )
			return false;

		$keys = array_keys($array);

		return array_keys($keys) !== $keys;
	}

	private static function array_slice_assoc($array, $keys)
	{
 	   return array_intersect_key($array,array_flip($keys));
	}

	private static function debug() 
	{
		$args = func_get_args();
		
		echo "<pre>";

		foreach ( $args as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);

		echo "</pre><br />";
	}
}

// WP < 2.8
if ( !function_exists('esc_html') ) :
function esc_html($text)
{
	return wp_specialchars($text, ENT_QUOTES);
}
endif;

// PHP < 5.2
if ( !function_exists('array_fill_keys') ) :
function array_fill_keys($keys, $value)
{
	if ( !is_array($keys) )
		trigger_error('First argument is expected to be an array.' . gettype($keys) . 'given', E_USER_WARNING);

	foreach($keys as $key)
		$r[$key] = $value;

	return $r;
}
endif;

