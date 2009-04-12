<?php

class scbOptions {
	private $key;
	private $defaults;
	private $data;

	function __construct($key, $file = '', $defaults = '') {
		$this->key = $key;
		$this->defaults = $defaults;
		$this->data = get_option($this->key);

		register_activation_hook($file, array($this, 'update_reset'));
		register_uninstall_hook($file, array($this, 'delete'));
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

	function __get($field) {
		return $this->data[$field];
	}

	function __set($field, $data) {
		$this->update_part(array($field => $data));
	}

	// Update one or more fields, leaving the others intact
	function update_part($newdata) {
		$this->update(array_merge((array) $this->data, $newdata));
	}

	// Reset option to defaults
	function reset() {
		$this->update($this->defaults);
	}

	// A combination of reset and update
	function update_reset() {
		if ( ! is_array($this->defaults) )
			return;

		$this->update(array_merge($this->defaults, $this->data));
	}

	// Update data
	function update($newdata) {
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		update_option($this->key, $this->data) or
		   add_option($this->key, $this->data);
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
