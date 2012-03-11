<?php

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string|array $ui The type of UI.
 * @param bool $echo Wether to echo or return the result
 */
function editable_post_meta( $post_id, $key, $ui = 'input', $echo = true ) {
	$data = get_editable_post_meta( $post_id, $key, $ui, true );

	if ( !$echo ) {
		return $data;
	}

	echo $data;
}

/**
 * @param int $post_id The id of a post
 * @param string $key The meta key
 * @param string|array $ui The type of UI.
 * @param bool $single Wether it's a custom field with a single value or multiple values
 */
function get_editable_post_meta( $post_id, $key, $ui = 'input', $single = false ) {
	$content = get_post_meta( $post_id, $key, $single );

	return apply_filters( 'post_meta', $content, $post_id, $key, $ui, $single );
}

/**
 * @param array $args:
 * - 'key' (string) The option key
 * - 'theme_option' (bool) Wether it's arbitrary theme text, or a core site option like 'description' or 'time_format'
 * - 'default' (mixed) The default value
 * - 'type' (string|array) The type of UI.
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
		'values' => array(),
		'echo' => true
	) ) );

	if ( empty( $key ) )
		return false;

	if ( $theme_option )
		$key = "editable_option_$key";

	$output = apply_filters( 'editable_option', get_option( $key, $default ), $key, compact( 'type', 'values' ) );

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

	$img = apply_filters( 'editable_image', html( 'img', $attr ), $key, $default_url );

	if ( $echo )
		echo $img;

	return $img;
}

/**
 * @param string $post_type The type of the post (Default: 'post')
 * @param bool $replace_first Wether to replace the first post in the loop or not (Default: true)
 */
function fee_inject_dummy_post( $post_type = 'post', $replace_first = true ) {
	global $wp_query;

	require_once( ABSPATH . '/wp-admin/includes/post.php' );

	$post = get_default_post_to_edit( $post_type, true );

	if ( $replace_first )
		$wp_query->posts[0] = $post;
	else
		array_unshift( $wp_query->posts, $post );
}

