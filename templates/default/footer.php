<?php

use Objectiv\Plugins\Checkout\Managers\SettingsManager;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<footer id="cfw-footer" class="container">
	<div class="row">
		<div class="col-12">
			<div class="cfw-footer-inner entry-footer">
				<?php
				/**
				 * Fires at the top of footer
				 *
				 * @since 3.0.0
				 */
				do_action( 'cfw_before_footer' );
				?>
				<?php esc_html_e( 'Copyright' ); ?> &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>, <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.' ); ?>
				<?php

				/**
				 * Fires at the bottom of footer
				 *
				 * @since 3.0.0
				 */
				do_action( 'cfw_after_footer' );
				?>
			</div>
		</div>
	</div>
</footer>
