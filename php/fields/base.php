<?php

/**
 * Base class, from which all editable fields should descend
 */
abstract class FEE_Field_Base {

	private $filter;
	protected $input_type;

	/**
	 * Keeps track of all wrapped fields
	 */
	private static $wrapped = array();

	/**
	 * Constructor; nothing fancy
	 */
	final public function __construct( $filter, $type ) {
		$this->filter = $filter;
		$this->input_type = $type;

		$this->setup();
	}

	/**
	 * The type of object this field operates with
	 *
	 * @return string
	 */
	abstract public static function get_object_type();

	/**
	 * Optional actions to be done once per instance
	 *
	 * @return null
	 */
	protected function setup() {}

	/**
	 * Mark the field as editable
	 *
	 * @param string $content Filtered content
	 * @param mixed $data Additional data like an object id etc.
	 *
	 * @return string Wrapped content
	 */
	public function wrap( $content, $data ) {
		if ( !$this->allow( $data ) )
			return $content;

		$data = wp_parse_args( $data, array(
			'type' => $this->input_type
		) );

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

		$wrap_tag = in_array( $data['type'], array( 'textarea', 'rich', 'widget' ) ) ? 'div' : 'span';

		return html( $wrap_tag, $data_attr, $content );
	}

	/**
	 * Retrieve the current data for the field
	 *
	 * @return string Raw content
	 */
	abstract public function get( $data );

	/**
	 * Save the data retrieved from the field
	 *
	 * @return string Saved content
	 */
	abstract public function save( $data, $content );

	/**
	 * Check user capabilities
	 *
	 * @return bool
	 */
	abstract public function check( $data = 0 );


	/**
	 * Generate a standard placeholder
	 * @return string
	 */
	protected function placeholder() {
		return '[' . __( 'empty', 'front-end-editor' ) . ']';
	}

	protected function placehold( $content ) {
		if ( '' === (string) $content )
			$content = $this->placeholder();

		return $content;
	}

	final protected static function convert_type( $type ) {
		if ( !is_array( $type ) ) {
			$type = compact( 'type' );
		}

		if ( !isset( $type['values'] ) ) {
			switch ( $type['type'] ) {
				case 'checkbox':
					$type['values'] = array(
						false => __( 'no', 'front-end-editor' ),
						true  => __( 'yes', 'front-end-editor' ),
					);
					break;
				case 'select':
					throw new WP_Error( 'incomplete type definition' );
				default:
					$type['values'] = array();
			}
		}

		return $type;
	}


	/**
	 * Get the filter of the current instance
	 *
	 * @return string
	 */
	final protected function get_filter() {
		return $this->filter;
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

