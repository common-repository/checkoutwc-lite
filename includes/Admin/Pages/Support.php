<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class Support extends PageAbstract {
	public function __construct() {
		parent::__construct( cfw__( 'Support', 'checkout-wc' ), 'manage_options', 'support' );
	}

	public function output() {
		?>
		<div class="max-w-3xl pb-8">
			<div>
				<p class="text-5xl font-bold text-gray-900">
					<?php cfw_e( 'CheckoutWC Lite is a free plugin', 'checkout-wc' ); ?>
				</p>
				<p class="max-w-xl mt-5 text-2xl text-gray-500">
					<?php cfw_e( 'While we occasionally answer questions in the WordPress.org forums, support is a premium feature. If you would like help with your checkout page, please consider upgrading to our premium version.', 'checkout-wc' ); ?>
				</p>
				<p class="mt-6">
					<a href="https://www.checkoutwc.com/pricing" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-lg shadow font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
						<?php cfw_e( 'Upgrade to CheckoutWC Pro', 'checkout-wc' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
