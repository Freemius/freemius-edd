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
	 * Class FS_Entity_Api
	 */
	class FS_Entity_Api extends FS_Api {
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
		 * @return FS_Entity_Api
		 */
		static function instance( $scope, $id, $public_key, $is_sandbox, $secret_key = false ) {
			$identifier = md5( $scope . $id . $public_key . ( is_string( $secret_key ) ? $secret_key : '' ) . json_encode( $is_sandbox ) );

			if ( ! isset( parent::$_instances[ $identifier ] ) ) {
				if ( 0 === count( parent::$_instances ) ) {
					parent::init();
				}

				self::$_instances[ $identifier ] = new FS_Entity_Api( $scope, $id, $public_key, $secret_key, $is_sandbox );
			}

			return self::$_instances[ $identifier ];
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
			$path = isset($this->_context_plugin_id) ?
				'/plugins/' . $this->_context_plugin_id . '/' . ltrim($path, '/') :
				$path;

			return parent::call( $path, $method, $params );
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

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param number $id
		 *
		 * @return false|FS_Event
		 */
		function get_event( $id ) {
			$event = $this->get( "/events/{$id}.json" );

			return parent::is_error($event) ?
				false :
				new FS_Event($event);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param number $id
		 *
		 * @return false|FS_Event
		 */
		function get_plan( $id ) {
			$plan = $this->get( "/plans/{$id}.json" );

			return parent::is_error($plan) ?
				false :
				new FS_Plan($plan);
		}
	}