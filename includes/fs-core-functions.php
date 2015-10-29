<?php
	/**
	 * @package     Freemius for EDD Add-On
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/* Url.
	--------------------------------------------------------------------------------------------*/
	function fs_get_url_daily_cache_killer() {
		return date( '\YY\Mm\Dd' );
	}

	/* Templates / Views.
	--------------------------------------------------------------------------------------------*/
	function fs_get_template_path( $path ) {
		return WP_FS__DIR_TEMPLATES . '/' . trim( $path, '/' );
	}

	function fs_include_template( $path, &$params = null ) {
		$VARS = &$params;
		include( fs_get_template_path( $path ) );
	}

	function fs_include_once_template( $path, &$params = null ) {
		$VARS = &$params;
		include_once( fs_get_template_path( $path ) );
	}

	function fs_require_template( $path, &$params = null ) {
		$VARS = &$params;
		require( fs_get_template_path( $path ) );
	}

	function fs_require_once_template( $path, &$params = null ) {
		$VARS = &$params;
		require_once( fs_get_template_path( $path ) );
	}

	function fs_get_template( $path, &$params = null ) {
		ob_start();

		$VARS = &$params;
		require_once( fs_get_template_path( $path ) );

		return ob_get_clean();
	}

	function __fs( $key ) {
		global $fs_text;

		if ( ! isset( $fs_text ) ) {
			require_once( dirname( __FILE__ ) . '/i18n.php' );
		}

		return isset( $fs_text[ $key ] ) ? $fs_text[ $key ] : $key;
	}

	function _efs( $key ) {
		echo __fs( $key );
	}

	/* Scripts and styles including.
	--------------------------------------------------------------------------------------------*/
	function fs_enqueue_local_style( $handle, $path, $deps = array(), $ver = false, $media = 'all' ) {
		wp_enqueue_style( $handle, plugins_url( plugin_basename( WP_FS__DIR_CSS . '/' . trim( $path, '/' ) ) ), $deps, $ver, $media );
	}

	function fs_enqueue_local_script( $handle, $path, $deps = array(), $ver = false, $in_footer = 'all' ) {
		wp_enqueue_script( $handle, plugins_url( plugin_basename( WP_FS__DIR_JS . '/' . trim( $path, '/' ) ) ), $deps, $ver, $in_footer );
	}

	/* Request handlers.
	--------------------------------------------------------------------------------------------*/
	/**
	 * @param string $key
	 * @param mixed  $def
	 *
	 * @return mixed
	 */
	function fs_request_get( $key, $def = false ) {
		return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : $def;
	}

	function fs_request_has( $key ) {
		return isset( $_REQUEST[ $key ] );
	}

	function fs_request_get_bool( $key, $def = false ) {
		return ( isset( $_REQUEST[ $key ] ) && ( 1 == $_REQUEST[ $key ] || 'true' === strtolower( $_REQUEST[ $key ] ) ) ) ? true : $def;
	}

	function fs_request_is_post() {
		return ( 'post' === strtolower( $_SERVER['REQUEST_METHOD'] ) );
	}

	function fs_request_is_get() {
		return ( 'get' === strtolower( $_SERVER['REQUEST_METHOD'] ) );
	}

	function fs_get_action( $action_key = 'action' ) {
		if ( ! empty( $_REQUEST[ $action_key ] ) ) {
			return strtolower( $_REQUEST[ $action_key ] );
		}

		if ( 'action' == $action_key ) {
			$action_key = 'fs_action';

			if ( ! empty( $_REQUEST[ $action_key ] ) ) {
				return strtolower( $_REQUEST[ $action_key ] );
			}
		}

		return false;
	}

	function fs_request_is_action( $action, $action_key = 'action' ) {
		return ( strtolower( $action ) === fs_get_action( $action_key ) );
	}

	function fs_is_plugin_page( $menu_slug ) {
		return ( is_admin() && $_REQUEST['page'] === $menu_slug );
	}

	/* Core Redirect (copied from BuddyPress).
	--------------------------------------------------------------------------------------------*/
	/**
	 * Redirects to another page, with a workaround for the IIS Set-Cookie bug.
	 *
	 * @link  http://support.microsoft.com/kb/q176113/
	 * @since 1.5.1
	 * @uses  apply_filters() Calls 'wp_redirect' hook on $location and $status.
	 *
	 * @param string $location The path to redirect to
	 * @param int    $status   Status code to use
	 *
	 * @return bool False if $location is not set
	 */
	function fs_redirect( $location, $status = 302 ) {
		global $is_IIS;

		if ( headers_sent() ) {
			return false;
		}

		if ( ! $location ) // allows the wp_redirect filter to cancel a redirect
		{
			return false;
		}

		$location = fs_sanitize_redirect( $location );

		if ( $is_IIS ) {
			header( "Refresh: 0;url=$location" );
		} else {
			if ( php_sapi_name() != 'cgi-fcgi' ) {
				status_header( $status );
			} // This causes problems on IIS and some FastCGI setups
			header( "Location: $location" );
		}

		return true;
	}

	/**
	 * Sanitizes a URL for use in a redirect.
	 *
	 * @since 2.3
	 *
	 * @param string $location
	 *
	 * @return string redirect-sanitized URL
	 */
	function fs_sanitize_redirect( $location ) {
		$location = preg_replace( '|[^a-z0-9-~+_.?#=&;,/:%!]|i', '', $location );
		$location = fs_kses_no_null( $location );

		// remove %0d and %0a from location
		$strip = array( '%0d', '%0a' );
		$found = true;
		while ( $found ) {
			$found = false;
			foreach ( (array) $strip as $val ) {
				while ( strpos( $location, $val ) !== false ) {
					$found    = true;
					$location = str_replace( $val, '', $location );
				}
			}
		}

		return $location;
	}

	/**
	 * Removes any NULL characters in $string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function fs_kses_no_null( $string ) {
		$string = preg_replace( '/\0+/', '', $string );
		$string = preg_replace( '/(\\\\0)+/', '', $string );

		return $string;
	}

	if ( function_exists( 'wp_normalize_path' ) ) {
		/**
		 * Normalize a filesystem path.
		 *
		 * Replaces backslashes with forward slashes for Windows systems, and ensures
		 * no duplicate slashes exist.
		 *
		 * @param string $path Path to normalize.
		 *
		 * @return string Normalized path.
		 */
		function fs_normalize_path( $path ) {
			return wp_normalize_path( $path );
		}
	} else {
		function fs_normalize_path( $path ) {
			$path = str_replace( '\\', '/', $path );
			$path = preg_replace( '|/+|', '/', $path );

			return $path;
		}
	}

	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		/**
		 *
		 * @param int $min
		 * @param int $max
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @link   http://stackoverflow.com/questions/2593807/md5uniqid-makes-sense-for-random-unique-tokens
		 *
		 * @return int
		 */
		function fs_crypto_rand_secure( $min = 0, $max = - 1 ) {
			if ( - 1 === $max ) {
				// Defaults to random max.
				$max = mt_getrandmax();
			}

			$range = $max - $min;
			if ( $range < 0 ) {
				return $min;
			} // not so random...
			$log    = log( $range, 2 );
			$bytes  = (int) ( $log / 8 ) + 1; // length in bytes
			$bits   = (int) $log + 1; // length in bits
			$filter = (int) ( 1 << $bits ) - 1; // set all lower bits to 1
			do {
				$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
				$rnd = $rnd & $filter; // discard irrelevant bits
			} while ( $rnd >= $range );

			return $min + $rnd;
		}
	} else {
		function fs_crypto_rand_secure( $min = 0, $max = - 1 ) {
			if ( - 1 === $max ) {
				// Defaults to random max.
				$max = mt_getrandmax();
			}

			return mt_rand( $min, $max );
		}
	}