<?php

class scbOptions 
{
	public $key;			// the option name
	public $defaults;		// the default value(s)

	public $wp_filter_id;	// used by WP hooks

	protected $data;

	function __construct($key, $file = '', $defaults = '')
	{
		$this->key = $key;
		$this->defaults = $defaults;
		$this->data = get_option($this->key);

		if ( is_array($this->defaults) )
		{
			$this->data = (array) $this->data;

			register_activation_hook($file, array($this, 'update_reset'));
		}

		register_uninstall_hook($file, array($this, 'delete'));
	}

	// Get all data fields, certain fields or a single field
	function get($field = '')
	{
		if ( empty($field) )
			return $this->data;

		if ( is_string($field) )
			return $this->data[$field];

		foreach ( $field as $key )
			$result[] = $this->data[$key];

		return $result;
	}

	function __get($field)
	{
		return $this->data[$field];
	}

	function __set($field, $data)
	{
		$this->update_part(array($field => $data));
	}

	// Update one or more fields, leaving the others intact
	function update_part($newdata)
	{
		if ( !is_array($newdata) )
			return trigger_error("Wrong data_type", E_USER_WARNING);

		$this->update(array_merge($this->data, $newdata));
	}

	// Update all data fields
	function update($newdata)
	{
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		update_option($this->key, $this->data) or
		   add_option($this->key, $this->data);
	}

	// Add new fields with their default values
	function update_reset()
	{
		$this->update(array_merge($this->defaults, $this->data));
	}

	// Reset option to defaults
	function reset()
	{
		$this->update($this->defaults);
	}

	// Delete option
	function delete()
	{
		delete_option($this->key);
	}
}

// WP < 2.7
if ( !function_exists('register_uninstall_hook') ) :
	function register_uninstall_hook(){}
endif;

