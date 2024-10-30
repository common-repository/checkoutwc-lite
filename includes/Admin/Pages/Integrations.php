<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class Integrations extends PageAbstract {
	public function __construct() {
		parent::__construct( cfw__( 'Integrations', 'checkout-wc' ), 'manage_options', 'integrations' );
	}

	public function output() {
		$this->output_form_open();
		?>
		<div class="space-y-6">
			<?php
			cfw_admin_page_section(
				cfw__( 'Themes and Plugins', 'checkout-wc' ),
				cfw__( 'Integrations with 3rd party themes and plugins.', 'checkout-wc' ),
				$this->get_integration_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_integration_settings() {
		ob_start();

		/**
		 * Fires at top of WP Admin > CheckoutWC > Advanced > Integrations
		 *
		 * Use to add additional integration settings
		 *
		 * @since 5.0.0
		 *
		 * @param PageAbstract $integrations The integrations admin page class
		 */
		do_action( 'cfw_admin_integrations_settings', $this );

		$output = ob_get_clean();

		if ( empty( $output ) ) {
			return cfw__( 'No integrations available.', 'checkout-wc' );
		}

		return $output;
	}
}
