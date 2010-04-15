<?php

// Usage: http://scribu.net/wordpress/scb-framework/scb-options.html

class scbOptions {
	protected $defaults;	// the default value(s)

	protected $key;			// the option name
	protected $data;		// the option value

	public $wp_filter_id;	// used by WP hooks

	/**
	 * Create a new set of options
	 *
	 * @param string $key Option name
	 * @param string $file Reference to main plugin file
	 * @param array $defaults An associative array of default values (optional)
	 */
	public function __construct($key, $file, $defaults = '') {
		$this->key = $key;
		$this->defaults = $defaults;
		$this->data = get_option($this->key);

		if ( is_array($this->defaults) ) {
			$this->data = (array) $this->data;

			register_activation_hook($file, array($this, '_update_reset'));
		}

		scbUtil::add_uninstall_hook($file, array($this, 'delete'));
	}

	/**
	 * Get option values for one, many or all fields
	 *
	 * @param string|array $field The field(s) to get
	 * @return mixed Whatever is in those fields
	 */
	public function get($field = '') {
		return $this->_get($field, $this->data);
	}

	/**
	 * Get default values for one, many or all fields
	 *
	 * @param string|array $field The field(s) to get
	 * @return mixed Whatever is in those fields
	 */
	public function get_defaults($field = '') {
		return $this->_get($field, $this->defaults);
	}

	/**
	 * Set all data fields, certain fields or a single field
	 *
	 * @param string|array $field The field to update or an associative array
	 * @param mixed $value The new value (ignored if $field is array)
	 * @return null
	 */
	public function set($field, $value = '') {
		if ( is_array($field) )
			$newdata = $field;
		else
			$newdata = array($field => $value);

		$this->update(array_merge($this->data, $newdata));
	}

	/**
	 * Reset option to defaults
	 *
	 * @return null
	 */
	public function reset() {
		$this->update($this->defaults);
	}

	/**
	 * Remove any keys that are not in the defaults array
	 *
	 * @return bool
	 */
	public function cleanup() {
		$r = array();

		if ( ! is_array($this->defaults) )
			return false;

		foreach ( array_keys($this->defaults) as $key )
			$r[$key] = $this->data[$key];

		$this->update($r);

		return true;
	}

	/**
	 * Update raw data
	 *
	 * @param mixed $newdata
	 * @return null
	 */
	public function update($newdata) {
		$this->data = $newdata;

		update_option($this->key, $this->data);
	}

	/**
	 * Delete the option
	 *
	 * @return null
	 */
	public function delete() {
		unset($this->data);

		delete_option($this->key);
	}


//_____INTERNAL METHODS_____


	// Add new fields with their default values
	function _update_reset() {
		$this->update(array_merge($this->defaults, $this->data));
	}

	// Get one, more or all fields from an array
	private function _get($field, $data) {
		if ( empty($field) )
			return $data;

		if ( is_string($field) )
			return $data[$field];

		foreach ( $field as $key )
			if ( isset($data[$key]) )
				$result[] = $data[$key];

		return $result;
	}

	// Magic method: $options->field
	function __get($field) {
		return $this->data[$field];
	}

	// Magic method: $options->field = $value
	function __set($field, $value) {
		$this->set($field, $value);
	}

	// Magic method: isset($options->field)
	function __isset($field) {
		return isset($this->data[$field]);
	}
}

