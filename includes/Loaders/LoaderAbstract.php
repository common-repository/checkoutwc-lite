<?php

namespace Objectiv\Plugins\Checkout\Loaders;

use Objectiv\Plugins\Checkout\Managers\StyleManager;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * Helps load pages
 *
 * @link checkoutwc.com
 * @since 3.6.0
 * @package Objectiv\Plugins\Checkout\Core
 */

abstract class LoaderAbstract {
	public static function checkout() {}

	/**
	 * @return array The global parameters
	 */
	public static function init_checkout() {
		/**
		 * Set Checkout Constant
		 */
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		/**
		 * Add body classes
		 */
		add_filter(
			'body_class',
			function( $css_classes ) {
				if ( ! cfw_show_shipping_tab() ) {
					$css_classes[] = 'cfw-hide-shipping';
				}

				return $css_classes;
			}
		);

		// This seems to be a 3.5 requirement
		// Ensure gateways and shipping methods are loaded early.
		WC()->payment_gateways();
		WC()->shipping();

		// When on the checkout with an empty cart, redirect to cart page
		// Check cart has contents.
		if ( WC()->cart->is_empty() && ! is_customize_preview() && apply_filters( 'woocommerce_checkout_redirect_empty_cart', true ) ) {
			wc_add_notice( cfw__( 'Checkout is not available whilst your cart is empty.', 'woocommerce' ), 'notice' );
			wp_redirect( wc_get_cart_url() );
			exit;
		}

		// Check cart contents for errors
		do_action( 'woocommerce_check_cart_items' );

		// Calc totals
		WC()->cart->calculate_totals();

		/**
		 * Filters global template parameters available to templates
		 *
		 * @since 3.0.0
		 *
		 * @param array $global_params The global template parameters
		 */
		return apply_filters( 'cfw_template_global_params', array() );
	}

	/**
	 * @since 1.0.0
	 * @access public
	 * @param array $global_template_parameters
	 * @param string $template_file
	 */
	public static function display( array $global_template_parameters, string $template_file ) {
		/**
		 * Fires before template pieces are loaded
		 *
		 * @since 3.0.0
		 *
		 * @param string $template_file The template file
		 */
		do_action( 'cfw_template_before_load', $template_file );

		// Load content template
		cfw_get_active_template()->view( $template_file, $global_template_parameters );

		/**
		 * Fires after template pieces are loaded
		 *
		 * @since 3.0.0
		 *
		 * @param string $template_file The template file
		 */
		do_action( 'cfw_template_after_load', $template_file );
	}

	/**
	 *
	 */
	public static function output_meta_tags() {
		?>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<?php
	}

	/**
	 * Output content of WP Admin > CheckoutWC > Advanced > Header Scripts
	 */
	public static function output_custom_header_scripts() {
		if ( cfw_is_checkout() ) {
			echo SettingsManager::instance()->get_setting( 'header_scripts_checkout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Output content of WP Admin > CheckoutWC > Advanced > Footer Scripts
	 */
	public static function output_custom_footer_scripts() {
		if ( cfw_is_checkout() ) {
			echo SettingsManager::instance()->get_setting( 'footer_scripts_checkout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public static function output_page_title() {
		?>
		<title>
			<?php echo wp_kses_post( wp_get_document_title() ); ?>
		</title>
		<?php
	}

	/**
	 * Output custom styles
	 */
	public static function custom_styles() {
		do_action( 'cfw_custom_styles' );
	}
}
