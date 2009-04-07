<?php

class scbOptions {
	private $key;
	private $defaults;
	private $data;

	function __construct($key, $file, $defaults) {
		$this->key = $key;
		$this->defaults = $defaults;
		$this->data = get_option($this->key);

		register_activation_hook($file, array($this, 'reset'), false);
		register_uninstall_hook($file, array($this, 'delete'));
	}

	function __get($field) {
		return $this->data[$field];
	}

	function __set($field, $data) {
		$this->data[$field] = $data;
	}

	// Get all data, certain fields or a single field
	function get($field = '') {
		if ( empty($field) )
			return $this->data;

		if ( !is_array($field) )
			return $this->data[$field];

		foreach ( $field as $key )
			$result[] = $this->data[$key];

		return $result;
	}

	// Update a portion of the data
	function update_part($newdata) {
		$this->update(array_merge($this->data, $newdata));
	}

	// Update option
	function update($newdata) {
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		update_option($this->key, $this->data) or
		   add_option($this->key, $this->data);
	}

	// Reset option to defaults
	function reset($override = true) {
		if ( $override || !is_array($this->defaults) || !is_array($this->data) )
			$newdata = $this->defaults;
		else
			foreach ( $this->defaults as $key => $value )
				$newdata[$key] = $this->data[$key] ? $this->data[$key] : $this->defaults[$key];

		$this->update($newdata);
	}

	// Delete option
	function delete() {
		delete_option($this->key);
	}
}

// WP < 2.7
if ( !function_exists('register_uninstall_hook') ) :
function register_uninstall_hook() {}
endif;
