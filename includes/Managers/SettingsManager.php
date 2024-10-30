<?php
namespace Objectiv\Plugins\Checkout\Managers;

use Objectiv\Plugins\Checkout\Interfaces\SettingsGetterInterface;

/**
 * Provides standard object for accessing user-defined plugin settings
 *
 * @link checkoutwc.com
 * @since 1.0.0
 * @package Objectiv\Plugins\Checkout\Managers
 */
class SettingsManager extends SettingsManagerAbstract implements SettingsGetterInterface {

	public $prefix = '_cfwlite_';

	/**
	 * Add suffix
	 *
	 * @param string $setting_name
	 * @param array $keys
	 * @return string
	 */
	private function add_suffix( string $setting_name, array $keys = array() ): string {
		if ( empty( $keys ) ) {
			return $setting_name;
		}

		asort( $keys );

		return $setting_name . '_' . join( '', $keys );
	}

	/**
	 * Add setting
	 *
	 * @param string $setting
	 * @param mixed $value
	 * @param array $keys
	 * @return bool
	 */
	public function add_setting( string $setting, $value, array $keys = array() ): bool {
		return parent::add_setting( $this->add_suffix( $setting, $keys ), $value );
	}

	/**
	 * Update setting
	 *
	 * @param string $setting
	 * @param array|string $value
	 * @param bool $save_to_db
	 * @param array $keys
	 * @return bool
	 */
	public function update_setting( string $setting, $value, bool $save_to_db = true, array $keys = array() ): bool {
		return parent::update_setting( $this->add_suffix( $setting, $keys ), $value, $save_to_db );
	}

	/**
	 * Delete setting
	 *
	 * @param string $setting
	 * @param array $keys
	 * @return bool
	 */
	public function delete_setting( string $setting, array $keys = array() ): bool {
		return parent::delete_setting( $this->add_suffix( $setting, $keys ) );
	}

	/**
	 * Get setting
	 *
	 * @param string $setting
	 * @param array $keys
	 * @return false|mixed
	 */
	public function get_setting( string $setting, array $keys = array() ) {
		return parent::get_setting( $this->add_suffix( $setting, $keys ) );
	}

	/**
	 * Get field name
	 *
	 * @param string $setting
	 * @param array $keys
	 * @return string
	 */
	public function get_field_name( string $setting, array $keys = array() ): string {
		return parent::get_field_name( $this->add_suffix( $setting, $keys ) );
	}
}
