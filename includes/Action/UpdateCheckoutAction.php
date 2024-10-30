<?php

namespace Objectiv\Plugins\Checkout\Action;

class UpdateCheckoutAction extends CFWAction {
	public function __construct() {
		parent::__construct( 'update_order_review' );
	}

	/**
	 * @since 1.0.0
	 * @access public
	 */
	public function load() {
		if ( ! isset( $_POST['cfw'] ) ) {
			return;
		}

		remove_all_actions( "wc_ajax_{$this->get_id()}" );
		add_action( "wc_ajax_{$this->get_id()}", array( $this, 'execute' ), 1 );

		/**
		 * These legacy handlers are here because Woo adds them and 3rd party plugins
		 * sometimes expect them. This is particularly important for WooCommerce Memberships
		 * which uses these handlers to detect valid WC ajax requests when the home page is
		 * restricted
		 */
		remove_all_actions( "wp_ajax_woocommerce_{$this->get_id()}" );
		add_action( "wp_ajax_woocommerce_{$this->get_id()}", array( $this, 'execute' ), 1 );

		remove_all_actions( "wp_ajax_nopriv_woocommerce_{$this->get_id()}" );
		add_action( "wp_ajax_nopriv_woocommerce_{$this->get_id()}", array( $this, 'execute' ), 1 );
	}

