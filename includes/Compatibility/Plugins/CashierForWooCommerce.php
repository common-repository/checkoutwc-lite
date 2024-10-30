<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Admin;

class CashierForWooCommerce extends CompatibilityAbstract {
	public function is_available(): bool {
		return function_exists( 'initialize_cashier_for_woocommerce' );
	}

	public function pre_init() {
		add_action( 'cfw_admin_integrations_settings', array( $this, 'admin_integration_settings' ) );
	}

	public function run_immediately() {
		add_filter( 'woocommerce_enable_order_notes_field', array( $this, 'enable_notes_field' ) );

		$enabled_modules = \SA_WC_Cashier::get_instance()->get_enabled_modules();

		if ( ! in_array( 'checkout-field-editor', $enabled_modules, true ) ) {
			return;
		}

		if ( SettingsManager::instance()->get_setting( 'allow_cashier_for_woocommerce_address_modification' ) === 'yes' ) {
			return;
		}

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		$billing_priority = apply_filters( 'sa_cfw_cfe_billing_fields_priority', 999 );
		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		$shipping_priority = apply_filters( 'sa_cfw_cfe_shipping_fields_priority', 999 );
		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

		$instance = \SA_CFW_CFE_Woo_Checkout_Fields::get_instance();

		remove_filter( 'woocommerce_billing_fields', array( $instance, 'billing_address' ), $billing_priority );
		remove_filter( 'woocommerce_shipping_fields', array( $instance, 'shipping_address' ), $shipping_priority );
	}

	public function run() {
	}

	/**
	 * Output the admin integration setting
	 *
	 * @param Admin\Pages\PageAbstract $integrations
	 */
	public function admin_integration_settings( Admin\Pages\PageAbstract $integrations ) {
		if ( ! $this->is_available() ) {
			return;
		}

		$integrations->output_checkbox_row(
			'allow_cashier_for_woocommerce_address_modification',
			cfw__( 'Enable Cashier for WooCommerce address field overrides. (Not Recommended)', 'checkout-wc' ),
			cfw__( 'Allow WooCommerce Cashier Checkout Field Editor module to modify billing and shipping address fields. Not compatible with these features: Discreet House Number and Street Name Address Fields, Full Name Field', 'checkout-wc' )
		);
	}

	public function enable_notes_field(): bool {
		return  'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' );
	}
}
