<?php

namespace Objectiv\Plugins\Checkout\Admin\Notices;

use Objectiv\Plugins\Checkout\Managers\SettingsManager;

class TemplateDisabledNotice extends NoticeAbstract {
	public function maybe_show() {
		$enabled = SettingsManager::instance()->get_setting( 'enable' ) === 'yes';

		if ( $enabled ) {
			return;
		}
		?>
		<div class='notice notice-warning checkout-wc'>
			<h4>
				<?php cfw_e( 'CheckoutWC Lite Template Deactivated', 'checkout-wc' ); ?>
			</h4>

			<p>
				<?php echo sprintf( cfw_esc_html__( 'The CheckoutWC Lite checkout template is disabled for normal visitors. To fix this, go to %s > %s and toggle "%s".', 'checkout-wc' ), cfw_esc_html__( 'Settings', 'checkout-wc' ), cfw_esc_html__( 'Start Here', 'checkout-wc' ), cfw_esc_html__( 'Activate CheckoutWC Template', 'checkout-wc' ) ); ?>
			</p>
		</div>
		<?php
	}
}
