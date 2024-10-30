<?php

namespace Objectiv\Plugins\Checkout\Compatibility\Plugins;

use Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

class WooCommerceCore extends CompatibilityAbstract {
	public function is_available(): bool {
		return true; // always on, baby
	}

	public function pre_init() {
		// Using this instead of is_ajax() in case is_ajax() is not available
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( apply_filters( 'wp_doing_ajax', defined( 'DOING_AJAX' ) && DOING_AJAX ) && ! isset( $_GET['wc-ajax'] ) ) {
			return;
		}
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'suppress_add_to_cart_notices_during_checkout_redirect' ), 100000000 ); // run this late
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'suppress_add_to_cart_notices_during_ajax_add_to_cart' ), 100000000 ); // run this late
		add_action( 'woocommerce_before_checkout_process', array( $this, 'sync_billing_fields_on_process_checkout' ) );
	}

	public function run() {
		//phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		add_action(
			'cfw_checkout_before_billing_address',
			function() {
				do_action( 'woocommerce_before_checkout_billing_form', WC()->checkout() );
			}
		);

		add_action(
			'cfw_checkout_after_billing_address',
			function() {
				do_action( 'woocommerce_after_checkout_billing_form', WC()->checkout() );
			}
		);

		add_action(
			'cfw_checkout_before_shipping_address',
			function() {
				do_action( 'woocommerce_before_checkout_shipping_form', WC()->checkout() );
			}
		);

		add_action(
			'cfw_checkout_after_shipping_address',
			function() {
				do_action( 'woocommerce_after_checkout_shipping_form', WC()->checkout() );
			}
		);

		add_action(
			'cfw_checkout_customer_info_tab',
			function() {
				/**
				 * This action is generally used to output HTML that breaks our layout,
				 * so we run it but discard the output. This allows plugins that
				 * initialize their stuff on this hook to still function
				 */
				ob_start();

				do_action( 'woocommerce_checkout_before_customer_details' );

				echo '<div class="cfw-force-hidden">' . cfw_clean_html( ob_get_clean() ) . '</div>';
			},
			5
		);

		add_action(
			'cfw_checkout_customer_info_tab',
			function() {
				/**
				 * This action is generally used to output HTML that breaks our layout,
				 * so we run it but discard the output. This allows plugins that
				 * initialize their stuff on this hook to still function
				 */
				ob_start();

				do_action( 'woocommerce_checkout_after_customer_details' );

				echo '<div class="cfw-force-hidden">' . cfw_clean_html( ob_get_clean() ) . '</div>';
			},
			55
		);
		//phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

		if ( SettingsManager::instance()->get_setting( 'enable_order_pay' ) === 'yes' ) {
			remove_action( 'before_woocommerce_pay', 'woocommerce_output_all_notices', 10 );
		}

		add_filter( 'woocommerce_countries_shipping_countries', array( $this, 'fool_woo_into_handling_one_country_the_way_we_prefer' ) );
		add_filter( 'woocommerce_countries_allowed_countries', array( $this, 'fool_woo_into_handling_one_country_the_way_we_prefer' ) );
		add_filter( 'woocommerce_form_field', array( $this, 'removed_shim_country_so_woo_handles_one_country_the_way_we_prefer' ), 200000, 3 );
	}

	public function sync_billing_fields_on_process_checkout() {
		// Is the CFW flag present and is it set to use the shipping address as the billing address?
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['bill_to_different_address'] ) && 'same_as_shipping' === $_POST['bill_to_different_address'] ) {
			foreach ( $_POST as $key => $value ) {
				// If a plugin has added a shipping email field, don't sync it
				// Specifically fixes issues with WooCommerce Pakettikauppa
				if ( 'shipping_email' === $key ) {
					continue;
				}

				// If this is a shipping field, create a duplicate billing field
				if ( substr( $key, 0, 9 ) === 'shipping_' ) {
					$billing_field_key = substr_replace( $key, 'billing_', 0, 9 );

					$_POST[ $billing_field_key ] = $value;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	public function suppress_add_to_cart_notices_during_ajax_add_to_cart( $fragments ) {
		/**
		 * Filters whether to suppress add to cart notices at checkout
		 *
		 * @since 2.0.0
		 *
		 * @param bool $supress_notices True suppress, false allow
		 */
		if ( ! apply_filters( 'cfw_suppress_add_to_cart_notices', true ) ) {
			return $fragments;
		}

		$checkout_url = wc_get_checkout_url();

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		$redirect_url = apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment

		// If we are going to redirect to checkout, don't show message
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_REQUEST['product_id'] ) && ! empty( $_REQUEST['wc-ajax'] ) && 'add_to_cart' === $_REQUEST['wc-ajax'] && $redirect_url === $checkout_url ) {
			$quantity   = wc_clean( $_REQUEST['quantity'] ?? 1 );
			$product_id = wc_clean( $_REQUEST['product_id'] );

			cfw_remove_add_to_cart_notice( $product_id, $quantity );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Continue on your way
		return $fragments;
	}

	public function suppress_add_to_cart_notices_during_checkout_redirect( $url ) {
		/**
		 * Filters whether to suppress add to cart notices at checkout
		 *
		 * @since 2.0.0
		 *
		 * @param bool $suppress_notices True suppress, false allow
		 */
		if ( ! apply_filters( 'cfw_suppress_add_to_cart_notices', true ) ) {
			return $url;
		}

		$checkout_url = wc_get_checkout_url();

		// If we are going to redirect to checkout, don't show message
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_REQUEST['add-to-cart'] ) && ( $url === $checkout_url || is_checkout() ) ) {
			$quantity   = wc_clean( $_REQUEST['quantity'] ?? 1 );
			$quantity   = is_numeric( $quantity ) ? intval( $quantity ) : 1;
			$product_id = wc_clean( $_REQUEST['add-to-cart'] );

			cfw_remove_add_to_cart_notice( $product_id, $quantity );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Continue on your way
		return $url;
	}

	public function remove_scripts( array $scripts ): array {
		$scripts['wc-cart-fragments'] = 'wc-cart-fragments';

		return $scripts;
	}

	public function fool_woo_into_handling_one_country_the_way_we_prefer( array $countries ): array {
		if ( count( $countries ) === 1 ) {
			$countries['shim'] = 'shim';
		}

		return $countries;
	}

	public function removed_shim_country_so_woo_handles_one_country_the_way_we_prefer( $field, $key, $args ) {
		if ( 'country' === $args['type'] ) {
			// Remove the shim option
			$field = preg_replace( '/<option value="shim".*shim<\/option>/', '', $field, -1, $count );

			// Now select the remaining country
			if ( $count ) {
				$field = str_replace( 'value=', 'selected="selected" value=', $field );
			}
		}

		return $field;
	}
}
