<?php

/**
 * Base class, from which all editable fields should descend
 */
abstract class FEE_Field_Base {

	protected $filter;
	protected $input_type;

	/**
	 * Keeps track of all wrapped fields.
	 */
	private static $wrapped = array();

	/**
	 * Keeps track of when the placeholder is used.
	 */
	protected $was_placeholded;

	/**
	 * Constructor; nothing fancy
	 */
	final public function __construct( $filter, $type ) {
		$this->filter = $filter;
		$this->input_type = $type;

		$this->setup();
	}

	/**
	 * Returns the type of object this field operates with.
	 *
	 * @return string
	 */
	public static function get_object_type() {}

	/**
	 * Optional actions to be done once per field type.
	 *
	 * @return null
	 */
	protected function setup() {}

	/**
	 * Mark the field as editable.
	 *
	 * @param string $content Filtered content
	 * @param mixed $data Additional data like an object id etc.
	 *
	 * @return string Wrapped content
	 */
	public function wrap( $content = null, $data = null ) {
		if ( !$this->allow( $data ) )
			return $content;

		if ( isset( $data['ui'] ) ) {
			if ( is_array( $data['ui'] ) )
				$data = array_merge( $data['ui'], $data );
			else
				$data['type'] = $data['ui'];

			unset( $data['ui'] );
		}

		if ( !isset( $data['type'] ) )
			$data['type'] = $this->input_type;

		if ( 'rich' == $data['type'] && !FEE_Core::$options->rich )
			$data['type'] = 'textarea';

		self::$wrapped[ $data['type'] ] = true;

		if ( is_null( $content ) )
			$content = '';

		if ( !is_scalar( $content ) )
			trigger_error( "scalar expected. " . gettype( $content ) . " given", E_USER_WARNING );

		$data = apply_filters( 'front_end_editor_wrap', $data, $this->filter );

		$data['filter'] = $this->filter;

		$data_attr = array();
		foreach ( $data as $key => $value ) {
			if ( !is_scalar( $value ) )
				$value = json_encode( $value );

			$data_attr[ "data-$key" ] = $value;
		}

		$data_attr['class'] = 'fee-field';

		if ( $this->was_placeholded )
			$data_attr['title'] = FEE_Core::get_title( $this->filter );

		$wrap_tag = in_array( $data['type'], array( 'textarea', 'rich', 'widget' ) ) ? 'div' : 'span';

		if ( 'div' == $wrap_tag )
			$data_attr['class'] .= ' fee-clearfix';

		return html( $wrap_tag, $data_attr, $content );
	}

	/**
	 * Retrieve the current data for the field.
	 *
	 * @return string Raw content
	 */
	abstract public function get( $data );

	/**
	 * Save the data retrieved from the field.
	 */
	abstract public function save( $data, $content );

	/**
	 * Return the content to be displayed.
	 *
	 * @return mixed Filtered content
	 */
	abstract public function get_filtered( $data );

	/**
	 * Check user capabilities.
	 *
	 * @return bool
	 */
	abstract public function check( $data = 0 );


	/**
	 * Generate a standard placeholder.
	 *
	 * @return string
	 */
	protected function placeholder() {
		return '[' . __( 'empty', 'front-end-editor' ) . ']';
	}

	protected function placehold( $content ) {
		if ( '' === (string) $content ) {
			$content = $this->placeholder();
			$this->was_placeholded = true;
		} else {
			$this->was_placeholded = false;
		}

		return $content;
	}

	/**
	 * Allow external code to block editing for certain objects
	 *
	 * @return bool
	 */
	final public function allow( $data ) {
		return apply_filters( 'front_end_editor_allow_' . $this->get_object_type(), true, $data, $this->filter, $this->input_type );
	}

	/**
	 * Get the list of used input types
	 *
	 * @return array
	 */
	final public static function get_wrapped() {
		return self::$wrapped;
	}
}

