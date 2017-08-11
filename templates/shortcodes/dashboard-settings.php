<?php
/**
 * Affiliate Dashboard Settings
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Affiliates
 * @version 1.0.5
 */

/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'YITH_WCAF' ) ) {
	exit;
} // Exit if accessed directly
?>

<div class="yith-wcaf yith-wcaf-settings woocommerce">

	<?php
	if( function_exists( 'wc_print_notices' ) ){
		wc_print_notices();
	}
	?>

	<?php do_action( 'yith_wcaf_before_dashboard_section', 'settings' ) ?>

	<form method="post">

		<?php if( apply_filters( 'yith_wcaf_payment_email_required', true ) ): ?>
		<p class="form form-row">
			<label for="payment_email"><?php _e( 'Payment email', 'yith-woocommerce-affiliates' ) ?></label>
			<input type="email" name="payment_email" id="payment_email" value="<?php echo $payment_email ?>" />
			<small><?php _e( '(Email address where you want to receive PayPal payments for commissions)', 'yith-woocommerce-affiliates' ) ?></small>
		</p>
		<?php endif; ?>

		<input type="submit" name="settings_submit" value="<?php _e( 'Submit', 'yith-woocommerce-affiliates' ) ?>" />

	</form>

	<?php do_action( 'yith_wcaf_after_dashboard_section', 'settings' ) ?>

</div>