	public function action() {
		check_ajax_referer( 'update-order-review', 'security' );

		\WC_Checkout::instance();
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		if ( WC()->cart->is_empty() && ! is_customize_preview() && cfw_apply_filters( 'woocommerce_checkout_update_order_review_expired', true ) ) {
			/**
			 * Filters which element to update with session expired notice
			 *
			 * @param string $element Element to update with session expired notice
			 *
			 * @since 5.2.0
			 */
			$target_selector = apply_filters( 'cfw_session_expired_target_element', 'form.woocommerce-checkout' );

			$this->out(
				array(
					'redirect'  => false,
					'fragments' => cfw_apply_filters(
						'woocommerce_update_order_review_fragments',
						array(
							$target_selector => '<div class="woocommerce-error">' . cfw__( 'Sorry, your session has expired.', 'woocommerce' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-backward">' . cfw__( 'Return to shop', 'woocommerce' ) . '</a></div>',
						)
					),
				)
			);
		}

		/** This action is documented in woocommerce/includes/class-wc-ajax.php */
		cfw_do_action( 'woocommerce_checkout_update_order_review', isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/**
		 * Fires when updating CheckoutWC order review
		 *
		 * @param string $post_data The POST data
		 *
		 * @since 2.0.0
		 *
		 */
		do_action( 'cfw_checkout_update_order_review', isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		$posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

		if ( is_array( $posted_shipping_methods ) ) {
			foreach ( $posted_shipping_methods as $i => $value ) {
				$chosen_shipping_methods[ $i ] = $value;
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		WC()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : wc_clean( wp_unslash( $_POST['payment_method'] ) ) );

		WC()->customer->set_props(
			array(
				'billing_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
				'billing_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
				'billing_postcode'  => isset( $_POST['postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : null,
				'billing_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
				'billing_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
				'billing_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
			)
		);

		if ( wc_ship_to_billing_address_only() || ! WC()->cart->needs_shipping() ) {
			WC()->customer->set_props(
				array(
					'shipping_country'   => isset( $_POST['country'] ) ? wc_clean( wp_unslash( $_POST['country'] ) ) : null,
					'shipping_state'     => isset( $_POST['state'] ) ? wc_clean( wp_unslash( $_POST['state'] ) ) : null,
					'shipping_postcode'  => isset( $_POST['postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : null,
					'shipping_city'      => isset( $_POST['city'] ) ? wc_clean( wp_unslash( $_POST['city'] ) ) : null,
					'shipping_address_1' => isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : null,
					'shipping_address_2' => isset( $_POST['address_2'] ) ? wc_clean( wp_unslash( $_POST['address_2'] ) ) : null,
				)
			);
		} else {
			WC()->customer->set_props(
				array(
					'shipping_country'   => isset( $_POST['s_country'] ) ? wc_clean( wp_unslash( $_POST['s_country'] ) ) : null,
					'shipping_state'     => isset( $_POST['s_state'] ) ? wc_clean( wp_unslash( $_POST['s_state'] ) ) : null,
					'shipping_postcode'  => isset( $_POST['s_postcode'] ) ? trim( wc_clean( wp_unslash( $_POST['s_postcode'] ) ) ) : null,
					'shipping_city'      => isset( $_POST['s_city'] ) ? wc_clean( wp_unslash( $_POST['s_city'] ) ) : null,
					'shipping_address_1' => isset( $_POST['s_address'] ) ? wc_clean( wp_unslash( $_POST['s_address'] ) ) : null,
					'shipping_address_2' => isset( $_POST['s_address_2'] ) ? wc_clean( wp_unslash( $_POST['s_address_2'] ) ) : null,
				)
			);
		}

		$calculated_shipping = isset( $_POST['has_full_address'] ) && wc_string_to_bool( wc_clean( wp_unslash( $_POST['has_full_address'] ) ) );
		WC()->customer->set_calculated_shipping( $calculated_shipping );

		WC()->customer->save();

		/**
		 * Handle situation where cart transitions from shipped to non-shipped or vice versa
		 *
		 * In this edge case we have to refresh the page
		 */
		$needs_shipping_before = WC()->cart->needs_shipping_address();

		/**
		 * Fires after customer address data has been updated.
		 *
		 * @since 7.0.0
		 */
		do_action( 'cfw_update_checkout_after_customer_save', isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$reload_checkout = isset( WC()->session->reload_checkout );

		if ( ! $reload_checkout && WC()->cart->needs_shipping_address() !== $needs_shipping_before ) {
			$reload_checkout = true;
		}

		/**
		 * Filters whether to redirect the checkout page during refresh
		 *
		 * @param bool|string Boolean false means don't redirect, string means redirect to URL
		 *
		 * @since 2.0.0
		 *
		 */
		$redirect = apply_filters( 'cfw_update_checkout_redirect', false );

		// Calculate shipping before totals. This will ensure any shipping methods that affect things like taxes are chosen prior to final totals being calculated. Ref: #22708.
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		unset( WC()->session->refresh_totals, WC()->session->reload_checkout );

		/**
		 * Fetch available gateways and make sure at least one is set
		 *
		 * This is to fix an issue where removing a free coupon doesn't show a selected gateway
		 * until the second refresh - not idea!
		 */
		$available_gateways = WC()->cart->needs_payment() ? WC()->payment_gateways()->get_available_payment_gateways() : array();

		reset( $available_gateways );

		$first_gateway       = key( $available_gateways );
		$have_chosen_gateway = false;

		foreach ( $available_gateways as $available_gateway ) {
			if ( $available_gateway->chosen ) {
				$have_chosen_gateway = true;
			}
		}

		if ( ! $have_chosen_gateway ) {
			if ( isset( $available_gateways[ WC()->session->get( 'chosen_payment_method' ) ] ) ) {
				$available_gateways[ WC()->session->get( 'chosen_payment_method' ) ]->chosen = true;
			} elseif ( ! empty( $available_gateways[ $first_gateway ] ) ) {
				$available_gateways[ $first_gateway ]->chosen = true;
				WC()->session->set( 'chosen_payment_method', $first_gateway );
			}
		}

		/**
		 * Filters payment methods during update_checkout refresh
		 *
		 * @param string The payment methods container and content
		 *
		 * @since 4.0.2
		 *
		 */
		$updated_payment_methods = apply_filters( 'cfw_update_payment_methods', cfw_get_payment_methods() );

		/** This action is documented in woocommerce/includes/class-wc-checkout.php */
		cfw_do_action( 'woocommerce_check_cart_items' );

		// Chosen shipping methods
		$chosen_shipping_methods_labels = array();

		$packages = WC()->shipping->get_packages();

		foreach ( $packages as $i => $package ) {
			$chosen_method     = WC()->session->get( 'chosen_shipping_methods' )[ $i ] ?? false;
			$available_methods = $package['rates'];

			if ( $chosen_method && method_exists( $available_methods[ $chosen_method ], 'get_label' ) ) {
				$chosen_shipping_methods_labels[] = $available_methods[ $chosen_method ]->get_label();
			}
		}

		/**
		 * Filters chosen shipping methods label
		 *
		 * @param string $chosen_shipping_methods_labels The chosen shipping methods
		 *
		 * @since 2.0.0
		 *
		 */
		$chosen_shipping_methods_labels = apply_filters( 'cfw_payment_method_address_review_shipping_method', $chosen_shipping_methods_labels );

		$update_checkout_output = array(
			'needs_payment'             => WC()->cart->needs_payment(),
			'fragments'                 => cfw_apply_filters(
				'woocommerce_update_order_review_fragments', /** This filter is documented in woocommerce/includes/class-wc-ajax.php */
				array(
					'.cfw-review-pane-shipping-address-label-value' => '<div role="rowheader" class="cfw-review-pane-shipping-address-label-value cfw-review-pane-label">' . cfw_get_review_pane_shipping_address_label() . '</div>',
					'.cfw-review-pane-shipping-address-value'       => '<div class="cfw-review-pane-content cfw-review-pane-shipping-address-value">' . cfw_get_review_pane_shipping_address( WC()->checkout() ) . '</div>',
					'.cfw-review-pane-contact-value'                => '<div class="cfw-review-pane-content cfw-review-pane-contact-value">' . apply_filters( 'cfw_review_pane_contact_value', WC()->checkout()->get_value( 'billing_email' ) ) . '</div>',
					'.cfw-review-pane-shipping-method-value'        => '<div class="cfw-review-pane-content cfw-review-pane-shipping-method-value">' . join( ', ', $chosen_shipping_methods_labels ) . '</div>',
					'.cfw-review-pane-payment-method-value'         => '<div class="cfw-review-pane-content cfw-review-pane-payment-method-value">' . cfw_get_review_pane_payment_method() . '</div>',
					'#cfw-checkout-before-order-review'             => $this->get_action_output( 'woocommerce_checkout_before_order_review', 'cfw-checkout-before-order-review' ),
					'#cfw-checkout-after-order-review'              => $this->get_action_output( 'woocommerce_checkout_after_order_review', 'cfw-checkout-after-order-review' ),
					'#cfw-place-order'                              => cfw_get_place_order(),
					'#cfw-totals-list'                              => cfw_get_totals_html(),
					'#cfw-cart'                                     => cfw_get_checkout_item_summary_table(),
					'#cfw-mobile-total'                             => '<span id="cfw-mobile-total" class="total amount cfw-display-table-cell">' . WC()->cart->get_total() . '</span>',
					'#cfw-billing-methods'                          => $updated_payment_methods,
					'#cfw-shipping-methods'                         => '<div id="cfw-shipping-methods" class="cfw-module">' . cfw_get_shipping_methods_html() . '</div>',
					'#cfw-review-order-totals'                      => cfw_return_function_output( 'cfw_order_review_step_totals_review_pane' ),
				)
			),
			'reload'                    => $reload_checkout,
			'redirect'                  => $redirect,
			'show_shipping_tab'         => cfw_show_shipping_tab(),
			'has_valid_shipping_method' => cfw_all_packages_have_available_shipping_methods( WC()->shipping()->get_packages() ),
		);

		if ( ! $reload_checkout ) {
			// Do this last so that anything that runs above can bubble up a notice
			$update_checkout_output['notices']  = cfw_get_woocommerce_notices( false ); // don't clear the notices so that
			$update_checkout_output['messages'] = wc_print_notices( true ); // <-- we can use them here
		}


		$this->out( $update_checkout_output );
	}

	protected function get_action_output( $action, $container = '' ) {
		ob_start();

		echo '<div id="' . esc_attr( $container ) . '">';
		do_action( $action );
		echo '</div>';

		return ob_get_clean();
	}
}
