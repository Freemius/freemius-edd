<?php
	/**
	 * Plugin Name: Freemius for EDD Add-On
	 * Plugin URI:  http://freemius.com/
	 * Description: Leverage Freemius' power with EDD. The most advanced combination for your WordPress plugin business.
	 * Version:     1.0.0
	 * Author:      Freemius
	 * Author URI:  http://freemius.com
	 * License: GPL2
	 * Text Domain: freemius-for-edd Domain
	 * Path:        /languages/
	 */

	/**
	 * @package     Freemius for EDD Add-On
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! class_exists( 'FS_EDD' ) ) {
		// Include required files.
		require_once dirname( __FILE__ ) . '/start.php';

		// Include EDD driver.
		require_once WP_FS__DIR_INCLUDES . '/class-fs-driver-edd.php';

		class FS_EDD {
			function __construct() {
				new FS_Webhook( new FS_Driver_EDD() );
			}
		}

		// Init EDD webhook processor.
		new FS_EDD();
	}