<?php
/**
 * Affiliate Admin Panel
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Affiliates
 * @version 1.0.0
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

<div id="yith_wcaf_panel_affiliate">
	<form id="plugin-fw-wc" class="affiliate-table" method="get">
		<input type="hidden" name="page" value="yith_wcaf_panel"/>
		<input type="hidden" name="tab" value="affiliates"/>

		<h3><?php _e( 'Add an affiliate', 'yith-woocommerce-affiliates' ) ?></h3>
		<div class="yith-new-affiliate">
			<h4><?php _e( 'Add new affiliate', 'yith-woocommerce-affiliates' ) ?></h4>
			<?php
			yit_add_select2_fields( array(
				'class' => 'yith-affiliate-select wc-customer-search',
				'name' => 'yith_new_affiliate',
				'data-placeholder' => __( 'Search for a customer&hellip;', 'yith-woocommerce-affiliates' ),
				'style' => 'min-width: 300px;',
				'value' => ''
			) );
			?>
			<input type="submit" class="yith-affiliate-submit button button-primary" value="<?php echo esc_attr( __( 'Add Existing', 'yith-woocommerce-affiliates' ) ) ?>" />
			<?php _e( 'or', 'yith-woocommerce-affiliates' ) ?>
			<a href="<?php echo admin_url( 'user-new.php' ) ?>" class="button button-secondary yith-affiliate-new"><?php _e( 'Create New', 'yith-woocommerce-affiliates' ) ?></a>
		</div>

		<div class="clear separator"></div>

		<h3><?php _e( 'Affiliates', 'yith-woocommerce-affiliates' ) ?></h3>
		<div class="yith-affiliates">
			<?php
			$affiliates_table->views();
			$affiliates_table->search_box( 'Search affiliate', 'affiliate' );
			$affiliates_table->display();
			?>
		</div>
	</form>
</div>