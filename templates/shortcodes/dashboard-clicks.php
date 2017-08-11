<?php
/**
 * Affiliate Dashboard
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

<div class="yith-wcaf yith-wcaf-clicks woocommerce">

	<?php
	if( function_exists( 'wc_print_notices' ) ){
		wc_print_notices();
	}
	?>

	<?php do_action( 'yith_wcaf_before_dashboard_section', 'clicks' ) ?>

	<div class="filters">
		<form>
			<div class="filters-row">
				<input type="text" class="datepicker" name="from" placeholder="<?php _e( 'From:', 'yith-woocommerce-affiliates' ) ?>" value="<?php echo esc_attr( $from )?>"/>
				<input type="text" class="datepicker" name="to" placeholder="<?php _e( 'To:', 'yith-woocommerce-affiliates' ) ?>" value="<?php echo esc_attr( $to )?>"/>
				<label for="per_page" class="per-page">
					<?php _e( 'Items per page:', 'yith-woocommerce-affiliates' ) ?>
					<input max="100" min="1" step="1" type="number" name="per_page" value="<?php echo esc_attr( $per_page )?>"/>
				</label>
			</div>
			<div class="button-row">
				<input type="submit" value="<?php _e( 'Filter', 'yith-woocommerce-affiliates' ) ?>"/>
				<?php if( $filter_set ): ?>
					<a href="<?php echo $dashboard_clicks_link ?>"><?php _e( 'Reset', 'yith-woocommerce-affiliates' ) ?></a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<table class="shop_table">
		<thead>
		<tr>
			<th class="column-date">
				<a class="<?php echo ( $ordered == 'click_date' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'click_date', 'order' => $to_order ) ) ) ?>"><?php _e( 'Date', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-status">
				<a class="<?php echo ( $ordered == 'order_id' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'order_id', 'order' => $to_order ) ) ) ?>"><?php _e( 'Status', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-link">
				<a class="<?php echo ( $ordered == 'link' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'link', 'order' => $to_order ) ) ) ?>"><?php _e( 'Link', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-origin">
				<a class="<?php echo ( $ordered == 'origin' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'origin', 'order' => $to_order ) ) ) ?>"><?php _e( 'Origin', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-origin-base">
				<a class="<?php echo ( $ordered == 'origin_base' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'origin_base', 'order' => $to_order ) ) ) ?>"><?php _e( 'Origin Base', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php if( ! empty( $clicks ) ): ?>
			<?php foreach( $clicks as $click ): ?>
				<tr>
					<td class="column-date"><?php echo esc_attr( date_i18n( wc_date_format(), strtotime( $click['click_date'] ) ) ) ?></td>
					<td class="column-status"><a href="<?php echo esc_url( add_query_arg( 'status', ! empty( $click['order_id'] ) ? 'converted' : 'not-converted' ) ) ?>"><?php echo ( ! empty( $click['order_id'] ) ? __( 'Converted', 'yith-woocommerce-affiliates' ) : __( 'Not Converted', 'yith-woocommerce-affiliates' ) ) ?></a></td>
					<td class="column-product"><?php echo esc_url( $click['link'] )?></td>
					<td class="column-rate"><?php echo ! empty( $click['origin'] ) ? esc_url( $click['origin'] ) : __( 'N/A', 'yith-woocommerce-affiliates' ) ?></td>
					<td class="column-amount"><?php echo ! empty( $click['origin_base'] ) ? esc_url( $click['origin_base'] ) : __( 'N/A', 'yith-woocommerce-affiliates' ) ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td class="empty-set" colspan="5"><?php _e( 'Sorry! There are no registered hits yet', 'yith-woocommerce-affiliates' ) ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if( ! empty( $page_links ) ): ?>
		<nav class="woocommerce-pagination">
			<?php echo $page_links ?>
		</nav>
	<?php endif; ?>

	<?php do_action( 'yith_wcaf_after_dashboard_section', 'clicks' ) ?>
</div>