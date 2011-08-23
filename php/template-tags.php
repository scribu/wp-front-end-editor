<?php

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string $type The type of UI. Can be 'input', 'checkbox', 'select', 'textarea' or 'rich'
 * @param bool $echo Wether to echo or return the result
 */
function editable_post_meta( $post_id, $key, $type = 'input', $echo = true ) {
	$data = get_editable_post_meta( $post_id, $key, $type, true );

	if ( !$echo ) {
		return $data;
	}

	echo $data;
}

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string $type The type of UI. Can be 'input', 'textarea' or 'rich'
 * @param bool $single Wether it's a custom field with a single value or multiple values
 */
function get_editable_post_meta( $post_id, $key, $type = 'input', $single = false ) {
	$content = get_post_meta( $post_id, $key, $single );

	return apply_filters( 'post_meta', $content, $post_id, $key, $type, $single );
}

/**
 * @param array $args:
 * - 'key' (string) The option key
 * - 'theme_option' (bool) Wether it's arbitrary theme text, or a core site option like 'description' or 'time_format'
 * - 'default' (mixed) The default value
 * - 'type' (string) The type of UI. Can be 'input', 'textarea' or 'rich'
 * - 'echo' (bool) Wether to echo or return the result
 */
function editable_option( $args ) {
	if ( !is_array( $args ) ) {
		_deprecated_argument( __FUNCTION__, '1.9.5', 'Passing individual arguments is deprecated. Use an associative array of arguments instead.' );
		$argv = func_get_args();
		$args = scbUtil::numeric_to_assoc( $argv, array( 'key', 'theme_option', 'type', 'echo' ) );
	}

	extract( wp_parse_args( $args, array(
		'key' => '',
		'theme_option' => true,
		'default' => false,
		'type' => 'input',
		'echo' => true
	) ) );

	if ( empty( $key ) )
		return false;

	if ( $theme_option )
		$key = "editable_option_$key";

	$output = apply_filters( 'editable_option', get_option( $key, $default ), $key, $type );

	if ( $echo )
		echo $output;

	return $output;
}

/**
 * @param string $key The option key
 * @param string $default_url An absolute URL to be used as the default
 * @param string|array $extra_attr Extra HTML attributes for the <img> tag
 * @param bool $echo Wether to echo or return the result
 */
function editable_image( $key, $default_url, $extra_attr = '', $echo = true ) {
	$attr = wp_parse_args( $extra_attr, array(
		'id' => $key
	) );

	if ( !$src = FEE_Field_Image::get( compact( 'key' ) ) )
		$src = $default_url;
	$attr['src'] = $src;

	$attr_str = '';
	foreach ( $attr as $a_key => $a_value ) {
		$a_key = trim( strip_tags( $a_key ) );
		$a_value = trim( esc_attr( $a_value ) );

		if ( empty( $a_key ) )
			continue;

		$attr_str .= " $a_key='$a_value'";
	}

	$attr_str = ltrim( $attr_str );

	$img = apply_filters( 'editable_image', "<img $attr_str />", $key, $default_url );

	if ( $echo )
		echo $img;

	return $img;
}

