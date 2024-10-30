<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Gateways;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class PayPalCheckout extends CompatibilityAbstract {
	public function is_available(): bool {
		return ( function_exists( 'wc_gateway_ppec' ) && ! empty( wc_gateway_ppec()->settings ) && method_exists( wc_gateway_ppec()->settings, 'is_enabled' ) && wc_gateway_ppec()->settings->is_enabled() );
	}

	public function run() {
		add_filter( 'cfw_is_checkout', array( $this, 'is_checkout' ), 10, 1 );
	}

	public function is_checkout( $is_checkout ) {
		if ( wc_gateway_ppec()->checkout->has_active_session() ) {
			return false;
		}

		return $is_checkout;
	}
}
