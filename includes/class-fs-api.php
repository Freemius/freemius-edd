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

	/**
	 * Class FS_Api
	 *
	 * Wraps Freemius API SDK to handle:
	 *      1. Clock sync.
	 *      2. Fallback to HTTP when HTTPS fails.
	 */
	class FS_Api {
		/**
		 * @var FS_Api[]
		 */
		private static $_instances = array();

		/**
		 * @var FS_Option_Manager Freemius options, options-manager.
		 */
		private static $_options;

		/**
		 * @var int Clock diff in seconds between current server to API server.
		 */
		private static $_clock_diff;

		/**
		 * @var Freemius_Api
		 */
		private $_api;

		/**
		 * @var number
		 */
		private $_context_plugin_id;

		/**
		 * @param string      $scope      'developer', 'plugin', 'user' or 'install'.
		 * @param number      $id         Element's id.
		 * @param string      $public_key Public key.
		 * @param bool        $is_sandbox
		 * @param bool|string $secret_key Element's secret key.
		 *
		 * @return FS_Api
		 */
		static function instance( $scope, $id, $public_key, $is_sandbox, $secret_key = false ) {
			$identifier = md5( $scope . $id . $public_key . ( is_string( $secret_key ) ? $secret_key : '' ) . json_encode( $is_sandbox ) );

			if ( ! isset( self::$_instances[ $identifier ] ) ) {
				if ( 0 === count( self::$_instances ) ) {
					self::_init();
				}

				self::$_instances[ $identifier ] = new FS_Api( $scope, $id, $public_key, $secret_key, $is_sandbox );
			}

			return self::$_instances[ $identifier ];
		}

		private static function _init() {
			if ( ! class_exists( 'Freemius_Api' ) ) {
				require_once( dirname( __FILE__ ) . '/sdk/Freemius.php' );
			}

			self::$_options = FS_Option_Manager::get_manager( WP_FS__OPTIONS_OPTION_NAME, true );

			self::$_clock_diff = self::$_options->get_option( 'api_clock_diff', 0 );
			Freemius_Api::SetClockDiff( self::$_clock_diff );

			if ( self::$_options->get_option( 'api_force_http', false ) ) {
				Freemius_Api::SetHttp();
			}
		}

		/**
		 * @param string      $scope      'app', 'developer', 'user' or 'install'.
		 * @param number      $id         Element's id.
		 * @param string      $public_key Public key.
		 * @param bool|string $secret_key Element's secret key.
		 * @param bool        $is_sandbox
		 *
		 * @internal param \Freemius $freemius
		 */
		private function __construct( $scope, $id, $public_key, $secret_key, $is_sandbox ) {
			$this->_api = new Freemius_Api( $scope, $id, $public_key, $secret_key, $is_sandbox );
		}

		/**
		 * Find clock diff between server and API server, and store the diff locally.
		 *
		 * @param bool|int $diff
		 *
		 * @return bool|int False if clock diff didn't change, otherwise returns the clock diff in seconds.
		 */
		private function _sync_clock_diff( $diff = false ) {
			// Sync clock and store.
			$new_clock_diff = ( false === $diff ) ?
				$this->_api->FindClockDiff() :
				$diff;

			if ( $new_clock_diff === self::$_clock_diff ) {
				return false;
			}

			self::$_clock_diff = $new_clock_diff;

			// Update API clock's diff.
			$this->_api->SetClockDiff( self::$_clock_diff );

			// Store new clock diff in storage.
			self::$_options->set_option( 'api_clock_diff', self::$_clock_diff, true );

			return $new_clock_diff;
		}

		/**
		 * Override API call to enable retry with servers' clock auto sync method.
		 *
		 * @param string $path
		 * @param string $method
		 * @param array  $params
		 * @param bool   $retry Is in retry or first call attempt.
		 *
		 * @return array|mixed|string|void
		 */
		private function _call( $path, $method = 'GET', $params = array(), $retry = false ) {
			$modified_path = isset($this->_context_plugin_id) ?
				'/plugins/' . $this->_context_plugin_id . '/' . ltrim($path, '/') :
				$path;

			$result = $this->_api->Api( $modified_path, $method, $params );

			if ( null !== $result &&
			     isset( $result->error ) &&
			     'request_expired' === $result->error->code
			) {
				if ( ! $retry ) {
					$diff = isset( $result->error->timestamp ) ?
						( time() - strtotime( $result->error->timestamp ) ) :
						false;

					// Try to sync clock diff.
					if ( false !== $this->_sync_clock_diff( $diff ) ) {
						// Retry call with new synced clock.
						return $this->_call( $path, $method, $params, true );
					}
				}
			}

			if ( null !== $result && isset( $result->error ) ) {
				// Log API errors.
			}

			return $result;
		}

		/**
		 * Override API call to wrap it in servers' clock sync method.
		 *
		 * @param string $path
		 * @param string $method
		 * @param array  $params
		 *
		 * @return array|mixed|string|void
		 * @throws Freemius_Exception
		 */
		function call( $path, $method = 'GET', $params = array() ) {
			return $this->_call( $path, $method, $params );
		}

		/**
		 * Get API request URL signed via query string.
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		function get_signed_url( $path ) {
			return $this->_api->GetSignedUrl( $path );
		}

		/**
		 * @param string $path
		 *
		 * @return stdClass|mixed
		 */
		function get( $path = '/' ) {
			return $this->call( $path );
		}

		/**
		 * Test API connectivity.
		 *
		 * @since  1.0.9 If fails, try to fallback to HTTP.
		 *
		 * @return bool True if successful connectivity to the API.
		 */
		function test() {
			if ( ! function_exists( 'curl_version' ) ) {
				// cUrl extension is not active.
				return false;
			}

			$test = $this->_api->Test();

			if ( false === $test && $this->_api->IsHttps() ) {
				// Fallback to HTTP, since HTTPS fails.
				$this->_api->SetHttp();

				self::$_options->set_option( 'api_force_http', true, true );

				$test = $this->_api->Test();
			}

			return $test;
		}

		/**
		 * Ping API for connectivity test, and return result object.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return object
		 */
		function ping() {
			return $this->_api->Ping();
		}

		/**
		 * Check if valid ping request result.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 *
		 * @param mixed $pong
		 *
		 * @return bool
		 */
		function is_valid_ping( $pong ) {
			return $this->_api->Test( $pong );
		}

		function get_url( $path = '' ) {
			return $this->_api->GetUrl( $path );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 *
		 * @param mixed $result
		 *
		 * @return bool Is API result contains an error.
		 */
		function is_error( $result ) {
			return ( is_object( $result ) && isset( $result->error ) ) ||
			       is_string( $result );
		}

		/**
		 * Set context plugin to be added in all requests.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param number $id
		 */
		function set_context_plugin( $id ) {
			$this->_context_plugin_id = $id;
		}
	}