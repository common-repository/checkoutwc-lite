<?php

namespace Objectiv\Plugins\Checkout;

use Objectiv\Plugins\Checkout\Managers\TemplatesManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * The core plugin class.
 *
 * In previous iterations of the plugin this class served as the middle man access point to all plugin functionality.
 * This has been deprecated and the remaining functions serve as legacy functions to be removed at a later date.
 *
 * @link checkoutwc.com
 * @since 1.0.0
 * @package Objectiv\Plugins\Checkout
 * @deprecated
 */

class Main extends SingletonAbstract {
	/**
	 * Returns the template manager
	 *
	 * @since 1.0.0
	 * @deprecated 5.0.0
	 * @return TemplatesManager
	 */
	public function get_templates_manager(): TemplatesManager {
		_deprecated_function( 'Main::get_templates_manager', 'CheckoutWC 5.0.0', 'TemplatesManager::instance' );
		return TemplatesManager::instance();
	}

	/**
	 * Returns the settings manager
	 *
	 * @deprecated
	 */
	public function get_settings_manager(): SettingsManager {
		_deprecated_function( 'Main::get_settings_manager', 'CheckoutWC 5.0.0', 'SettingsManager::instance()' );
		return SettingsManager::instance();
	}

	/**
	 * Get the address fields augmenter object
	 *
	 * @return AddressFieldsAugmenter The form object
	 * @deprecated 5.0.0
	 * @since 1.1.5
	 */
	public function get_form(): AddressFieldsAugmenter {
		_deprecated_function( 'Main::get_form', 'CheckoutWC 5.0.0', 'FormAugmenter::instance' );
		return AddressFieldsAugmenter::instance();
	}

	/**
	 * Is checkout?
	 *
	 * @deprecated
	 * @return mixed|void
	 */
	public static function is_checkout(): bool {
		_deprecated_function( 'Main::is_checkout', 'CheckoutWC 5.0.0', 'cfw_is_checkout' );
		return cfw_is_checkout();
	}

	/**
	 * Is cfw page?
	 *
	 * @deprecated
	 * @return bool
	 */
	public static function is_cfw_page(): bool {
		_deprecated_function( 'Main::is_cfw_page', 'CheckoutWC 5.0.0', 'is_cfw_page()' );
		return is_cfw_page();
	}
}
