<?php

namespace Objectiv\Plugins\Checkout;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * This class is a holding tank for settings defaults that have yet to find a home
 *
 * @deprecated
 */
class DefaultSettingsSetter {
	public function __construct() {}

	public function init() {
		$settings_manager = SettingsManager::instance();

		$settings_manager->add_setting( 'enable', 'no' );
		$settings_manager->add_setting( 'login_style', 'enhanced' );
		$settings_manager->add_setting( 'cart_item_link', 'disabled' );
		$settings_manager->add_setting( 'cart_item_data_display', 'short' );
		$settings_manager->add_setting( 'enable_order_notes', 'no' );
		$settings_manager->add_setting( 'active_template', 'default' );
		$settings_manager->add_setting( 'allow_checkout_field_editor_address_modification', 'no' );
		$settings_manager->add_setting( 'enable_elementor_pro_support', 'no' );
		$settings_manager->add_setting( 'enable_beaver_themer_support', 'no' );
		$settings_manager->add_setting( 'template_loader', 'redirect' );

		foreach ( cfw_get_available_templates() as $template ) {
			$breadcrumb_completed_text_color   = '#7f7f7f';
			$breadcrumb_current_text_color     = '#333333';
			$breadcrumb_next_text_color        = '#7f7f7f';
			$breadcrumb_completed_accent_color = '#333333';
			$breadcrumb_current_accent_color   = '#333333';
			$breadcrumb_next_accent_color      = '#333333';

			if ( $template->get_slug() === 'glass' ) {
				$breadcrumb_current_text_color   = $settings_manager->get_setting( 'button_color', array( 'glass' ) );
				$breadcrumb_current_accent_color = $settings_manager->get_setting( 'button_color', array( 'glass' ) );
				$breadcrumb_next_text_color      = '#dfdcdb';
				$breadcrumb_next_accent_color    = '#dfdcdb';

			} elseif ( $template->get_slug() === 'futurist' ) {
				$breadcrumb_completed_text_color   = '#222222';
				$breadcrumb_current_text_color     = '#222222';
				$breadcrumb_next_text_color        = '#222222';
				$breadcrumb_completed_accent_color = '#222222';
				$breadcrumb_current_accent_color   = '#222222';
				$breadcrumb_next_accent_color      = '#222222';
			}

			$settings_manager->add_setting( 'breadcrumb_completed_text_color', $breadcrumb_completed_text_color, array( $template->get_slug() ) );
			$settings_manager->add_setting( 'breadcrumb_current_text_color', $breadcrumb_current_text_color, array( $template->get_slug() ) );
			$settings_manager->add_setting( 'breadcrumb_next_text_color', $breadcrumb_next_text_color, array( $template->get_slug() ) );
			$settings_manager->add_setting( 'breadcrumb_completed_accent_color', $breadcrumb_completed_accent_color, array( $template->get_slug() ) );
			$settings_manager->add_setting( 'breadcrumb_current_accent_color', $breadcrumb_current_accent_color, array( $template->get_slug() ) );
			$settings_manager->add_setting( 'breadcrumb_next_accent_color', $breadcrumb_next_accent_color, array( $template->get_slug() ) );
		}

		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			$settings_manager->add_setting( 'logo_attachment_id', $custom_logo_id );
		}

		$settings_manager->set_settings_obj( $settings_manager->settings );
	}
}
