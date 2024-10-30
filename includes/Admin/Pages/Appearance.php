<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Model\Template;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class Appearance extends PageAbstract {
	protected $settings_manager;

	public function __construct( SettingsManager $settings_manager ) {
		$this->settings_manager = $settings_manager;

		parent::__construct( cfw__( 'Appearance', 'checkout-wc' ), 'manage_options', 'appearance' );
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 1000 );
		add_action( $this->settings_manager->prefix . '_settings_saved', array( $this, 'maybe_activate_theme' ) );

		parent::init();
	}

	public function maybe_activate_theme() {
		$prefix = $this->settings_manager->prefix;

		$new_settings = stripslashes_deep( $_REQUEST[ "{$prefix}_setting" ] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $new_settings['active_template'] ) ) {
			return;
		}

		$active_template = new Template( $this->settings_manager->get_setting( 'active_template' ) );
		$active_template->init();
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_media();
	}

	public function output() {
		$this->design_tab();
	}

	public function design_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6 mt-4">
			<?php
			cfw_admin_page_section(
				cfw__( 'Template Settings', 'checkout-wc' ),
				cfw__( 'Control how your checkout page appears.', 'checkout-wc' ),
				$this->get_global_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_global_settings() : string {
		$settings = SettingsManager::instance();
		ob_start();
		?>
		<div class="cfw-admin-field-container cfw-admin-upload-control-parent">
			<legend class="text-base font-medium text-gray-900">
				<?php echo esc_html( cfw__( 'Logo', 'checkout-wc' ) ); ?>
			</legend>
			<p class="text-sm leading-5 text-gray-500">
				<?php echo cfw_esc_html__( 'Choose the logo you wish to display in the header. If you do not choose a logo we will use your site name.', 'checkout-wc' ); ?>
			</p>
			<div class="cfw-admin-image-preview-wrapper mb-4 mt-4">
				<img class="cfw-admin-image-preview" src='<?php echo esc_attr( wp_get_attachment_url( $settings->get_setting( 'logo_attachment_id' ) ) ); ?>' width='100' style='max-height: 100px; width: 100px;'>
			</div>
			<input class="cfw-admin-image-picker-button button" type="button" value="<?php cfw_e( 'Upload image' ); ?>" />
			<input type='hidden' name='<?php echo esc_attr( $settings->get_field_name( 'logo_attachment_id' ) ); ?>' id='logo_attachment_id' value="<?php echo esc_attr( $settings->get_setting( 'logo_attachment_id' ) ); ?>">

			<a class="delete-custom-img button secondary-button"><?php cfw_e( 'Clear Logo', 'checkout-wc' ); ?></a>
		</div>

		<?php

		echo $this->get_template_settings(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return ob_get_clean();
	}

	protected function get_template_settings() {
		$template_path = cfw_get_active_template()->get_slug();

		ob_start();

		$this->output_textarea_row(
			'custom_css',
			cfw__( 'Custom CSS', 'checkout-wc' ),
			cfw__( 'Add Custom CSS rules to fully control the appearance of the checkout template.', 'checkout-wc' ),
			array(
				'setting_seed' => array( $template_path ),
			)
		);
		?>

		<?php foreach ( $this->get_theme_color_settings() as $color_settings_section ) : ?>
			<?php
			if ( empty( $color_settings_section['settings'] ) ) {
				continue;
			}
			?>
			<div class="cfw-admin-field-container">
				<h3 class="text-lg leading-6 font-medium text-gray-900">
					<?php echo esc_html( $color_settings_section['title'] ); ?>
				</h3>

				<div class="flex flex-wrap">
					<?php foreach ( $color_settings_section['settings'] as $key => $label ) : ?>
						<?php
						$this->output_color_picker_input(
							$key,
							$label,
							cfw_get_active_template()->get_default_setting( $key ),
							array(
								'setting_seed'       => array( $template_path ),
								'additional_classes' => array( 'w-1/3' ),
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<?php
		return ob_get_clean();
	}

	public function get_current_tab() {
		return empty( $_GET['subpage'] ) ? false : sanitize_text_field( $_GET['subpage'] );
	}

	/**
	 * @return array
	 */
	public static function get_theme_color_settings(): array {
		$active_template = cfw_get_active_template();
		$color_settings  = array();

		// Body
		$color_settings['body'] = array(
			'title'    => 'Body',
			'settings' => array(),
		);

		$color_settings['body']['settings']['body_background_color'] = cfw__( 'Body Background Color', 'checkout-wc' );
		$color_settings['body']['settings']['body_text_color']       = cfw__( 'Body Text Color', 'checkout-wc' );
		$color_settings['body']['settings']['link_color']            = cfw__( 'Link Color', 'checkout-wc' );

		// Header
		$color_settings['header'] = array(
			'title'    => 'Header',
			'settings' => array(),
		);

		if ( $active_template->supports( 'header-background' ) ) {
			$color_settings['header']['settings']['header_background_color'] = cfw__( 'Header Background Color', 'checkout-wc' );
		}

		$color_settings['header']['settings']['header_text_color'] = cfw__( 'Header Text Color', 'checkout-wc' );

		// Footer
		$color_settings['footer'] = array(
			'title'    => 'Footer',
			'settings' => array(),
		);

		if ( $active_template->supports( 'footer-background' ) ) {
			$color_settings['footer']['settings']['footer_background_color'] = cfw__( 'Footer Background Color', 'checkout-wc' );
		}

		$color_settings['footer']['settings']['footer_color'] = cfw__( 'Footer Text Color', 'checkout-wc' );

		// Cart Summary
		$color_settings['cart_summary'] = array(
			'title'    => 'Cart Summary',
			'settings' => array(),
		);

		if ( $active_template->supports( 'summary-background' ) ) {
			$color_settings['cart_summary']['settings']['summary_background_color'] = cfw__( 'Summary Background Color', 'checkout-wc' );
			$color_settings['cart_summary']['settings']['summary_text_color']       = cfw__( 'Summary Text Color', 'checkout-wc' );
		}

		$color_settings['cart_summary']['settings']['summary_link_color'] = cfw__( 'Summary Link Color', 'checkout-wc' );

		$color_settings['cart_summary']['settings']['summary_mobile_background_color'] = cfw__( 'Summary Mobile Background Color', 'checkout-wc' );

		$color_settings['cart_summary']['settings']['cart_item_quantity_color']      = cfw__( 'Item Quantity Bubble Background Color', 'checkout-wc' );
		$color_settings['cart_summary']['settings']['cart_item_quantity_text_color'] = cfw__( 'Item Quantity Bubble Text Color', 'checkout-wc' );

		// Breadcrumbs
		$color_settings['breadcrumbs'] = array(
			'title'    => 'Breadcrumbs',
			'settings' => array(),
		);

		if ( $active_template->supports( 'breadcrumb-colors' ) ) {
			$color_settings['breadcrumbs']['settings']['breadcrumb_completed_text_color']   = cfw__( 'Completed Breadcrumb Completed Text Color', 'checkout-wc' );
			$color_settings['breadcrumbs']['settings']['breadcrumb_current_text_color']     = cfw__( 'Current Breadcrumb Text Color', 'checkout-wc' );
			$color_settings['breadcrumbs']['settings']['breadcrumb_next_text_color']        = cfw__( 'Next Breadcrumb Text Color', 'checkout-wc' );
			$color_settings['breadcrumbs']['settings']['breadcrumb_completed_accent_color'] = cfw__( 'Completed Breadcrumb Accent Color', 'checkout-wc' );
			$color_settings['breadcrumbs']['settings']['breadcrumb_current_accent_color']   = cfw__( 'Current Breadcrumb Accent Color', 'checkout-wc' );
			$color_settings['breadcrumbs']['settings']['breadcrumb_next_accent_color']      = cfw__( 'Next Breadcrumb Accent Color', 'checkout-wc' );
		}

		$color_settings['buttons'] = array(
			'title'    => 'Buttons',
			'settings' => array(),
		);

		// Buttons
		$color_settings['buttons']['settings']['button_color']                      = cfw__( 'Primary Button Background Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['button_text_color']                 = cfw__( 'Primary Button Text Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['button_hover_color']                = cfw__( 'Primary Button Background Hover Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['button_text_hover_color']           = cfw__( 'Primary Button Text Hover Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['secondary_button_color']            = cfw__( 'Secondary Button Background Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['secondary_button_text_color']       = cfw__( 'Secondary Button Text Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['secondary_button_hover_color']      = cfw__( 'Secondary Button Background Hover Color', 'checkout-wc' );
		$color_settings['buttons']['settings']['secondary_button_text_hover_color'] = cfw__( 'Secondary Button Text Hover Color', 'checkout-wc' );

		// Theme Specific Colors
		$color_settings['active_theme_colors'] = array(
			'title'    => 'Theme Specific Colors',
			'settings' => apply_filters( 'cfw_active_theme_color_settings', array() ),
		);

		return apply_filters( 'cfw_theme_color_settings', $color_settings );
	}
}
