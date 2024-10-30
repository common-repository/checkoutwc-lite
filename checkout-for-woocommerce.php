<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://www.checkoutwc.com
 * @since             1.0.0
 * @package           Objectiv\Plugins\Checkout
 *
 * @wordpress-plugin
 * Plugin Name:       CheckoutWC Lite
 * Plugin URI:        https://www.CheckoutWC.com
 * Description:       A beautiful, responsive, conversion optimized checkout template for WooCommerce.
 * Version:           1.1.11
 * Author:            CheckoutWC
 * Author URI:        https://www.checkoutwc.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       checkout-wc
 * Domain Path:       /languages
 * Requires Plugins: woocommerce
 * Tested up to: 6.4.3
 * WC tested up to: 8.7.0
 * Requires PHP: 7.1
 */

/**
 * If this file is called directly, abort.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'activate_checkout-for-woocommerce/checkout-for-woocommerce.php', function () {
	deactivate_plugins( 'checkoutwc-lite/checkout-for-woocommerce.php' );
} );

if ( defined( 'CFW_VERSION' ) ) {
	return;
}

define( 'CFW_NAME', 'CheckoutWC Lite' );
define( 'CFW_VERSION', '1.1.11' );
define( 'CFW_PATH', dirname( __FILE__ ) );
define( 'CFW_URL', plugins_url( '/', __FILE__ ) );
define( 'CFW_MAIN_FILE', __FILE__ );
define( 'CFW_PATH_BASE', plugin_dir_path( __FILE__ ) );
define( 'CFW_PATH_URL_BASE', plugin_dir_url( __FILE__ ) );
define( 'CFW_PATH_MAIN_FILE', CFW_PATH_BASE . __FILE__ );
define( 'CFW_PATH_ASSETS', CFW_PATH_URL_BASE . 'assets' );
define( 'CFW_PATH_PLUGIN_TEMPLATE', CFW_PATH_BASE . 'templates' );
define( 'CFW_PATH_THEME_TEMPLATE', get_stylesheet_directory() . '/checkout-wc' );

/**
 * Our language function wrappers that we only use for
 * external translation domains
 *
 * This has to run here or we can't use these functions in the PHP warning which short circuits everything else.
 */
require_once CFW_PATH . '/sources/php/language-wrapper-functions.php';

/**
 * Our hook function wrappers that we only use for external hooks
 */
require_once CFW_PATH . '/sources/php/hook-wrapper-functions.php';

/*
 * Protect our gentle, out of date users from our fancy modern code
 */
if ( version_compare( phpversion(), '7.1', '<' ) ) {
	add_action( 'admin_notices', function () {
		?>
		<div class="notice notice-error">
			<p>
				<?php _e( 'Your site is running an <strong>insecure version</strong> of PHP that is no longer supported. Please contact your web hosting provider to update your PHP version.', 'checkout-wc' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>
				<br><br>
				<?php
				printf(
					wp_kses(
					/* translators: %s - checkoutwc.com URL for documentation with more details. */
						__( '<strong>Note:</strong> CheckoutWC is disabled on your site until you fix the issue. <a href="%s" target="_blank" rel="noopener noreferrer">Need help? Click here.</a>', 'checkout-wc' ),
						[
							'a'      => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
							'strong' => [],
						]
					),
					'https://www.checkoutwc.com/documentation/installation-requirements/'
				);
				?>
			</p>
		</div>

		<?php
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	} );

	// Abort!
	return;
}

// Require WP 5.2+
if ( version_compare( $GLOBALS['wp_version'], '5.2', '<' ) ) {
	add_action( 'admin_notices', function () {

		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
				/* translators: %s - WordPress version. */
					esc_html__( 'CheckoutWC requires WordPress %s or later.', 'checkout-wc' ),
					'5.2'
				);
				?>
			</p>
		</div>

		<?php
		// In case this is on plugin activation.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	} );

	// Do not process the plugin code further.
	return;
}

// Test to see if WooCommerce is active (including network activated).
$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (
	! in_array( $plugin_path, wp_get_active_and_valid_plugins(), true )
	&& ( ! function_exists( 'wp_get_active_network_plugins' ) || ! in_array( $plugin_path, wp_get_active_network_plugins(), true ) )
) {
	add_action( 'admin_notices', function () {

		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
				/* translators: %s - WordPress version. */
					esc_html__( 'CheckoutWC requires WooCommerce %s or later.', 'checkout-wc' ),
					'5.6'
				);
				?>
			</p>
		</div>

		<?php
		// In case this is on plugin activation.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	} );

	// Do not process the plugin code further.
	return;
}

/**
 * Auto-loader (composer)
 */
require_once CFW_PATH . '/vendor/autoload.php';

// ensure CFW_DEV_MODE is defined
if ( ! defined( 'CFW_DEV_MODE' ) ) {
	define( 'CFW_DEV_MODE', getenv( 'CFW_DEV_MODE' ) === 'true' );
}

require_once CFW_PATH . '/sources/php/functions.php';
require_once CFW_PATH . '/sources/php/admin-template-functions.php';
require_once CFW_PATH . '/sources/php/template-functions.php';
require_once CFW_PATH . '/sources/php/template-hooks.php';

/**
 * Debugging - Kint disabled by default. Enable by enabling developer mode (see docs)
 */
if ( class_exists( '\Kint' ) && property_exists( '\Kint', 'enabled_mode' ) ) {
	\Kint::$enabled_mode = defined( 'CFW_DEV_MODE' ) && CFW_DEV_MODE;
}

// Declare compatibility with High-Performance Order Storage.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

require CFW_PATH . '/sources/php/init.php';
