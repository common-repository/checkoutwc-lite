<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use WP_Admin_Bar;

/**
 * Start Here admin page
 *
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class General extends PageAbstract {
	protected $appearance_page;

	public function __construct( Appearance $appearance_page ) {
		$this->appearance_page = $appearance_page;
		parent::__construct( cfw__( 'Start Here', 'checkout-wc' ), 'manage_options' );
	}

	public function init() {
		parent::init();

		add_action( 'admin_bar_menu', array( $this, 'add_parent_node' ), 100 );
		add_action( 'admin_menu', array( $this, 'setup_main_menu_page' ), $this->priority - 5 );
	}

	public function setup_menu() {
		add_submenu_page( self::$parent_slug, $this->title, $this->title, $this->capability, $this->slug, null, $this->priority );
	}

	public function setup_main_menu_page() {
		add_menu_page( 'CheckoutWC', 'CheckoutWC', 'manage_options', self::$parent_slug, array( $this, 'output_with_wrap' ), 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( CFW_PATH . '/assets/admin/images/icon.svg' ) ) );
	}

	public function output() {
		$this->output_form_open();
		?>
		<div class="max-w-3xl pb-8">
			<div>
				<p class="text-5xl font-bold text-gray-900">
					<?php cfw_e( 'Welcome to the new standard for WooCommerce checkouts.', 'checkout-wc' ); ?>
				</p>
				<p class="max-w-xl mt-5 text-2xl text-gray-500">
					<?php cfw_e( 'Higher conversions start here.', 'checkout-wc' ); ?>
				</p>
			</div>
		</div>
		<div class="hidden sm:block" aria-hidden="true">
			<div class="py-8">
				<div class="border-t border-gray-300"></div>
			</div>
		</div>
		<div class="space-y-8 mt-4">
			<?php
			cfw_admin_page_section(
				cfw__( '1. Customize Logo and Colors', 'checkout-wc' ),
				cfw__( 'Review your logo and set your brand colors.', 'checkout-wc' ),
				$this->get_design_content()
			);

			cfw_admin_page_section(
				cfw__( '2. Review Your Checkout Page', 'checkout-wc' ),
				cfw__( 'Test your checkout page and make sure everything is working correctly.', 'checkout-wc' ),
				$this->get_preview_content()
			);

			cfw_admin_page_section(
				cfw__( '3. Go Live', 'checkout-wc' ),
				cfw__( 'Enable templates for all visitors.', 'checkout-wc' ),
				$this->get_activation_settings()
			);
			?>
		</div>
		<div class="mt-8 rounded" style="background: #122b42">

			<div class="p-8 flex">

				<div class="left text-white space-y-4 w-2/3">
					<h2 class="text-2xl text-white font-bold"><?php cfw_esc_html_e( 'Upgrade to PRO', 'checkout-wc' ); ?></h2>
					<ul class="flex flex-wrap">
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( '5 Modern Templates', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Thank You Page', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Side Cart', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Order Bumps', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Order Pay Page', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Zip / Address Autocomplete', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Address Verification', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Trust Badges', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'One Page Checkout Layout', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Order Review Step', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'User Matching', 'wpforms-lite' ); ?></li>
						<li class="w-1/2 text-base flex content-center"><span class="dashicons dashicons-yes text-base text-green-700 pr-2"></span> <?php cfw_esc_html_e( 'Full Name Field', 'wpforms-lite' ); ?></li>
					</ul>
				</div>

				<div class="w-1/3 text-center">
					<div class="divide-y divide-slate-600 mb-4">
						<h2 class="text-white text-2xl py-2 font-bold"><span><?php esc_html_e( 'Starting At', 'checkout-wc' ); ?></span></h2>

						<div class="price py-2">
							<span class="amount text-white text-5xl">$99</span><br>
							<span class="term text-white"><?php esc_html_e( 'per year', 'checkout-wc' ); ?></span>
						</div>
					</div>

					<a href="https://www.checkoutwc.com/lite-upgrade/?utm_campaign=liteplugin&utm_medium=admin-menu&utm_source=WordPress&utm_content=Upgrade+to+Pro" rel="noopener noreferrer" target="_blank"
					   class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-lg font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
						<?php esc_html_e( 'Upgrade Now - Save 25%', 'checkout-wc' ); ?>
					</a>
				</div>

			</div>

		</div>
		<?php
		$this->output_form_close();
	}

	public function get_activation_settings() {
		ob_start();

		$this->output_toggle_checkbox(
			'enable',
			cfw__( 'Activate CheckoutWC Template', 'checkout-wc' ),
			cfw__( 'Requires a valid and active license key. Checkout template is always activated for admin users.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	public function get_design_content() {
		ob_start();
		?>
		<div class="flex flex-row items-center">
			<a href="<?php echo esc_attr( $this->appearance_page->get_url() ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Customize Logo and Colors', 'checkout-wc' ); ?>
			</a>
			<svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="<?php cfw_e( 'Opens in new tab' ); ?>">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_preview_content() {
		$url = wc_get_checkout_url();

		$products = wc_get_products(
			array(
				'limit'  => 1,
				'status' => 'publish',
				'type'   => array( 'simple' ),
			)
		);

		if ( empty( $products ) ) {
			$products = wc_get_products(
				array(
					'parent_exclude' => 0,
					'limit'          => 1,
					'status'         => 'publish',
					'type'           => array( 'variable' ),
				)
			);
		}

		// Get any simple or variable woocommerce product
		if ( ! empty( $products ) ) {
			$product = $products[0];

			$url = add_query_arg( array( 'add-to-cart' => $product->get_id() ), $url );
		}

		ob_start();
		?>
		<div class="flex flex-row items-center">
			<a href="<?php echo esc_attr( $url ); ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
				<?php cfw_e( 'Preview Your Checkout Page', 'checkout-wc' ); ?>
			</a>
			<svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-label="<?php cfw_e( 'Opens in new tab' ); ?>">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add parent node
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_parent_node( WP_Admin_Bar $admin_bar ) {
		if ( ! $this->can_show_admin_bar_button() ) {
			return;
		}

		if ( cfw_is_checkout() ) {
			// Remove irrelevant buttons
			$admin_bar->remove_node( 'new-content' );
			$admin_bar->remove_node( 'updates' );
			$admin_bar->remove_node( 'edit' );
			$admin_bar->remove_node( 'comments' );
		}

		$url = $this->get_url();

		$admin_bar->add_node(
			array(
				'id'     => self::$parent_slug,
				'title'  => '<span class="ab-icon dashicons dashicons-cart"></span>' . cfw__( 'CheckoutWC', 'checkout-wc' ),
				'href'   => $url,
				'parent' => false,
			)
		);
	}

	/**
	 * Add admin bar menu node
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function add_admin_bar_menu_node( WP_Admin_Bar $admin_bar ) {
		if ( ! apply_filters( 'cfw_do_admin_bar', true ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'     => $this->slug . '-general',
				'title'  => $this->title,
				'href'   => $this->get_url(),
				'parent' => self::$parent_slug,
			)
		);
	}
}
