<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class WooCommerceSubscriptions extends CompatibilityAbstract {
	public function is_available(): bool {
		return class_exists( '\\WC_Subscriptions_Cart' );
	}

	public function run_immediately() {
		add_filter( 'cfw_show_shipping_tab', array( $this, 'maybe_hide_shipping_tab' ) );
	}

	public function run() {
		add_filter( 'woocommerce_checkout_registration_required', array( $this, 'override_registration_required' ), 10, 1 );
	}

	public function maybe_hide_shipping_tab( $show_shipping_tab ) {
		if ( ! $show_shipping_tab ) {
			return $show_shipping_tab;
		}

		$cart_contents = WC()->cart->get_cart_contents();

		// Remove any items from the cart array that are non-qualifying subscriptions
		foreach ( $cart_contents as $i => $cart_item ) {
			if ( \WC_Subscriptions_Product::get_trial_length( $cart_item['data'] ) > 0 ) {
				unset( $cart_contents[ $i ] );
			}
		}

		// If the cart is now empty, we should hide the shipping tab
		if ( count( $cart_contents ) === 0 ) {
			return false;
		}

		return $show_shipping_tab;
	}

	public function override_registration_required( $result ) {
		if ( \WC_Subscriptions_Cart::cart_contains_subscription() && ! is_user_logged_in() ) {
			$result = true;
		}

		return $result;
	}
}
