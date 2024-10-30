<?php

namespace Objectiv\Plugins\Checkout\Admin;

use Exception;

class DataUpgrader {
	public function __construct() {}

	/**
	 * Init
	 *
	 * @throws Exception
	 */
	public function init() {
		$db_version = get_option( 'cfw_db_version', false );

		// Don't run upgrades for first time activators
		if ( ! $db_version ) {
			$this->update_version();
			return;
		}

		// Prevents data migrations from running on every page load
		// If already at the prescribed version, bail
		if ( CFW_VERSION === $db_version ) {
			return;
		}

		do_action( 'cfw_before_plugin_data_upgrades', $db_version );

		// Upgrades go here

		$this->update_version();

		do_action( 'cfw_after_plugin_data_upgrades', $db_version );
	}

	private function update_version() {
		$db_version = get_option( 'cfw_db_version', '0.0.0' );

		// Only update db version if the current version is greater than the db version
		if ( version_compare( CFW_VERSION, $db_version, '>' ) ) {
			update_option( 'cfw_db_version', CFW_VERSION );
		}
	}
}
