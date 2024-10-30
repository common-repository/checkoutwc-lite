<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class Checkout extends PageAbstract {
	public function __construct() {

		parent::__construct( cfw__( 'Checkout', 'checkout-wc' ), 'manage_options', 'checkout' );
	}

	public function output() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php cfw_admin_page_section( 'Steps', 'Control the checkout steps.', $this->get_steps_fields() ); ?>
			<?php cfw_admin_page_section( 'Field Options', 'Control how different checkout fields appear.', $this->get_field_option_fields() ); ?>
			<?php cfw_admin_page_section( 'Address Options', 'Control address fields.', $this->get_address_options_fields() ); ?>
		</div>
		<?php
		$this->output_form_close();
	}

	public function get_steps_fields() {
		ob_start();

		$this->output_checkbox_row(
			'skip_cart_step',
			cfw__( 'Disable Cart Step', 'checkout-wc' ),
			cfw__( 'Disable to skip the cart and redirect customers directly to checkout after adding a product to the cart.', 'checkout-wc' )
		);

		/**
		 * Fires at the bottom steps settings container
		 *
		 * @since 7.0.0
		 *
		 * @param Checkout $checkout_admin_page The checkout settings admin page
		 */
		do_action( 'cfw_after_admin_page_checkout_steps_section', $this );

		return ob_get_clean();
	}

	public function get_field_option_fields() {
		$settings           = SettingsManager::instance();
		$order_notes_enable = ! has_filter( 'woocommerce_enable_order_notes_field' ) || ( $settings->get_setting( 'enable_order_notes' ) === 'yes' && 1 === cfw_count_filters( 'woocommerce_enable_order_notes_field' ) );

		$order_notes_notice_replacement_text = '';

		if ( ! $order_notes_enable && defined( 'WC_CHECKOUT_FIELD_EDITOR_VERSION' ) ) {
			$order_notes_notice_replacement_text = cfw__( 'This setting is overridden by WooCommerce Checkout Field Editor.', 'checkout-wc' );
		}

		ob_start();

		$this->output_checkbox_row(
			'enable_order_notes',
			cfw__( 'Enable Order Notes Field', 'checkout-wc' ),
			cfw__( 'Enable or disable WooCommerce Order Notes field. (Default: Disabled)', 'checkout-wc' ),
			array(
				'enabled'                => $order_notes_enable,
				'show_overridden_notice' => false === $order_notes_enable,
				'overridden_notice'      => $order_notes_notice_replacement_text,
			)
		);

		/**
		 * Fires at the bottom steps settings container
		 *
		 * @since 7.0.0
		 *
		 * @param Checkout $checkout_admin_page The checkout settings admin page
		 */
		do_action( 'cfw_after_admin_page_field_options_section', $this );

		return ob_get_clean();
	}

	public function get_address_options_fields() {
		ob_start();

		$this->output_checkbox_row(
			'force_different_billing_address',
			cfw__( 'Force Different Billing Address', 'checkout-wc' ),
			cfw__( 'Remove option to use shipping address as billing address.', 'checkout-wc' )
		);

		return ob_get_clean();
	}
}
