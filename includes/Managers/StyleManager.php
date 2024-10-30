<?php

namespace Objectiv\Plugins\Checkout\Managers;

/**
 * Handle CSS custom properties and custom styles
 *
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Managers
 */
class StyleManager {
	public static function get_css_custom_property_overrides(): string {
		$settings_manager                  = SettingsManager::instance();
		$active_template                   = cfw_get_active_template();
		$active_theme                      = $active_template->get_slug();
		$body_background_color             = $settings_manager->get_setting( 'body_background_color', array( $active_theme ) );
		$body_text_color                   = $settings_manager->get_setting( 'body_text_color', array( $active_theme ) );
		$header_background_color           = $settings_manager->get_setting( 'header_background_color', array( $active_theme ) );
		$footer_background_color           = $settings_manager->get_setting( 'footer_background_color', array( $active_theme ) );
		$summary_bg_color                  = $settings_manager->get_setting( 'summary_background_color', array( $active_theme ) );
		$summary_mobile_bg_color           = $settings_manager->get_setting( 'summary_mobile_background_color', array( $active_theme ) );
		$summary_text_color                = $settings_manager->get_setting( 'summary_text_color', array( $active_theme ) );
		$summary_link_color                = $settings_manager->get_setting( 'summary_link_color', array( $active_theme ) );
		$header_text_color                 = $settings_manager->get_setting( 'header_text_color', array( $active_theme ) );
		$footer_text_color                 = $settings_manager->get_setting( 'footer_color', array( $active_theme ) );
		$body_link_color                   = $settings_manager->get_setting( 'link_color', array( $active_theme ) );
		$primary_button_bg_color           = $settings_manager->get_setting( 'button_color', array( $active_theme ) );
		$primary_button_text_color         = $settings_manager->get_setting( 'button_text_color', array( $active_theme ) );
		$primary_button_hover_bg_color     = $settings_manager->get_setting( 'button_hover_color', array( $active_theme ) );
		$primary_button_hover_text_color   = $settings_manager->get_setting( 'button_text_hover_color', array( $active_theme ) );
		$secondary_button_bg_color         = $settings_manager->get_setting( 'secondary_button_color', array( $active_theme ) );
		$secondary_button_text_color       = $settings_manager->get_setting( 'secondary_button_text_color', array( $active_theme ) );
		$secondary_button_hover_bg_color   = $settings_manager->get_setting( 'secondary_button_hover_color', array( $active_theme ) );
		$secondary_button_hover_text_color = $settings_manager->get_setting( 'secondary_button_text_hover_color', array( $active_theme ) );
		$cart_item_background_color        = $settings_manager->get_setting( 'cart_item_quantity_color', array( $active_theme ) );
		$cart_item_text_color              = $settings_manager->get_setting( 'cart_item_quantity_text_color', array( $active_theme ) );
		$breadcrumb_completed_text_color   = $settings_manager->get_setting( 'breadcrumb_completed_text_color', array( $active_theme ) );
		$breadcrumb_current_text_color     = $settings_manager->get_setting( 'breadcrumb_current_text_color', array( $active_theme ) );
		$breadcrumb_next_text_color        = $settings_manager->get_setting( 'breadcrumb_next_text_color', array( $active_theme ) );
		$breadcrumb_completed_accent_color = $settings_manager->get_setting( 'breadcrumb_completed_accent_color', array( $active_theme ) );
		$breadcrumb_current_accent_color   = $settings_manager->get_setting( 'breadcrumb_current_accent_color', array( $active_theme ) );
		$breadcrumb_next_accent_color      = $settings_manager->get_setting( 'breadcrumb_next_accent_color', array( $active_theme ) );
		$logo_url                          = cfw_get_logo_url();

		/**
		 * Filter the CSS custom property overrides
		 *
		 * @since 5.0.0
		 * @var array $overrides The CSS custom properties
		 */
		$custom_properties = apply_filters(
			'cfw_custom_css_properties',
			array(
				'--cfw-body-background-color'              => $body_background_color,
				'--cfw-body-text-color'                    => $body_text_color,
				'--cfw-header-background-color'            => $active_template->supports( 'header-background' ) ? $header_background_color : $body_background_color,
				'--cfw-header-bottom-margin'               => strtolower( $header_background_color ) !== strtolower( $body_background_color ) ? '2em' : false,
				'--cfw-footer-background-color'            => $active_template->supports( 'footer-background' ) ? $footer_background_color : $body_background_color,
				'--cfw-footer-top-margin'                  => '#ffffff' !== strtolower( $footer_background_color ) ? '2em' : false,
				'--cfw-cart-summary-background-color'      => $active_template->supports( 'summary-background' ) ? $summary_bg_color : false,
				'--cfw-cart-summary-mobile-background-color' => $summary_mobile_bg_color,
				'--cfw-cart-summary-text-color'            => $active_template->supports( 'summary-background' ) ? $summary_text_color : false,
				'--cfw-cart-summary-link-color'            => $summary_link_color,
				'--cfw-header-text-color'                  => $header_text_color,
				'--cfw-footer-text-color'                  => $footer_text_color,
				'--cfw-body-link-color'                    => $body_link_color,
				'--cfw-buttons-primary-background-color'   => $primary_button_bg_color,
				'--cfw-buttons-primary-text-color'         => $primary_button_text_color,
				'--cfw-buttons-primary-hover-background-color' => $primary_button_hover_bg_color,
				'--cfw-buttons-primary-hover-text-color'   => $primary_button_hover_text_color,
				'--cfw-buttons-secondary-background-color' => $secondary_button_bg_color,
				'--cfw-buttons-secondary-text-color'       => $secondary_button_text_color,
				'--cfw-buttons-secondary-hover-background-color' => $secondary_button_hover_bg_color,
				'--cfw-buttons-secondary-hover-text-color' => $secondary_button_hover_text_color,
				'--cfw-cart-summary-item-quantity-background-color' => $cart_item_background_color,
				'--cfw-cart-summary-item-quantity-text-color' => $cart_item_text_color,
				'--cfw-breadcrumb-completed-text-color'    => $breadcrumb_completed_text_color,
				'--cfw-breadcrumb-current-text-color'      => $breadcrumb_current_text_color,
				'--cfw-breadcrumb-next-text-color'         => $breadcrumb_next_text_color,
				'--cfw-breadcrumb-completed-accent-color'  => $breadcrumb_completed_accent_color,
				'--cfw-breadcrumb-current-accent-color'    => $breadcrumb_current_accent_color,
				'--cfw-breadcrumb-next-accent-color'       => $breadcrumb_next_accent_color,
				'--cfw-logo-url'                           => "url({$logo_url})",
			)
		);

		$output = ':root, body { ' . PHP_EOL;

		foreach ( $custom_properties as $custom_property => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$output .= "	{$custom_property}: {$value};" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$output .= ' }' . PHP_EOL;

		return $output;
	}

	public static function get_custom_css(): string {
		$settings_manager = SettingsManager::instance();
		$active_template  = cfw_get_active_template();
		$custom_css       = $settings_manager->get_setting( 'custom_css', array( $active_template->get_slug() ) );

		$output = 'html { background: var(--cfw-body-background-color) !important; }' . PHP_EOL;
		?>
		<?php
		if ( ! empty( $custom_css ) ) {
			$output .= $custom_css;
		}

		return $output;
	}

	public static function add_styles() {
		wp_add_inline_style( 'cfw_front_css', self::get_css_custom_property_overrides() . self::get_custom_css() );
	}
}
