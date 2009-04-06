<?php

class scbOptions {
	var $key;
	var $defaults;
	var $data;

	function __construct($key) {
		$this->key = $key;
		$this->data = get_option($this->key);
	}

	// PHP < 5
	function scbOptions($key) {
		$this->__construct($key);
	}

	function setup($file, $defaults) {
		$this->defaults = $defaults;

		register_activation_hook($file, array($this, 'reset'), false);
		register_uninstall_hook($file, array($this, 'delete'));
	}

	// Get all data or a certain field
	function get($field = '') {
		if ( empty($field) )
			return $this->data;

		return @$this->data[$field];
	}

	function __get($field) {
		return $this->get($field);
	}

	function __set($field, $data) {
		$this->data[$field] = $data;
	}

	// Update a portion of the data
	function update_part($newdata) {
		$this->update(array_merge((array) $this->data, (array) $newdata));
	}

	// Update option
	function update($newdata) {
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		   add_option($this->key, $this->data) or
		update_option($this->key, $this->data);
	}

	// Reset option to defaults
	function reset($override = true) {
		if ( !$override && is_array($this->defaults) && is_array($this->data) )
			$newdata = array_merge($this->defaults, $this->data);
		else
			$newdata = $this->defaults;

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
