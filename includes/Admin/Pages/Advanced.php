<?php

namespace Objectiv\Plugins\Checkout\Admin\Pages;

use Objectiv\Plugins\Checkout\Admin\TabNavigation;
use Objectiv\Plugins\Checkout\Managers\SettingsManager;

/**
 * @link checkoutwc.com
 * @since 5.0.0
 * @package Objectiv\Plugins\Checkout\Admin\Pages
 */
class Advanced extends PageAbstract {
	protected $tab_navigation;

	public function __construct() {
		parent::__construct( cfw__( 'Advanced', 'checkout-wc' ), 'manage_options', 'advanced' );
	}

	public function init() {
		add_action( 'wp_ajax_cfw_generate_settings', array( $this, 'generate_settings_export' ) );
		add_action( 'admin_init', array( $this, 'maybe_upload_settings' ), 0 );

		parent::init();
	}

	public function output() {
		$this->tab_navigation = new TabNavigation( 'Advanced', 'subpage' );

		$this->tab_navigation->add_tab( 'Advanced', add_query_arg( array( 'subpage' => 'advanced' ), $this->get_url() ) );
		$this->tab_navigation->add_tab( 'Scripts', add_query_arg( array( 'subpage' => 'scripts' ), $this->get_url() ) );

		if ( $this->get_current_tab() === false ) {
			$_GET['subpage'] = 'advanced';
		}

		$current_tab_function = $this->get_current_tab() === false ? 'scripts_tab' : $this->get_current_tab() . '_tab';
		$callable             = array( $this, $current_tab_function );

		$this->tab_navigation->display_tabs();

		call_user_func( $callable );
	}

	public function scripts_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6 mt-4">
			<?php
			cfw_admin_page_section(
				cfw__( 'Checkout', 'checkout-wc' ),
				'Add custom JavaScript and PHP that runs on the checkout page.',
				$this->get_checkout_script_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	protected function get_checkout_script_settings() {
		ob_start();

		$this->output_textarea_row(
			'header_scripts_checkout',
			cfw__( 'Header Scripts', 'checkout-wc' ),
			cfw__( 'This code will output immediately before the closing <code>&lt;/head&gt;</code> tag in the document source.', 'checkout-wc' )
		);

		$this->output_textarea_row(
			'footer_scripts_checkout',
			cfw__( 'Footer Scripts', 'checkout-wc' ),
			cfw__( 'This code will output immediately before the closing <code>&lt;/body&gt;</code> tag in the document source.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	public function advanced_tab() {
		$this->output_form_open();
		?>
		<div class="space-y-6 mt-4">
			<?php
			cfw_admin_page_section(
				cfw__( 'Other', 'checkout-wc' ),
				cfw__( 'We are great at categorizing things!', 'checkout-wc' ),
				$this->get_other_settings()
			);
			?>
		</div>
		<?php
		$this->output_form_close();
	}

	public function get_other_settings() {
		ob_start();

		$this->output_checkbox_row(
			'hide_admin_bar_button',
			cfw__( 'Hide Admin Menu Bar Button', 'checkout-wc' ),
			cfw__( 'Hide the CheckoutWC admin menu bar button unless you are on the checkout page, or one of the checkout endpoints such as thank you and order pay.', 'checkout-wc' )
		);

		return ob_get_clean();
	}

	public function get_export_settings() {
		ob_start();
		?>
		<input id="export_settings_button" type="button" class="button" data-nonce="<?php echo esc_attr( wp_create_nonce( '_cfw__export_settings' ) ); ?>" value="<?php cfw_e( 'Export Settings', 'checkout-wc' ); ?>" />

		<p id="small-description" class="text-gray-500">
			<?php cfw_e( 'Download a backup file of your settings.', 'checkout-wc' ); ?>
		</p>
		<?php
		return ob_get_clean();
	}

	public function get_import_settings() {
		ob_start();
		?>
		<input name="uploaded_settings" type="file" class="" value="<?php cfw_e( 'Import Settings', 'checkout-wc' ); ?>" />
		<?php wp_nonce_field( 'import_cfw_settings_nonce' ); ?>
		<div>
			<input id="import_settings_button" type="submit" class="button" name="import_cfw_settings" value="<?php cfw_e( 'Upload File and Import Settings', 'checkout-wc' ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	public function get_current_tab() {
		return empty( $_GET['subpage'] ) ? false : wc_clean( $_GET['subpage'] );
	}

	/**
	 * Generate Settings JSON file
	 *
	 * @since  3.8.0
	 *
	 * @return void
	 */
	public static function generate_settings_export() {
		// Bail if not admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		// Bail if nonce check fails.
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ?? '' ), '_cfw__export_settings' ) ) {
			wp_die();
		}

		$settings = get_option( '_cfw__settings' );

		$settings['logo_attachment_url'] = wp_get_attachment_url( $settings['logo_attachment_id'] );

		if ( ! empty( $settings ) ) {
			echo wp_json_encode( $settings );
			wp_die();
		}

		wp_die();
	}

