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

	// Configuration should be loaded first.
	require_once dirname( __FILE__ ) . '/config.php';

	// Logger must be loaded before any other.
	require_once WP_FS__DIR_INCLUDES . '/class-fs-logger.php';

	require_once WP_FS__DIR_INCLUDES . '/class-fs-option-manager.php';
	require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-entity.php';
	require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-entity-map.php';
	require_once WP_FS__DIR_INCLUDES . '/class-fs-entity-mapper.php';
	require_once WP_FS__DIR_INCLUDES . '/class-fs-driver-abstract.php';
	require_once WP_FS__DIR_INCLUDES . '/class-fs-webhook.php';

	function fs_dump_log() {
		FS_Logger::dump();
	}