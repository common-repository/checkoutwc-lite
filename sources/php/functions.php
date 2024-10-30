<?php
use Objectiv\Plugins\Checkout\Adapters\CartItemFactory;
use Objectiv\Plugins\Checkout\AddressFieldsAugmenter;
use Objectiv\Plugins\Checkout\FormFieldAugmenter;
use Objectiv\Plugins\Checkout\Interfaces\ItemInterface;
use Objectiv\Plugins\Checkout\Model\CartItem;
use Objectiv\Plugins\Checkout\Model\Template;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;
use Objectiv\Plugins\Checkout\Managers\AssetManager;
use Objectiv\Plugins\Checkout\Loaders\Redirect;

function cfw_output_fieldset( array $fieldset ) {
	if ( empty( $fieldset ) ) {
		return;
	}

	$row_open  = '<div class="row cfw-input-wrap-row">' . PHP_EOL;
	$row_close = '</div>' . PHP_EOL;
	$count     = 0;
	$max       = 12;

	echo $row_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	foreach ( $fieldset as $key => $field ) {
		$field            = apply_filters( 'cfw_pre_output_fieldset_field_args', $field, $key );
		$field['columns'] = $field['columns'] ?? 12;
		$field['type']    = $field['type'] ?? 'text';

		if ( ( $count + $field['columns'] > $max || $count === $max ) && 'hidden' !== $field['type'] && ! in_array( 'hidden', $field['class'] ?? array(), true ) ) {
			echo $row_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $row_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$count = 0;
		}

		woocommerce_form_field( $key, $field, WC()->checkout()->get_value( $key ) );
		$count += $field['columns'];
	}

	echo $row_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * @param WC_Checkout $checkout
 */
function cfw_output_account_checkout_fields( WC_Checkout $checkout ) {
	if ( is_user_logged_in() || ! $checkout->is_registration_enabled() ) {
		return;
	}

	/**
	 * Filters shipping address checkout fields
	 *
	 * @since 2.0.0
	 *
	 * @param array $account_checkout_fields Account checkout fields
	 */
	$account_checkout_fields = apply_filters( 'cfw_get_account_checkout_fields', $checkout->get_checkout_fields( 'account' ) );

	unset( $account_checkout_fields['account_password'] );
	unset( $account_checkout_fields['account_username'] );

	do_action( 'cfw_output_fieldset', $account_checkout_fields, 'account' );
}

/**
 * Get the shipping fields
 *
 * @return array
 */
function cfw_get_shipping_checkout_fields(): array {
	/**
	 * Filters shipping address checkout fields
	 *
	 * @since 2.0.0
	 *
	 * @param array $shipping_checkout_fields Shipping address checkout fields
	 */
	return apply_filters( 'cfw_get_shipping_checkout_fields', (array) WC()->checkout()->get_checkout_fields( 'shipping' ) );
}

/**
 */
function cfw_output_shipping_checkout_fields() {
	do_action( 'cfw_output_fieldset', cfw_get_shipping_checkout_fields(), 'shipping' );
}

/**
 * Get the billing fields
 *
 * @return array
 */
function cfw_get_billing_checkout_fields(): array {
	/**
	 * Filters billing address checkout fields
	 *
	 * @since 2.0.0
	 *
	 * @param array $billing_checkout_fields Billing address checkout fields
	 */
	$billing_checkout_fields = apply_filters( 'cfw_get_billing_checkout_fields', (array) WC()->checkout()->get_checkout_fields( 'billing' ) );

	// Email field is handled separately
	unset( $billing_checkout_fields['billing_email'] );

	return $billing_checkout_fields;
}

/**
 */
function cfw_output_billing_checkout_fields() {
	$billing_checkout_fields = cfw_get_billing_checkout_fields();

	/** This filter is documented in checkoutwc-lite/sources/php/functions.php */
	if ( ! WC()->cart->needs_shipping_address() || apply_filters('cfw_force_display_billing_address', false ) ) {
		do_action( 'cfw_output_fieldset', $billing_checkout_fields, 'billing' );
		return;
	}

	$shipping_checkout_fields = cfw_get_shipping_checkout_fields();
	$billing_fields_in_common = cfw_get_common_billing_fields( $billing_checkout_fields, $shipping_checkout_fields );

	do_action( 'cfw_output_fieldset', $billing_fields_in_common, 'billing' );
}

/**
 * Filter billing fields down to only fields that match shipping fields
 *
 * @param array $billing_fields
 * @param array $shipping_fields
 *
 * @return array
 */
function cfw_get_common_billing_fields( array $billing_fields, array $shipping_fields ): array {
	$keys = array();

	foreach ( array_keys( $shipping_fields ) as $key ) {
		$keys[ str_replace( 'shipping_', 'billing_', $key ) ] = true;
	}

	return array_intersect_key( $billing_fields, $keys );
}

/**
 * Filter billing fields down to only unique fields that aren't also shipping fields
 *
 * @param array $billing_fields
 * @param array $shipping_fields
 *
 * @return array
 */
function cfw_get_unique_billing_fields( array $billing_fields, array $shipping_fields ): array {
	$keys = array();

	foreach ( array_keys( $shipping_fields ) as $key ) {
		$keys[ str_replace( 'shipping_', 'billing_', $key ) ] = true;
	}

	$unique_fields = array_diff_key( $billing_fields, $keys );

	return apply_filters( 'cfw_unique_billing_fields', $unique_fields );
}

function cfw_maybe_output_extra_billing_fields() {
	if ( ! WC()->cart->needs_shipping_address() ) {
		return;
	}

	$unique_fields = cfw_get_unique_billing_fields( cfw_get_billing_checkout_fields(), cfw_get_shipping_checkout_fields() );

	if ( ! empty( $unique_fields ) ) {
		do_action( 'cfw_output_fieldset', $unique_fields, 'billing_unique' );
	}
}

function cfw_get_review_pane_shipping_address_label(): string {
	if ( ! wc_ship_to_billing_address_only() ) {
		$ship_to_label = __( 'Ship to', 'checkout-wc' );
	} else {
		$ship_to_label = cfw__( 'Address', 'woocommerce' );
	}

	/**
	 * Filters ship to label in review pane
	 *
	 * @param string $ship_to_label Ship to label
	 * @since 3.0.0
	 */
	return apply_filters( 'cfw_ship_to_label', $ship_to_label );
}

/**
 * @param WC_Checkout $checkout
 *
 * @return string
 */
function cfw_get_review_pane_shipping_address( WC_Checkout $checkout ): string {
	$formatted_address = WC()->countries->get_formatted_address(
		/**
		 * Filters review pane shipping address
		 *
		 * @since 2.0.0
		 *
		 * @param array $shipping_details_address Review pane shipping address
		 */
		apply_filters(
			'cfw_get_shipping_details_address',
			cfw_get_posted_address_fields( wc_ship_to_billing_address_only() ? 'billing' : 'shipping' ),
			$checkout
		),
		', '
	);

	/**
	 * Filters review pane formatted shipping address
	 *
	 * @since 7.3.0
	 *
	 * @param string $formatted_address Formatted shipping address
	 */
	$formatted_address = apply_filters( 'cfw_get_review_pane_shipping_address', $formatted_address );

	// Cleanup address formats that weren't used
	return cfw_cleanup_formatted_address( $formatted_address );
}

/**
 * @param WC_Checkout $checkout
 *
 * @return string
 */
function cfw_get_review_pane_billing_address( WC_Checkout $checkout ): string {
	$formatted_address = WC()->countries->get_formatted_address(
		/**
		 * Filters review pane billing address
		 *
		 * @since 2.0.0
		 *
		 * @param array $billing_details_address Review pane billing address
		 */
		apply_filters(
			'cfw_get_review_pane_billing_address',
			cfw_get_posted_address_fields(),
			$checkout
		),
		', '
	);

	return cfw_cleanup_formatted_address( $formatted_address );
}

function cfw_cleanup_formatted_address( string $address ): string {
	// Cleanup address formats that weren't used
	return preg_replace( '/{[^\s]+}(,|\s)/', '', $address );
}

/**
 * @param string $fieldset
 *
 * @return array
 */
function cfw_get_posted_address_fields( string $fieldset = 'billing' ): array {
	$short_prefix         = 'billing' === $fieldset ? '' : 's_';
	$long_prefix          = 'billing' === $fieldset ? 'billing_' : 'shipping_';
	$known_address_fields = WC()->countries->get_default_address_fields();

	$address = array();

	$post_data = array();
	if ( ! empty( $_POST['post_data'] ) ) {
		parse_str( wp_unslash( $_POST['post_data'] ?? '' ), $post_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	foreach ( $known_address_fields as $key => $field ) {
		$address[ $key ] = sanitize_text_field( $_POST[ $short_prefix . $key ] ?? $post_data[ $long_prefix . $key ] ?? '' );
	}

	return $address;
}

function cfw_get_shipping_methods_html() {
	ob_start();

	cfw_shipping_methods_html();

	return ob_get_clean();
}

function cfw_shipping_methods_html() {
	$packages = WC()->shipping->get_packages();

	foreach ( $packages as $i => $package ) {
		$product_names = array();

		if ( count( $packages ) > 1 ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
			}
			$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
		}

		/**
		 * Filters available shipping methods
		 *
		 * @since 8.0.0
		 *
		 * @param array $methods Available shipping methods
		 * @param array $package Package data
		 */
		$available_methods = apply_filters( 'cfw_available_shipping_methods', $package['rates'], $package, $i );

		$chosen_method        = WC()->session->chosen_shipping_methods[ $i ] ?? '';
		$show_package_details = count( $packages ) > 1;
		$package_details      = implode( ', ', $product_names );
		$package_name         = apply_filters( 'woocommerce_shipping_package_name', sprintf( cfw_nx( 'Shipping', 'Shipping %d', ( $i + 1 ), 'shipping packages', 'woocommerce' ), ( $i + 1 ) ), $i, $package );
		$index                = $i;

		// Next section ripped straight from cart-shipping and edited for now
		if ( count( $available_methods ) > 0 ) : ?>
			<?php if ( 1 < count( $packages ) ) : ?>
				<h4 class="cfw-shipping-package-title"><?php echo esc_html( $package_name ); ?></h4>
			<?php endif; ?>

			<?php cfw_before_shipping(); ?>

			<ul id="shipping_method" class="cfw-shipping-methods-list">
				<?php
				foreach ( $available_methods as $method ) :
					ob_start();
					do_action( 'woocommerce_after_shipping_rate', $method, $index );
					$after_shipping_method = ob_get_clean();
					$label                 = wc_cart_totals_shipping_method_label( $method );
					$label                 = str_replace( ': <span', '<span', $label );
					?>
					<li>
						<div class="cfw-shipping-method-inner">
							<?php printf( '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr( sanitize_title( $method->id ) ), esc_attr( $method->id ), checked( $method->id, $chosen_method, false ) ); // WPCS: XSS ok. ?>
							<?php printf( '<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr( sanitize_title( $method->id ) ), $label ); // WPCS: XSS ok. ?>
						</div>
						<?php
						if ( ! empty( trim( $after_shipping_method ) ) && preg_match( '/<thead|<tbody|<tfoot|<th|<tr/', $after_shipping_method ) && substr( trim( $after_shipping_method ), 0, 6 ) !== '<table' ) :
							?>
							<table>
								<?php echo $after_shipping_method; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</table>
						<?php else : ?>
							<?php echo $after_shipping_method; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php cfw_after_shipping(); ?>
		<?php else : ?>
			<div class="shipping-message">
				<?php echo apply_filters( 'woocommerce_no_shipping_available_html', '<div class="cfw-alert cfw-alert-error"><div class="message">' . wpautop( cfw_esc_html__( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) . '</div></div>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>

		<?php if ( $show_package_details ) : ?>
			<?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>'; ?>
		<?php endif; ?>
		<?php
	}
}

function cfw_before_shipping() {
	if ( has_action( 'woocommerce_review_order_before_shipping' ) ) :
		?>
		<table id="cfw-before-shipping">
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
		</table>
		<?php
	endif;
}

function cfw_after_shipping() {
	if ( has_action( 'woocommerce_review_order_after_shipping' ) ) :
		?>
		<table id="cfw-after-shipping">
			<?php
			/**
			 * Fires after shipping methods in table
			 *
			 * @since 2.0.0
			 */
			do_action( 'cfw_after_shipping_methods' );
			?>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		</table>
		<?php
	endif;
}

function cfw_get_payment_methods_html() {
	/**
	 * Fires before payment methods html is fetched
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_get_payment_methods_html' );

	$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
	WC()->payment_gateways()->set_current_gateway( $available_gateways );

	remove_filter( 'woocommerce_form_field_args', array( FormFieldAugmenter::instance(), 'calculate_columns' ), 100000 );
	remove_filter( 'woocommerce_form_field', array( FormFieldAugmenter::instance(), 'remove_extraneous_field_classes' ), 100000 );

	ob_start();
	?>
	<ul class="wc_payment_methods payment_methods methods cfw-radio-reveal-group">
	<?php
	/**
	 * Fires at start of payment methods UL
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_payment_methods_ul_start' );

	if ( ! empty( $available_gateways ) ) {
		$count = 0;
		foreach ( $available_gateways as $gateway ) {
			// Prevent fatal errors when no gateway is available
			// OR when gateway isn't actually a gateway
			if ( is_a( $gateway, 'stdClass' ) ) {
				continue;
			}

			/**
			 * Filters whether to show gateway in list of gateways
			 *
			 * @since 2.0.0
			 *
			 * @param bool $show Show gateway output
			 */
			if ( apply_filters( "cfw_show_gateway_{$gateway->id}", true ) ) :
				/**
				 * Filters gateway order button text
				 *
				 * @since 2.0.0
				 *
				 * @param string $gateway_order_button_text The gateway order button text
				 */
				$gateway_order_button_text = apply_filters( 'cfw_gateway_order_button_text', $gateway->order_button_text, $gateway );

				/**
				 * Filters gateway order button text
				 *
				 * @since 2.0.0
				 *
				 * @param string $icons The gateway icon HTML
				 * @param \WC_Payment_Gateway
				 */
				$icons = apply_filters( 'cfw_get_gateway_icons', $gateway->get_icon(), $gateway );

				$title              = $gateway->get_title();
				$is_active_class    = $gateway->chosen ? 'cfw-active' : '';
				$li_class_attribute = apply_filters( 'cfw_payment_method_li_class', "wc_payment_method cfw-radio-reveal-li $is_active_class payment_method_{$gateway->id}" );
				?>
				<li class="<?php echo esc_attr( $li_class_attribute ); ?>">
					<div class="payment_method_title_wrap cfw-radio-reveal-title-wrap">
						<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway_order_button_text ); ?>"/>

						<label class="payment_method_label cfw-radio-reveal-label" for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
							<div>
								<?php if ( $title ) : ?>
									<span class="payment_method_title cfw-radio-reveal-title">
										<?php echo wp_kses_post( $title ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $icons ) : ?>
									<span class="payment_method_icons">
										<?php echo $icons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
								<?php endif; ?>
							</div>
						</label>
					</div>
				<?php
				/**
				 * Filters whether to show gateway content
				 *
				 * @since 2.0.0
				 *
				 * @param bool $show Show gateway content
				 */
				if ( apply_filters( "cfw_payment_gateway_{$gateway->id}_content", $gateway->has_fields() || $gateway->get_description() ) ) :
					?>
						<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?> cfw-radio-reveal-content" <?php echo ! $gateway->chosen ? 'style="display:none;"' : ''; ?>>
							<?php
							ob_start();

							$gateway->payment_fields();

							$field_html = ob_get_clean();

							/**
							 * Gateway Compatibility Patches
							 */
							// Expiration field fix
							$field_html = str_ireplace( 'js-sv-wc-payment-gateway-credit-card-form-expiry', 'js-sv-wc-payment-gateway-credit-card-form-expiry  wc-credit-card-form-card-expiry', $field_html );
							$field_html = str_ireplace( 'js-sv-wc-payment-gateway-credit-card-form-account-number', 'js-sv-wc-payment-gateway-credit-card-form-account-number  wc-credit-card-form-card-number', $field_html );

							// Credit Card Field Placeholders
							$field_html = str_ireplace( '•••• •••• •••• ••••', 'Card Number', $field_html );
							$field_html = str_ireplace( '&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;', 'Card Number', $field_html );

							/**
							 * Filters gateway payment field output HTML
							 *
							 * @since 2.0.0
							 *
							 * @param string $gateway_output Payment gateway output HTML
							 */
							echo apply_filters( "cfw_payment_gateway_field_html_{$gateway->id}", $field_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					<?php endif; ?>
				</li>

				<?php
				else :
					/**
					 * Fires after payment method LI to allow alternate / additional output
					 *
					 * @since 2.0.0
					 */
					do_action_ref_array( "cfw_payment_gateway_list_{$gateway->id}_alternate", array( $count ) );
				endif;

				$count++;
		}
	} else {
		echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', cfw__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Fires after bottom of payment methods UL
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_payment_methods_ul_end' );
	?>
	</ul>
	<?php

	add_filter( 'woocommerce_form_field_args', array( FormFieldAugmenter::instance(), 'calculate_columns' ), 100000 );
	add_filter( 'woocommerce_form_field', array( FormFieldAugmenter::instance(), 'remove_extraneous_field_classes' ), 100000, 1 );

	return ob_get_clean();
}

/**
 * @return string
 */
function cfw_get_checkout_item_summary_table(): string {
	// Discard output of this hook for now because
	// we are adding this for Free Gifts for WooCommerce
	// and we don't know if other plugins are using this hook
	// in a way that we don't prefer
	ob_start();
	do_action( 'woocommerce_review_order_before_cart_contents' );
	$output = ob_get_clean();

	/**
	 * Filters whether woocommerce_review_order_before_cart_contents hook is allowed to output
	 *
	 * @since 4.3.2
	 *
	 * @param bool $show_hook Whether to output hook
	 */
	echo apply_filters( 'cfw_show_review_order_before_cart_contents_hook', false ) ? $output : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$items = CartItemFactory::get( WC()->cart );

	/**
	 * Filters whether to link cart items to products
	 *
	 * @since 1.0.0
	 *
	 * @param bool $link_cart_items Link cart items to products
	 */
	$link_items = apply_filters( 'cfw_link_cart_items', SettingsManager::instance()->get_setting( 'cart_item_link' ) === 'enabled' );

	/**
	 * Filters the classes that are added to the checkout cart summary container
	 *
	 * @since 6.3.0
	 *
	 * @param bool $classes The classes to apply to the checkout cart summary container
	 */
	$classes = apply_filters( 'cfw_get_checkout_item_summary_table_container_classes', array( 'cfw-module' ) );
	ob_start();
	?>
	<table id="cfw-cart" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php
		/**
		 * Fires at start of cart table
		 *
		 * @since 2.0.0
		 */
		do_action( 'cfw_cart_html_table_start' );

		/** @var CartItem $item */
		foreach ( $items as $item ) :
			$item_thumb = $item->get_thumbnail();
			?>
			<tr class="cart-item-row cart-item-<?php echo esc_attr( $item->get_item_key() ); ?> <?php echo esc_attr( $item->get_row_class() ); ?>">
				<?php if ( $item_thumb ) : ?>
					<td class="cfw-cart-item-image">
						<div class="cfw-cart-item-image-wrap">
							<?php if ( $link_items ) : ?>
								<a target="_blank" href="<?php echo esc_attr( $item->get_url() ); ?>">
									<?php echo $item_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							<?php else : ?>
								<?php echo $item_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>

							<span class="cfw-cart-item-quantity-bubble">
								<?php echo wp_kses_post( $item->get_quantity() ); ?>
							</span>
						</div>
					</td>
				<?php endif; ?>

				<th class="cfw-cart-item-description" <?php echo empty( $item_thumb ) ? 'colspan="2" style="padding-left: 0; ?>"' : ''; ?> >
					<div class="cfw-cart-item-title">
						<?php if ( $link_items ) : ?>
							<a target="_blank" href="<?php echo esc_attr( $item->get_url() ); ?>">
								<?php echo wp_kses_post( $item->get_title() ); ?>
							</a>
						<?php else : ?>
							<?php echo wp_kses_post( $item->get_title() ); ?>
						<?php endif; ?>
					</div>
					<?php
					cfw_display_item_data( $item );

					/**
					 * Fires after cart item data output
					 *
					 * @since 2.0.0
					 */
					do_action( 'cfw_cart_item_after_data', $item->get_raw_item(), $item->get_item_key(), $item );
					?>
				</th>

				<td class="cfw-cart-item-quantity visually-hidden">
					<?php echo wp_kses_post( $item->get_quantity() ); ?>
				</td>

				<td class="cfw-cart-item-subtotal">
					<?php do_action( 'cfw_before_cart_item_subtotal', $item ); ?>
					<?php echo $item->get_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
			<?php
			/**
			 * Fires after cart item row <tr/> is outputted
			 *
			 * @since 2.0.0
			 */
			do_action( 'cfw_after_cart_item_row', $item->get_raw_item(), $item->get_item_key() );
		endforeach;
		?>
	</table>
	<?php
	$return = ob_get_clean();
	/**
	 * After cart html table output
	 *
	 * @since 4.3.4
	 */
	do_action_deprecated( 'cfw_after_cart_html', null, 'CheckoutWC 5.4.0', 'cfw_after_items_summary_table' );

	/**
	 * Filters cart output HTML
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_html Cart output HTML
	 */
	$return = apply_filters_deprecated( 'cfw_cart_html', array( $return ), 'CheckoutWC 5.4.0', 'cfw_items_summary_table_html' );

	return (string) apply_filters( 'cfw_items_summary_table_html', $return, 'checkout' );
}

function cfw_display_item_data( ItemInterface $item ) {
	$output = $item->get_formatted_data();

	if ( $output ) {
		echo '<div class="cfw-cart-item-data">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function cfw_get_totals_html() {
	ob_start();

	/**
	 * Filters cart element ID
	 *
	 * @since 3.0.0
	 *
	 * @param string $cart_element_id Cart element ID
	 */
	$template_cart_el_id = apply_filters( 'cfw_template_cart_el', 'cfw-totals-list' );
	?>
	<div id="<?php echo esc_attr( $template_cart_el_id ); ?>" class="cfw-module">
		<table class="cfw-module">
			<?php
			/**
			 * Fires at start of cart summary totals table
			 *
			 * @since 2.0.0
			 */
			do_action( 'cfw_before_cart_summary_totals' );
			?>

			<tr class="cart-subtotal">
				<th><?php cfw_e( 'Subtotal', 'woocommerce' ); ?></th>
				<td><?php wc_cart_totals_subtotal_html(); ?></td>
			</tr>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
					<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( cfw_show_shipping_total() ) : ?>
				<?php cfw_cart_totals_shipping_html(); ?>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<tr class="fee">
					<th><?php echo esc_html( $fee->name ); ?></th>
					<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
				<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
					<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
						<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
							<th><?php echo esc_html( $tax->label ); ?></th>
							<td data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="tax-total">
						<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
						<td><?php wc_cart_totals_taxes_total_html(); ?></td>
					</tr>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			/**
			 * Fires before totals are output in cart summary totals table
			 *
			 * @since 2.0.0
			 */
			do_action( 'woocommerce_review_order_before_order_total' );
			?>

			<tr class="order-total">
				<th><?php cfw_e( 'Total', 'woocommerce' ); ?></th>
				<td><?php wc_cart_totals_order_total_html(); ?></td>
			</tr>

			<?php
			/**
			 * Fires after totals are output in cart summary totals table
			 *
			 * @since 2.0.0
			 */
			do_action( 'woocommerce_review_order_after_order_total' );
			?>

			<?php
			/**
			 * Fires at end of cart summary totals table before </table> tag
			 *
			 * @since 2.0.0
			 */
			do_action( 'cfw_after_cart_summary_totals' );
			?>
		</table>
	</div>
	<?php

	/**
	 * Filters cart totals HTML
	 *
	 * @since 2.0.0
	 *
	 * @param string $totals_html Cart totals HTML
	 */
	return apply_filters( 'cfw_totals_html', ob_get_clean() );
}

/**
 * Get shipping methods.
 *
 * @see wc_cart_totals_shipping_html()
 */
function cfw_cart_totals_shipping_html() {
	?>
	<tr class="woocommerce-shipping-totals">
		<th>
			<?php
			/**
			 * Filters cart totals shipping label
			 *
			 * @since 2.0.0
			 *
			 * @param string $cart_totals_shipping_label Cart totals shipping label
			 */
			echo apply_filters( 'cfw_cart_totals_shipping_label', cfw_esc_html__( 'Shipping', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</th>
		<td>
			<?php echo cfw_get_shipping_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</td>
	</tr>
	<?php
}

function cfw_all_packages_have_available_shipping_methods( array $packages ): bool {
	foreach ( $packages as $package ) {
		if ( empty( $package['rates'] ) ) {
			return false;
		}
	}

	return true;
}

function cfw_get_shipping_total(): string {
	$small_format = '<span class="cfw-small">%s</span>';

	$has_calculated_shipping = WC()->customer->has_calculated_shipping();
	$address_required        = get_option( 'woocommerce_shipping_cost_requires_address' ) === 'yes';
	$missing_address         = $address_required && ! $has_calculated_shipping;

	if ( $missing_address ) {
		/**
		 * Filters shipping total address required text
		 *
		 * @param string $address_required_text Shipping total address required text
		 * @since 2.0.0
		 */
		return sprintf( $small_format, apply_filters( 'cfw_shipping_total_address_required_text', cfw_esc_html__( 'Enter your address to view shipping options.', 'woocommerce' ) ) );
	}

	$packages = WC()->shipping()->get_packages();

	if ( ! cfw_all_packages_have_available_shipping_methods( $packages ) ) {
		/**
		 * Filters shipping total text when no shipping methods are available
		 *
		 * @param string $new_shipping_total_not_available_text Shipping total text when no shipping methods are available
		 * @since 2.0.0
		 */
		return sprintf( $small_format, apply_filters( 'cfw_shipping_total_not_available_text', __( 'No shipping methods available', 'checkout-wc' ) ) );
	}

	if ( has_filter( 'woocommerce_shipping_chosen_method' ) && ! cfw_all_packages_have_selected_shipping_methods( $packages ) ) {
		return apply_filters( 'cfw_no_shipping_method_selected_message', '' );
	}

	$total = cfw_calculate_packages_shipping( $packages, WC()->session, WC()->cart );

	if ( 0 < $total ) {
		return wc_price( $total );
	}

	return apply_filters( 'cfw_shipping_free_text', cfw__( 'Free!', 'woocommerce' ) );
}

function cfw_all_packages_have_selected_shipping_methods( $packages ): bool {
	foreach ( $packages as $i => $package ) {
		$default = wc_get_default_shipping_method_for_package( $i, $package, false );
		$session = WC()->session->chosen_shipping_methods[ $i ] ?? false;

		if ( false === $default && false === $session ) {
			return false;
		}
	}

	return true;
}

function cfw_calculate_packages_shipping( array $packages, $wc_session, $wc_cart ) {
	$total = 0;

	foreach ( $packages as $i => $package ) {
		$chosen_method     = $wc_session->chosen_shipping_methods[ $i ] ?? '';
		$available_methods = empty( $package['rates'] ) ? array() : $package['rates'];

		foreach ( $available_methods as $method ) {
			if ( (string) $method->id !== (string) $chosen_method ) { // WC_Shipping_Method::id is defined as a string type, so we need to make sure we're comparing it as a string
				continue;
			}

			if ( 0 >= $method->cost ) {
				continue;
			}

			$total += $method->cost;

			if ( $wc_cart->display_prices_including_tax() ) {
				$total += $method->get_shipping_tax();
			}
		}
	}

	return $total;
}

/**
 * Get shipping methods.
 *
 * @see wc_cart_totals_shipping_html()
 */
function cfw_order_review_pane_shipping_totals() {
	?>
	<li>
		<div class="inner cfw-no-border">
			<div role="rowheader" class="cfw-review-pane-label">
				<?php
				/**
				 * Filters cart totals shipping label
				 *
				 * @param string $cart_totals_shipping_label Cart totals shipping label
				 * @since 3.0.0
				 */
				echo apply_filters( 'cfw_cart_totals_shipping_label', cfw_esc_html__( 'Shipping', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</div>
		<div role="cell" class="cfw-review-pane-content cfw-review-pane-right cfw-no-border">
			<?php echo cfw_get_shipping_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</li>
	<?php
}

function cfw_address_class_wrap( $shipping = true ) {
	// If __field-wrapper class isn't there, Amazon Pay nukes our address fields :-(
	$result = 'woocommerce-billing-fields woocommerce-billing-fields__field-wrapper';

	if ( true === $shipping ) {
		$result = 'woocommerce-shipping-fields woocommerce-shipping-fields__field-wrapper';
	}

	echo esc_attr( $result );
}

function cfw_get_place_order( $order_button_text = false ) {
	ob_start();

	$order_button_text = ! $order_button_text ? cfw_apply_filters( 'woocommerce_order_button_text', __( 'Complete Order', 'checkout-wc' ) ) : $order_button_text;

	/**
	 * Filters place order button container classes
	 *
	 * @since 2.0.0
	 *
	 * @param array $place_order_button_container_classes Place order button container classes
	 */
	$place_order_button_container_class = join( ' ', apply_filters( 'cfw_place_order_button_container_classes', array( 'place-order' ) ) );
	?>
	<div class="<?php echo esc_attr( $place_order_button_container_class ); ?>" data-total="<?php echo esc_attr( WC()->cart->get_total( 'checkoutwc' ) ); ?>" id="cfw-place-order">
		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="cfw-primary-btn cfw-next-tab validate" name="woocommerce_checkout_place_order" id="place_order" formnovalidate="formnovalidate" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '"><span class="cfw-button-text">' . esc_html( $order_button_text ) . '</span></button>' ); // @codingStandardsIgnoreLine ?>

		<?php cfw_do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
		<input type="hidden" name="cfw_update_cart" value="false" />
	</div>
	<?php
	if ( ! wp_doing_ajax() ) {
		cfw_do_action( 'woocommerce_review_order_after_payment' );
	}

	return ob_get_clean();
}

function cfw_place_order( $order_button_text = false ) {
	echo cfw_get_place_order( $order_button_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cfw_get_payment_methods( $object = false, $show_title = true, $payment_methods_html = false ) {
	if ( false === $payment_methods_html ) {
		$payment_methods_html = cfw_get_payment_methods_html();
	}

	$object = ! $object ? WC()->cart : $object;

	ob_start();
	?>
	<div id="cfw-billing-methods" class="cfw-module cfw-accordion">
		<?php
		/**
		 * Fires above the payment method heading
		 *
		 * @since 5.1.1
		 */
		do_action( 'cfw_before_payment_method_heading' );

		if ( $show_title ) :
			?>
			<h3>
				<?php
				/**
				 * Filters payment methods heading
				 *
				 * @since 2.0.0
				 *
				 * @param string $payment_methods_heading Payment methods heading
				 */
				echo apply_filters( 'cfw_payment_method_heading', esc_html__( 'Payment', 'checkout-wc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</h3>
		<?php endif; ?>

		<?php
		/**
		 * Fires after payment methods heading and before transaction are encrypted statement
		 *
		 * @since 2.0.0
		 */
		do_action( 'cfw_checkout_before_payment_methods' );
		?>

		<?php if ( $object->needs_payment() ) : ?>
			<div class="cfw-payment-method-information-wrap">
				<h4 class="cfw-small secure-notice">
					<?php
					/**
					 * Filters payment methods transactions are encrypted statement
					 *
					 * @since 2.0.0
					 *
					 * @param string $transactions_encrypted_statement Payment methods transactions are encrypted statement
					 */
					echo apply_filters( 'cfw_transactions_encrypted_statement', esc_html__( 'All transactions are secure and encrypted.', 'checkout-wc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</h4>

				<div class="cfw-payment-methods-wrap">
					<div id="payment" class="woocommerce-checkout-payment">
						<?php echo $payment_methods_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="cfw-no-payment-method-wrap">
				<span class="cfw-small">
					<?php
					/**
					 * Filters no payment required text
					 *
					 * @since 2.0.0
					 *
					 * @param string $no_payment_required_text No payment required text
					 */
					echo apply_filters( 'cfw_no_payment_required_text', esc_html__( 'Your order is free. No payment is required.', 'checkout-wc' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Fires at end of payment methods container before </div> tag
		 *
		 * @since 2.0.0
		 */
		do_action( 'cfw_checkout_after_payment_methods' );
		?>
	</div>
	<?php

	return ob_get_clean();
}

function cfw_billing_address_radio_group() {
	/**
	 * Fires before billing address radio group is output
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_checkout_before_billing_address' );

	/**
	 * Filters whether to force displaying the billing address (no accordion)
	 *
	 * @since 2.0.0
	 *
	 * @param bool $force_display_billing_address Force displaying billing address
	 */
	if ( ! apply_filters( 'cfw_force_display_billing_address', SettingsManager::instance()->get_setting( 'force_different_billing_address' ) === 'yes' ) ) :
		?>
		<div id="cfw-shipping-same-billing" class="cfw-module cfw-accordion">
			<ul class="cfw-radio-reveal-group">
				<li class="cfw-radio-reveal-li cfw-no-reveal">
					<div class="cfw-radio-reveal-title-wrap">
						<input type="radio" name="bill_to_different_address" id="billing_same_as_shipping_radio" value="same_as_shipping" checked="checked" />

						<label for="billing_same_as_shipping_radio" class="cfw-radio-reveal-label">
							<div>
								<span class="cfw-radio-reveal-title"><?php esc_html_e( 'Same as shipping address', 'checkout-wc' ); ?></span>
							</div>
						</label>

						<?php
						/**
						 * Fires after same as shipping address label
						 *
						 * @since 2.0.0
						 */
						do_action( 'cfw_after_same_as_shipping_address_label' );
						?>
					</div>
				</li>
				<li class="cfw-radio-reveal-li">
					<div class="cfw-radio-reveal-title-wrap">
						<input type="radio" name="bill_to_different_address" id="shipping_dif_from_billing_radio" value="different_from_shipping" />

						<label for="shipping_dif_from_billing_radio" class="cfw-radio-reveal-label">
							<div>
								<span class="cfw-radio-reveal-title"><?php esc_html_e( 'Use a different billing address', 'checkout-wc' ); ?></span>
							</div>
						</label>
					</div>
					<div id="cfw-billing-fields-container" class="cfw-radio-reveal-content <?php cfw_address_class_wrap( false ); ?>" style="display: none">
						<?php
						/**
						 * Fires before billing address inside billing address container
						 *
						 * @since 2.0.0
						 */
						do_action( 'cfw_start_billing_address_container' );

						cfw_output_billing_checkout_fields();

						/**
						 * Fires after billing address inside billing address container
						 *
						 * @since 2.0.0
						 */
						do_action( 'cfw_end_billing_address_container' );
						?>
					</div>
				</li>
			</ul>
		</div>
	<?php else : ?>
		<input type="hidden" name="bill_to_different_address" id="billing_same_as_shipping_radio" value="different_from_shipping" />
		<div class="cfw-module">
			<?php cfw_output_billing_checkout_fields(); ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires after billing address
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_checkout_after_billing_address' );
}

function cfw_return_to_cart_link() {
	if ( ! apply_filters( 'cfw_show_return_to_cart_link', true ) ) {
		return;
	}

	/**
	 * Filter return to cart link URL
	 *
	 * @since 2.0.0
	 *
	 * @param string $return_to_cart_link_url Return to cart link URL
	 */
	$return_to_cart_link_url = apply_filters( 'cfw_return_to_cart_link_url', wc_get_cart_url() );

	/**
	 * Filter return to cart link text
	 *
	 * @since 2.0.0
	 *
	 * @param string $return_to_cart_link_text Return to cart link text
	 */
	$return_to_cart_link_text = apply_filters( 'cfw_return_to_cart_link_text', esc_html__( 'Return to cart', 'checkout-wc' ) );

	/**
	 * Filter return to cart link
	 *
	 * @since 2.0.0
	 *
	 * @param string $cart_link Return to cart link
	 */
	echo apply_filters( 'cfw_return_to_cart_link', sprintf( '<a href="%s" class="cfw-prev-tab">« %s</a>', esc_attr( $return_to_cart_link_url ), $return_to_cart_link_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * @param string $label The pre-translated button label
 * @param array $classes Any extra classes to add
 */
function cfw_continue_to_shipping_button( string $label = '', array $classes = array() ) {
	$new_classes = array_merge(
		array(
			'cfw-primary-btn' => 'cfw-primary-btn',
			'cfw-next-tab',
			'cfw-continue-to-shipping-btn',
		),
		$classes
	);

	if ( in_array( 'cfw-secondary-btn', $classes, true ) ) {
		unset( $new_classes['cfw-primary-btn'] );
	}

	/**
	 * Filter continue to shipping method button label
	 *
	 * @since 3.0.0
	 *
	 * @param string $continue_to_shipping_method_label Continue to shipping method button label
	 */
	$continue_to_shipping_method_label = ! empty( $label ) ? $label : apply_filters( 'cfw_continue_to_shipping_method_label', esc_html__( 'Continue to shipping', 'checkout-wc' ) );

	/**
	 * Filter continue to shipping method button
	 *
	 * @since 3.0.0
	 *
	 * @param string $shipping_method_button Continue to shipping method button
	 */
	echo apply_filters( 'cfw_continue_to_shipping_button', sprintf( '<a href="javascript:" data-tab="#cfw-shipping-method" class="%s"><span class="cfw-button-text">%s</span></a>', esc_attr( join( ' ', $new_classes ) ), $continue_to_shipping_method_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * @param array $args Button options such as label or classes
 */
function cfw_continue_to_payment_button( array $args = array() ) {
	$defaults = array(
		'classes' => array(
			'cfw-primary-btn' => 'cfw-primary-btn',
			'cfw-next-tab',
			'cfw-continue-to-payment-btn',
		),
		'label'   => esc_html__( 'Continue to payment', 'checkout-wc' ),
	);

	$args = wp_parse_args( $args, $defaults );

	if ( in_array( 'cfw-secondary-btn', $args['classes'], true ) ) {
		unset( $args['classes']['cfw-primary-btn'] );
	}

	/**
	 * Filter continue to payment method button label
	 *
	 * @since 3.0.0
	 *
	 * @param string $continue_to_payment_method_label Continue to payment method button label
	 */
	$continue_to_payment_method_label = apply_filters( 'cfw_continue_to_payment_method_label', $args['label'] );

	/**
	 * Filter continue to payment method button
	 *
	 * @since 3.0.0
	 *
	 * @param string $payment_method_button Continue to payment method button
	 */
	echo apply_filters( 'cfw_continue_to_payment_button', sprintf( '<a href="javascript:" data-tab="#cfw-payment-method" class="%s"><span class="cfw-button-text">%s</span></a>', esc_attr( join( ' ', $args['classes'] ) ), $continue_to_payment_method_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cfw_continue_to_order_review_button() {
	/**
	 * Filter continue to order review button label
	 *
	 * @since 3.0.0
	 *
	 * @param string $continue_to_order_review_label Continue to order review button label
	 */
	$continue_to_order_review_label = apply_filters( 'cfw_continue_to_order_review_label', esc_html__( 'Review order', 'checkout-wc' ) );

	/**
	 * Filter continue to order review button
	 *
	 * @since 3.0.0
	 *
	 * @param string $order_review_button Continue to order review button
	 */
	echo apply_filters( 'cfw_continue_to_order_review_button', sprintf( '<a href="javascript:" data-tab="#cfw-order-review" class="cfw-primary-btn cfw-next-tab cfw-continue-to-order-review-btn">%s</a>', $continue_to_order_review_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cfw_return_to_customer_information_link() {
	/**
	 * Filter return to customer information tab label
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_customer_info_label Return to customer information tab label
	 */
	$return_to_customer_info_label = apply_filters( 'cfw_return_to_customer_info_label', esc_html__( 'Return to information', 'checkout-wc' ) );

	/**
	 * Filter return to customer information tab link
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_customer_info_link Return to customer information tab link
	 */
	echo apply_filters( 'cfw_return_to_customer_information_link', sprintf( '<a href="javascript:" data-tab="#cfw-customer-info" class="cfw-prev-tab cfw-return-to-information-btn">« %s</a>', $return_to_customer_info_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cfw_return_to_shipping_method_link() {
	/**
	 * Filter return to shipping method tab label
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_shipping_method_label Return to shipping method tab label
	 */
	$return_to_shipping_method_label = apply_filters( 'cfw_return_to_shipping_method_label', esc_html__( 'Return to shipping', 'checkout-wc' ) );

	/**
	 * Filter return to shipping method tab link
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_shipping_method_link Return to shipping method tab link
	 */
	echo apply_filters( 'cfw_return_to_shipping_method_link', sprintf( '<a href="javascript:" data-tab="#cfw-shipping-method" class="cfw-prev-tab cfw-return-to-shipping-btn">« %s</a>', $return_to_shipping_method_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function cfw_return_to_payment_method_link() {
	/**
	 * Filter return to payment method tab label
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_payment_method_label Return to payment method tab label
	 */
	$return_to_payment_method_label = apply_filters( 'cfw_return_to_payment_method_label', esc_html__( 'Return to payment', 'checkout-wc' ) );

	/**
	 * Filter return to payment method tab link
	 *
	 * @since 3.0.0
	 *
	 * @param string $return_to_payment_method_link Return to payment method tab link
	 */
	echo apply_filters( 'cfw_return_to_payment_method_link', sprintf( '<a href="javascript:" data-tab="#cfw-payment-method" class="cfw-prev-tab cfw-return-to-payment-btn">« %s</a>', $return_to_payment_method_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * @return bool
 */
function cfw_show_customer_information_tab(): bool {
	/**
	 * Filters whether to show customer information tab
	 *
	 * @since 3.0.0
	 *
	 * @param bool $show_customer_information_tab Show customer information tab
	 */
	return apply_filters( 'cfw_show_customer_information_tab', true );
}

function cfw_get_breadcrumbs() : array {
	$tabs = cfw_get_checkout_tabs();

	$default_breadcrumbs = array(
		'cart' => array(
			/**
			 * Filters breadcrumb cart link URL
			 *
			 * @since 3.0.0
			 *
			 * @param string $breadcrumb_cart_link_url Breadcrumb cart link URL
			 */
			'href'     => apply_filters( 'cfw_breadcrumb_cart_url', wc_get_cart_url() ),

			/**
			 * Filters breadcrumb cart link label
			 *
			 * @since 3.0.0
			 *
			 * @param string $breadcrumb_cart_link_label Breadcrumb cart link label
			 */
			'label'    => apply_filters( 'cfw_breadcrumb_cart_label', cfw_esc_html__( 'Cart', 'woocommerce' ) ),
			'priority' => 10,
			'classes'  => array(),
		),
	);

	$first_tab_key = key( $tabs );

	foreach ( $tabs as $tab_id => $tab ) {
		$classes = $tab['enabled'] ? array() : array( 'cfw-force-hidden' );

		if ( $tab_id === $first_tab_key ) {
			$classes[] = 'cfw-default-tab';
		}

		$default_breadcrumbs[ $tab_id ] = array(
			'href'     => "#{$tab_id}",
			'label'    => $tab['label'],
			'priority' => $tab['priority'],
			'classes'  => $classes,
		);
	}

	/**
	 * Filters breadcrumbs
	 *
	 * @since 3.0.0
	 *
	 * @param string $breadcrumbs Breadcrumbs
	 */
	$breadcrumbs = apply_filters( 'cfw_breadcrumbs', $default_breadcrumbs );

	// Order by priority
	uasort( $breadcrumbs, 'cfw_uasort_by_priority_comparison' );

	return $breadcrumbs;
}

function cfw_breadcrumb_navigation() {
	/**
	 * Fires before breadcrumb navigation is output
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_before_breadcrumb_navigation' );
	?>
	<ul id="cfw-breadcrumb" class="etabs">
		<?php
		foreach ( cfw_get_breadcrumbs() as $id => $breadcrumb ) :
			$classes = $breadcrumb['classes'] ?? array();
			?>
			<li class="<?php echo ( 'cart' !== $id ) ? 'tab' : ''; ?> <?php echo esc_attr( $id ); ?> <?php echo esc_attr( join( ' ', $classes ) ); ?>">
				<a href="<?php echo esc_attr( $breadcrumb['href'] ); ?>" class="cfw-small"><?php echo esc_html( $breadcrumb['label'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php

	/**
	 * Fires after breadcrumb navigation is output
	 *
	 * @since 2.0.0
	 */
	do_action( 'cfw_after_breadcrumb_navigation' );
}

/**
 * User to sort breadcrumbs based on priority with uasort.
 *
 * @param array $a First value to compare.
 * @param array $b Second value to compare.
 * @return int
 *@since 3.5.1
 */
function cfw_uasort_by_priority_comparison( array $a, array $b ): int {
	/*
	 * We are not guaranteed to get a priority
	 * setting. So don't compare if they don't
	 * exist.
	 */
	if ( ! isset( $a['priority'], $b['priority'] ) ) {
		return 0;
	}

	return wc_uasort_comparison( $a['priority'], $b['priority'] );
}

function cfw_main_container_classes( $context = 'checkout' ) {
	$classes = array();

	$classes[] = 'container';
	$classes[] = 'context-' . $context;
	$classes[] = 'checkoutwc';

	if ( is_admin_bar_showing() ) {
		$classes[] = 'admin-bar';
	}

	/**
	 * Filters main container classes
	 *
	 * @since 3.0.0
	 *
	 * @param string $classes Main container classes
	 */
	return apply_filters( "cfw_{$context}_main_container_classes", join( ' ', $classes ) );
}

/**
 * @param callable $function
 * @return false|string
 */
function cfw_return_function_output( callable $function ) {
	ob_start();

	$function();

	return ob_get_clean();
}

function cfw_count_filters( $filter ): int {
	global $wp_filter;
	$count = 0;

	if ( isset( $wp_filter[ $filter ] ) ) {
		foreach ( $wp_filter[ $filter ]->callbacks as $callbacks ) {
			$count += (int) count( $callbacks );
		}
	}

	return $count;
}

/**
 * @return bool
 */
function cfw_is_checkout(): bool {
	/**
	 * Filter cfw_is_checkout()
	 *
	 * @since 3.0.0
	 *
	 * @param bool $is_checkout Whether or not we are on the checkout page
	 */
	return apply_filters(
		'cfw_is_checkout',
		( function_exists( 'is_checkout' ) && is_checkout() ) &&
		! is_order_received_page() &&
		! is_checkout_pay_page()
	);
}

/**
 * @return bool
 */
function is_cfw_page(): bool {
	return cfw_is_checkout();
}

/**
 * Determines whether CheckoutWC templates can load on the frontend
 *
 * @return bool
 */
function cfw_is_enabled(): bool {
	$templates_enabled   = SettingsManager::instance()->get_setting( 'enable' ) === 'yes';
	$is_admin            = current_user_can( 'manage_options' );
	$user_can_access     = $templates_enabled || $is_admin;
	$forcefully_disabled = defined( 'CFW_DISABLE_TEMPLATES' ) || isset( $_COOKIE['CFW_DISABLE_TEMPLATES'] ) || isset( $_GET['bypass-cfw'] );

	return $user_can_access && ! $forcefully_disabled;
}

/**
 * Get phone field setting
 *
 * @return boolean
 */
function cfw_is_phone_fields_enabled(): bool {
	return 'hidden' !== get_option( 'woocommerce_checkout_phone_field', 'required' );
}

function cfw_get_plugin_template_path(): string {
	return CFW_PATH_BASE . '/templates';
}

function cfw_get_user_template_path(): string {
	return get_stylesheet_directory() . '/checkout-wc';
}

function cfw_get_active_template(): Template {
	$active_template_slug = sanitize_text_field( $_GET['cfw-preview'] ?? SettingsManager::instance()->get_setting( 'active_template' ) );
	return new Template( empty( $active_template_slug ) ? 'default' : $active_template_slug );
}

/**
 * @return Template[]
 */
function cfw_get_available_templates(): array {
	return Template::get_all_available();
}

function cfw_frontend() {
	// Enqueue Assets
	( new AssetManager() )->init();

	if ( ! is_cfw_page() ) {
		return;
	}

	add_action(
		'template_redirect',
		function() {
			Redirect::template_redirect();
		},
		apply_filters( 'cfw_template_redirect_priority', 11 )
	);
}

/**
 * @return AddressFieldsAugmenter|null
 */
function cfw_get_form(): AddressFieldsAugmenter {
	return AddressFieldsAugmenter::instance();
}

/**
 * @return false|string
 */
function cfw_get_logo_url() {
	$logo_attachment_id = SettingsManager::instance()->get_setting( 'logo_attachment_id' );

	return wp_get_attachment_url( $logo_attachment_id );
}

function cfw_logo() {
	/**
	 * Filters header logo / title link URL
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The link URL
	 */
	$url = apply_filters( 'cfw_header_home_url', get_home_url() );

	/**
	 * Filters header logo / title link URL
	 *
	 * @since 5.3.0
	 *
	 * @param string $url The link URL
	 */
	$blog_name = apply_filters( 'cfw_header_blog_name', get_bloginfo( 'name' ) );

	$logo_url = cfw_get_logo_url();
	?>
	<div class="cfw-logo">
		<a title="<?php echo esc_attr( html_entity_decode( $blog_name, ENT_QUOTES ) ); ?>" href="<?php echo esc_attr( $url ); ?>" class="<?php echo ! empty( $logo_url ) ? 'logo' : ''; ?>">
			<?php if ( empty( $logo_url ) ) : ?>
				<?php echo html_entity_decode( $blog_name, ENT_QUOTES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</a>
	</div>
	<?php
}

/**
 * Add WP theme styles to list of blocked style handles.
 *
 * @param $styles
 *
 * @return array
 */
function cfw_remove_theme_styles( $styles ): array {
	global $wp_styles;

	$theme_directory_uri = get_theme_root_uri();
	$theme_directory_uri = str_replace( array( 'http:', 'https:' ), '', $theme_directory_uri ); // handle both http/https/and relative protocol URLs

	foreach ( $wp_styles->registered as $wp_style ) {
		if ( ! empty( $wp_style->src ) && stripos( $wp_style->src, $theme_directory_uri ) !== false && stripos( $wp_style->src, '/checkout-wc/' ) === false ) {
			$styles[] = $wp_style->handle;
		}
	}

	return $styles;
}

/**
 * Add WP theme styles to list of blocked style handles.
 *
 * @param $scripts
 *
 * @return array
 */
function cfw_remove_theme_scripts( $scripts ): array {
	global $wp_scripts;

	$theme_directory_uri = get_theme_root_uri();
	$theme_directory_uri = str_replace( array( 'http:', 'https:' ), '', $theme_directory_uri ); // handle both http/https/and relative protocol URLs

	foreach ( $wp_scripts->registered as $wp_script ) {
		if ( ! empty( $wp_script->src ) && stripos( $wp_script->src, $theme_directory_uri ) !== false && stripos( $wp_script->src, '/checkout-wc/' ) === false ) {
			$scripts[] = $wp_script->handle;
		}
	}

	return $scripts;
}

/**
 * For gateways that add buttons above checkout form
 *
 * @param string $class
 * @param string $id
 * @param string $style
 */
function cfw_add_separator( string $class = '', string $id = '', string $style = '' ) {
	if ( ! defined( 'CFW_PAYMENT_BUTTON_SEPARATOR' ) ) {
		define( 'CFW_PAYMENT_BUTTON_SEPARATOR', true );
	} else {
		return;
	}
	?>
	<div id="payment-info-separator-wrap" class="<?php echo esc_attr( $class ); ?>">
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p <?php echo ( $id ) ? "id='{$id}'" : ''; ?> style="<?php echo empty( $style ) ? "{$style};" : ''; ?>display: none" class="pay-button-separator">
			<span>
				<?php
				/**
				 * Filters payment request button separator text
				 *
				 * @since 2.0.0
				 *
				 * @param string $separator_label The separator label (default: Or)
				 */
				echo esc_html( apply_filters( 'cfw_express_pay_separator_text', __( 'Or', 'checkout-wc' ) ) );
				?>
			</span>
		</p>
	</div>
	<?php
}

/**
 * @param string $hook
 * @param string $function_name
 * @param int $priority
 * @return false|mixed
 */
function cfw_get_hook_instance_object( string $hook, string $function_name, int $priority = 10 ) {
	global $wp_filter;

	$existing_hooks = $wp_filter[ $hook ] ?? false;

	if ( ! $existing_hooks ) {
		return false;
	}

	if ( $existing_hooks[ $priority ] ) {
		foreach ( $existing_hooks[ $priority ] as $key => $callback ) {
			if ( false !== stripos( $key, $function_name ) ) {
				return $callback['function'][0];
			}
		}
	}

	return false;
}

/**
 * @return bool
 */
function cfw_is_login_at_checkout_allowed(): bool {
	return 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );
}

function cfw_get_woocommerce_notices( $clear_notices = true ): array {
	/**
	 * Set notices
	 */
	$all_notices = WC()->session->get( 'wc_notices', array() );

	// Filter out empty messages
	foreach ( $all_notices as $key => $notice ) {
		if ( empty( array_filter( $notice ) ) ) {
			unset( $all_notices[ $key ] );
		}
	}

	/** This filter is documented in woocommerce/includes/wc-notice-functions.php **/
	$notice_types = apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );
	$notices      = array();

	foreach ( $notice_types as $notice_type ) {
		if ( count( $all_notices[ $notice_type ] ?? array() ) > 0 && isset( $all_notices[ $notice_type ] ) ) {
			$notices[ $notice_type ] = array();

			// In WooCommerce 3.9+, messages can be an array with two properties:
			// - notice
			// - data
			foreach ( $all_notices[ $notice_type ] as $notice ) {
				$notices[ $notice_type ][] = array(
					'notice' => $notice['notice'] ?? $notice,
					'data'	=> $notice['data'] ?? null,
				);
			}
		}
	}

	if ( $clear_notices ) {
		wc_clear_notices();
	}

	return $notices;
}

function cfw_remove_add_to_cart_notice( $product_id, $quantity ) {
	$add_to_cart_notice = wc_add_to_cart_message( array( $product_id => $quantity ), true, true );

	if ( wc_has_notice( $add_to_cart_notice ) ) {
		$notices                  = wc_get_notices();
		$add_to_cart_notice_index = array_search( $add_to_cart_notice, $notices['success'], true );

		unset( $notices['success'][ $add_to_cart_notice_index ] );
		wc_set_notices( $notices );
	}
}

function cfw_get_allowed_html(): array {
	$allowed_html = wp_kses_allowed_html( 'post' );

	$allowed_html = array_merge(
		$allowed_html,
		array(
			'form'   => array(
				'action'         => true,
				'accept'         => true,
				'accept-charset' => true,
				'enctype'        => true,
				'method'         => true,
				'name'           => true,
				'target'         => true,
			),

			'input'  => array(
				'type'        => true,
				'id'          => true,
				'placeholder' => true,
				'name'        => true,
				'value'       => true,
				'checked'     => true,
			),

			'button' => array(
				'type'  => true,
				'class' => true,
				'label' => true,
			),

			'svg'    => array(
				'hidden'    => true,
				'role'      => true,
				'focusable' => true,
				'xmlns'     => true,
				'width'     => true,
				'height'    => true,
				'viewbox'   => true,
			),
			'path'   => array(
				'd' => true,
			),
			'bdi'
		)
	);

	return array_map( '_wp_add_global_attributes', $allowed_html );
}

/**
 * Clean up HTML string to remove orphaned tags and other issues
 *
 * Note: The cleaned HTML is wrapped with <div></div>
 *
 * @param $html
 * @return false|string
 */
function cfw_clean_html( $html ) {
	$html = "<div>$html</div>";

	$doc = new DOMDocument();

	// Use libxml_use_internal_errors to suppress warnings from malformed HTML
	libxml_use_internal_errors(true);

	// Load HTML, specifying UTF-8 encoding
	$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();

	// Save the cleaned HTML, removing the doctype and other unwanted tags
	$cleanedHtml = $doc->saveHTML($doc->documentElement);

	// Remove the wrapping <div></div>
	return preg_replace( '/^\s*<div>(.*?)<\/div>\s*$/s', '$1', $cleanedHtml );
}