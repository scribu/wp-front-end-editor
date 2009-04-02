<?php
// PHP < 5.2
if( !function_exists('json_encode') ) :
function json_encode($array) {
	if( !is_array( $array ) )
		return false;

	$associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));

	if( $associative ) {
		$construct = array();
		foreach( $array as $key => $value ) {
			// We first copy each key/value pair into a staging array,
			// formatting each key and value properly as we go.

			// Format the key:
			if( is_numeric($key) ){
				$key = "key_$key";
			}
			$key = "'".addslashes($key)."'";

			// Format the value:
			if( is_array( $value )) {
				$value = json_encode($value);
			} else if( !is_numeric( $value ) || is_string( $value ) ) {
				$value = "'".addslashes($value)."'";
			}

			// Add to staging array:
			$construct[] = "$key: $value";
		}

		// Then we collapse the staging array into the JSON form:
		$result = "{ " . implode( ", ", $construct ) . " }";

	} else { // If the array is a vector (not associative):

		$construct = array();
		foreach( $array as $value ){

			// Format the value:
			if( is_array( $value )){
				$value = json_encode($value);
			} else if( !is_numeric( $value ) || is_string( $value ) ){
				$value = "'".addslashes($value)."'";
			}

			// Add to staging array:
			$construct[] = $value;
		}

		// Then we collapse the staging array into the JSON form:
		$result = "[ " . implode( ", ", $construct ) . " ]";
	}

	return $result;
}
endif;

