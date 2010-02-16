<?php

// All field classes should extend from this one or one of it's descendants
abstract class FEE_Field_Base {
	private $filter;
	private $input_type;

	private static $wrapped = array();

	/**
	 * Constructor; nothing fancy
	 * @return null
	 */
	final public function __construct($filter, $type) {
		$this->filter = $filter;
		$this->input_type = $type;

		$this->setup();
	}


	/**
	 * The type of object this field operates with
	 * @return string
	 */
	abstract public static function get_object_type();

	/**
	 * Optional actions to be done once per instance
	 * @return null
	 */
	protected function setup() {}

	/**
	 * Mark the field as editable
	 * @return string Wrapped content
	 */
	public function wrap($content, $object_id, $inline = false) {
		if ( ! $this->allow($object_id) )
			return $content;

		self::$wrapped[$this->input_type] = true;

		if ( is_null($content) )
			$content = '';

		if ( ! is_scalar($content) )
			trigger_error("scalar expected. " . gettype($content) . " given", E_USER_WARNING);

		$class = $this->filter . '-fee-field fee-field';
		$object_id = 'fee_' . esc_attr($object_id);

		$wrap_tag = ( $inline || in_array($this->input_type, array('input', 'terminput', 'image')) ) ? 'span' : 'div';

		return html("$wrap_tag id='{$object_id}' class='{$class}'", $content);
	}

	/**
	 * Retrieve the current data for the field
	 * @return string Unfiltered content
	 */
	abstract public function get($object_id);

	/**
	 * Save the data retrieved from the field
	 * @return string Saved content
	 */
	abstract public function save($object_id, $content);

	/**
	 * Check user permissions
	 * @return bool
	 */
	abstract public function check($object_id = 0);


	/**
	 * Generate a standard placeholder
	 * @return string
	 */
	protected function placeholder() {
		return '[' . __('empty', 'front-end-editor') . ']';
	}

	protected function placehold($content) {
		if ( empty($content) )
			$content = $this->placeholder();

		return $content;
	}

	/**
	 * Get the filter of the current instance
	 * @return string
	 */
	final protected function get_filter() {
		return $this->filter;
	}


	/**
	 * Allow external code to block editing for certain objects
	 * @return bool
	 */
	final public function allow($object_id) {
		return apply_filters('front_end_editor_allow_' . $this->get_object_type(), true, $object_id, $this->filter, $this->input_type);
	}

	/**
	 * Get the list of used input types
	 * @return array
	 */
	final public static function get_wrapped() {
		return self::$wrapped;
	}
}