	/**
	 * Upload Settings
	 *
	 * @since  3.8.0
	 *
	 * @return void
	 */
	public function maybe_upload_settings() {
		// Make sure we're an admin and that we have a valid request
		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['import_cfw_settings'] ) ) {
			return;
		}

		if ( ! current_user_can('upload_files') || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ?? '' ), 'import_cfw_settings_nonce' ) || ( empty( $_FILES['uploaded_settings'] ) || 0 === $_FILES['uploaded_settings']['size'] ?? 0 ) ) {
			add_action(
				'admin_notices',
				function() {
					$important = '';
					if ( isset( $_GET['page'] ) && 'cfw-settings' === $_GET['page'] ) {
						$important = "style='display:block !important'";
					}
					?>
					<div <?php echo esc_html( $important ); ?> class="notice notice-error is-dismissible checkout-wc">
						<p><?php cfw_e( 'CheckoutWC: Unable to import settings. Did you select a JSON file to upload?', 'checkout-wc' ); ?></p>
					</div>
					<?php
				}
			);

			return;
		}

		$upload = ! empty( $_FILES['uploaded_settings'] ) ? $_FILES['uploaded_settings'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! empty( $upload ) ) {
			$file_tmp_path  = $upload['tmp_name'];
			$file_name      = $upload['name'];
			$file_name_cmps = explode( '.', $file_name );
			$file_extension = strtolower( end( $file_name_cmps ) );

			$new_file_name = md5( time() . $file_name ) . '.' . $file_extension;

			if ( 'json' === $file_extension ) {
				$wp_uploads = wp_upload_dir();
				$upload_dir = trailingslashit( $wp_uploads['basedir'] );
				$dest_path  = $upload_dir . $new_file_name;

				if ( move_uploaded_file( $file_tmp_path, $dest_path ) ) {
					$contents = file_get_contents( $dest_path );
					$decoded  = json_decode( $contents, JSON_OBJECT_AS_ARRAY );

					if ( ! is_null( $decoded ) && isset( $decoded['logo_attachment_id'] ) && ! empty( $decoded['logo_attachment_id'] && false !== $decoded['logo_attachment_url'] ) ) {
						$image_upload                  = $this->upload_logo( $decoded['logo_attachment_url'] );
						$decoded['logo_attachment_id'] = $image_upload ? $image_upload : '';
					} else {
						wp_die( 'An error occurred while importing settings!' );
					}

					update_option( '_cfwlite__settings_backup', get_option( '_cfwlite__settings' ) ); // backup settings to be safe
					update_option( '_cfwlite__settings', $decoded );

					unlink( $dest_path );

					add_action(
						'admin_notices',
						function() {
							$important = '';
							if ( isset( $_GET['page'] ) && 'cfw-settings' === $_GET['page'] ) {
								$important = "style='display:block !important'";
							}
							?>
							<div <?php echo esc_html( $important ); ?> class="notice notice-success is-dismissible checkout-wc">
								<p><?php cfw_e( 'CheckoutWC: Successfully imported settings.', 'checkout-wc' ); ?></p>
							</div>
							<?php
						}
					);
				}
			}
		}
	}

	/**
	 * Upload Logo
	 *
	 * @param $file_url
	 * @return int|\WP_Error
	 * @since  3.8.0
	 */
	public function upload_logo( $file_url ) {
		$filename = basename( $file_url );

		add_filter( 'https_ssl_verify', '__return_false' );
		$logo = wp_remote_get( $file_url );

		if ( is_wp_error( $logo ) ) {
			wp_die( 'An error occurred retrieving logo.' );
		}

		$upload_file = wp_upload_bits( $filename, null, wp_remote_retrieve_body( $logo ) );

		if ( ! $upload_file['error'] ) {
			$wp_file_type = wp_check_filetype( $filename, null );

			$attachment = array(
				'post_mime_type' => $wp_file_type['type'],
				'post_parent'    => 0,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], 0 );

			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}

			return $attachment_id;
		}

		return '';
	}
}
