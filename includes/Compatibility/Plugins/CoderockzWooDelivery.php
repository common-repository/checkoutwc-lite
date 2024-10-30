<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;

class CoderockzWooDelivery extends CompatibilityAbstract {
	public function is_available(): bool {
		return function_exists( 'run_coderockz_woo_delivery' );
	}

	public function run() {
		add_filter( 'cfw_select_field_options', array( $this, 'remove_empty_options' ), 10, 3 );

		$other_settings = get_option( 'coderockz_woo_delivery_other_settings' );
		$position       = isset( $other_settings['field_position'] ) && '' !== $other_settings['field_position'] ? $other_settings['field_position'] : 'after_billing';
		$action         = 'woocommerce_after_checkout_billing_form';

		if ( 'before_billing' === $position ) {
			$action = 'woocommerce_checkout_billing';
		} elseif ( 'before_shipping' === $position ) {
			$action = 'woocommerce_checkout_shipping';
		} elseif ( 'after_shipping' === $position ) {
			$action = 'woocommerce_after_checkout_shipping_form';
		} elseif ( 'before_notes' === $position ) {
			$action = 'woocommerce_before_order_notes';
		} elseif ( 'after_notes' === $position ) {
			$action = 'woocommerce_after_order_notes';
		} elseif ( 'before_payment' === $position ) {
			$action = 'woocommerce_review_order_before_payment';
		} elseif ( 'before_your_order' === $position ) {
			$action = 'woocommerce_checkout_before_order_review_heading';
		}

		$instance = cfw_get_hook_instance_object( $action, 'coderockz_woo_delivery_add_custom_field' );

		if ( ! $instance ) {
			return;
		}

		add_action( 'cfw_checkout_after_shipping_methods', array( $instance, 'coderockz_woo_delivery_add_custom_field' ) );
	}

	/**
	 * Plugin expects select2 and thus provides a null option which is not desirable
	 * It isn't desirable because Select 2 doesn't allow selecting the null option, but
	 * withotu select2 it is selectable which throws errors
	 *
	 * @param $options
	 * @param $args
	 * @param $key
	 *
	 * @return void
	 */
	public function remove_empty_options( $options, $args, $key ) {
		// If key doesn't start with coderockz_, return
		if ( 0 !== strpos( $key, 'coderockz_' ) ) {
			return $options;
		}

		reset( $options );

		if ( key( $options ) !== '' ) {
			return $options;
		}

		// Remove first element of array and return the array
		array_shift( $options );

		return $options;
	}
}
