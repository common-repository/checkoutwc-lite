<?php
/**
 * Checkout
 *
 * @see cfw_wc_print_notices_with_wrap()
 * @see cfw_breadcrumb_navigation()
 * @see cfw_customer_info_tab_heading()
 * @see cfw_customer_info_tab_account()
 * @see cfw_customer_info_address()
 * @see cfw_customer_info_tab_nav()
 * @see cfw_shipping_method_address_review_pane()
 * @see cfw_shipping_methods()
 * @see cfw_shipping_method_tab_nav()
 * @see cfw_payment_tab_before_content()
 * @see cfw_payment_methods()
 * @see cfw_payment_tab_content_billing_address()
 * @see cfw_payment_tab_content_order_notes()
 * @see cfw_payment_tab_content_terms_and_conditions()
 * @see cfw_payment_tab_nav()
 * @see cfw_cart_summary_mobile_header()
 * @see cfw_cart_summary_content_open_wrap()
 * @see cfw_cart_summary_before_order_review()
 * @see cfw_cart_html()
 * @see cfw_coupon_module()
 * @see cfw_cart_summary_after_order_review()
 * @see cfw_totals_html()
 * @see cfw_close_cart_summary_div()
 * @see cfw_maybe_output_login_modal_container()
 */
add_action( 'cfw_checkout_main_container_start', 'cfw_wc_print_notices_with_wrap', 10 );
add_action( 'cfw_checkout_main_container_start', 'cfw_maybe_output_login_modal_container', 10 );
add_action(
	'cfw_checkout_main_container_start',
	function() {
		if ( ! apply_filters( 'cfw_replace_form', false ) ) {
			do_action( 'woocommerce_before_checkout_form', WC()->checkout() );
		}
	},
	20
);
add_action(
	'cfw_checkout_main_container_end',
	function() {
		if ( ! apply_filters( 'cfw_replace_form', false ) ) {
			do_action( 'woocommerce_after_checkout_form', WC()->checkout() );
		}
	},
	20
);

// Breadcrumbs
add_action( 'cfw_checkout_before_order_review', 'cfw_breadcrumb_navigation', 10 );

// Checkout tabs
add_action( 'cfw_checkout_tabs', 'cfw_output_checkout_tabs', 10 );

// Customer Information Tab
// IMPORTANT: Some of these hooks are dehooked for One Page Checkout feature
add_action( 'cfw_checkout_customer_info_tab', 'cfw_payment_request_buttons', 10 );
add_action( 'cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_heading', 20 );
add_action( 'cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_account', 30 );
add_action( 'cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_account_fields', 40 );
add_action( 'cfw_checkout_customer_info_tab', 'cfw_customer_info_address', 50 );
add_action( 'cfw_checkout_customer_info_tab', 'cfw_customer_info_tab_nav', 60 );

// Shipping Method Tab
add_action( 'cfw_checkout_shipping_method_tab', 'cfw_shipping_method_address_review_pane', 10 );
add_action( 'cfw_checkout_shipping_method_tab', 'cfw_shipping_methods', 20 );
add_action( 'cfw_checkout_shipping_method_tab', 'cfw_shipping_method_tab_nav', 30 );

// Payment Method Tab
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_method_address_review_pane', 0 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_methods', 10 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_maybe_output_extra_billing_fields', 25 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_order_notes', 30 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_terms_and_conditions', 40 );
add_action( 'cfw_payment_nav_place_order_button', 'cfw_place_order', 10 );
add_action( 'cfw_checkout_payment_method_tab', 'cfw_payment_tab_nav', 50, 0 );

// Cart Summary
add_action( 'cfw_checkout_cart_summary', 'cfw_cart_summary_mobile_header', 10 );
add_action( 'cfw_checkout_cart_summary', 'cfw_cart_summary_content_open_wrap', 20 ); // Div open
add_action( 'cfw_checkout_cart_summary', 'cfw_cart_summary_before_order_review', 30 );
add_action( 'cfw_checkout_cart_summary', 'cfw_cart_html', 40 );
add_action( 'cfw_checkout_cart_summary', 'cfw_coupon_module', 50 );
add_action( 'cfw_checkout_cart_summary', 'cfw_cart_summary_after_order_review', 60 );
add_action( 'cfw_checkout_cart_summary', 'cfw_totals_html', 70 );
add_action( 'cfw_checkout_cart_summary', 'cfw_close_cart_summary_div', 75 ); // Div close

// Lost password modal
add_action( 'cfw_checkout_main_container_end', 'cfw_lost_password_modal' );

/**
 * Footer
 *
 * @see cfw_maybe_output_footer_nav_menu()
 */
add_action( 'cfw_after_footer', 'cfw_maybe_output_footer_nav_menu', 10 );
