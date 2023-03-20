<?php
// functions
if ( ! function_exists( 'print_rr' ) ) {
	function print_rr( $content = '', $subject = null ) {
		if ( $subject ) {
			echo '<strong>' . $subject . '</strong><br>';
		}

		echo '<pre>';
		print_r( $content );
		echo '</pre>';
	}
}

if ( ! function_exists( 'siget' ) ) {
	function siget( $name, $array = null, $default = '' ) {
		if ( ! isset( $array ) ) {
			$array = $_GET;
		}

		if ( ! is_array( $array ) ) {
			return $default;
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return $default;
	}
}

if ( ! function_exists( 'sipost' ) ) {
	function sipost( $name, $do_stripslashes = true, $default = '' ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $do_stripslashes && function_exists( 'stripslashes_deep' ) ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
		}

		return $default;
	}
}

if ( ! function_exists( 'siar' ) ) {
	function siar( $array, $name, $default = '' ) {
		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return $default;
	}
}

if ( ! function_exists( 'siars' ) ) {
	function siars( $array, $name, $default = '' ) {
		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;

		foreach ( $names as $current_name ) {
			$val = siar( $val, $current_name );
		}

		return $val;
	}
}

if ( ! function_exists( 'siempty' ) ) {
	function siempty( $name, $array = null ) {
		if ( is_array( $name ) ) {
			return empty( $name );
		}

		if ( ! $array ) {
			$array = $_POST;
		}

		$val = siar( $array, $name );

		return empty( $val );
	}
}

if ( ! function_exists( 'siblank' ) ) {
	function siblank( $text ) {
		return is_array( $text ) ? empty( $text ) : ( empty( $text ) && strval( $text ) != '0' );
	}
}

if ( ! function_exists( 'siobj' ) ) {
	function siobj( $obj, $name, $default = '' ) {
		if ( isset( $obj->$name ) ) {
			return $obj->$name;
		}

		return $default;
	}
}

if ( ! function_exists( 'siexplode' ) ) {
	function siexplode( $sep, $string, $count ) {
		$ary = explode( $sep, $string );
		while ( count( $ary ) < $count ) {
			$ary[] = '';
		}

		return $ary;
	}
}

if ( ! function_exists( 'wp_js_redirect' ) ) {
	function wp_js_redirect( $location ) {
		echo '<script type="text/javascript">' . "window.location.href = '" . $location . "';" . '</script>';
	}
}
