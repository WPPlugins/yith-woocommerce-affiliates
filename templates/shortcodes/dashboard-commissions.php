<?php
/**
 * Affiliate Dashboard Commissions
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

<div class="yith-wcaf yith-wcaf-commissions woocommerce">

	<?php
	if( function_exists( 'wc_print_notices' ) ){
		wc_print_notices();
	}
	?>

	<?php do_action( 'yith_wcaf_before_dashboard_section', 'commissions' ) ?>

	<div class="filters">
		<form>
			<div class="filters-row">
				<input type="hidden" class="product-search wc-product-search" name="product_id" data-placeholder="<?php _e( 'Product:', 'yith-woocommerce-affiliates' ) ?>" value="<?php echo esc_attr( $product_id )?>" data-selected="<?php echo esc_attr( $product_name ) ?>" />
				<input type="text" class="datepicker" name="from" placeholder="<?php _e( 'From:', 'yith-woocommerce-affiliates' ) ?>" value="<?php echo esc_attr( $from )?>"/>
				<input type="text" class="datepicker" name="to" placeholder="<?php _e( 'To:', 'yith-woocommerce-affiliates' ) ?>" value="<?php echo esc_attr( $to )?>"/>
			</div>
			<div class="filters-row">
				<label for="per_page" class="per-page">
					<?php _e( 'Items per page:', 'yith-woocommerce-affiliates' ) ?>
					<input max="100" min="1" step="1" type="number" name="per_page" value="<?php echo esc_attr( $per_page )?>"/>
				</label>
			</div>
			<div class="button-row">
				<input type="submit" value="<?php _e( 'Filter', 'yith-woocommerce-affiliates' ) ?>"/>
				<?php if( $filter_set ): ?>
					<a href="<?php echo $dashboard_commissions_link ?>"><?php _e( 'Reset', 'yith-woocommerce-affiliates' ) ?></a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<table class="shop_table">
		<thead>
		<tr>
			<th class="column-id">
				<a class="<?php echo ( $ordered == 'ID' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'ID', 'order' => $to_order ) ) ) ?>"><?php _e( 'ID', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-date">
				<a class="<?php echo ( $ordered == 'created_at' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'created_at', 'order' => $to_order ) ) ) ?>"><?php _e( 'Date', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-status">
				<a class="<?php echo ( $ordered == 'status' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'status', 'order' => $to_order ) ) ) ?>"><?php _e( 'Status', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-product">
				<a class="<?php echo ( $ordered == 'product_name' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'product_name', 'order' => $to_order ) ) ) ?>"><?php _e( 'Product', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-rate">
				<a class="<?php echo ( $ordered == 'rate' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'rate', 'order' => $to_order ) ) ) ?>"><?php _e( 'Rate', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
			<th class="column-amount">
				<a class="<?php echo ( $ordered == 'amount' ) ? 'ordered to-order-' . strtolower( $to_order )  : '' ?>" href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'amount', 'order' => $to_order ) ) ) ?>"><?php _e( 'Amount', 'yith-woocommerce-affiliates' ) ?></a>
			</th>
		</tr>
		</thead>
		<tbody>
			<?php if( ! empty( $commissions ) ): ?>
				<?php foreach( $commissions as $commission ): ?>
					<tr>
						<td class="column-id">#<?php echo esc_attr( $commission['ID'] ) ?></td>
						<td class="column-date"><?php echo esc_attr( date_i18n( wc_date_format(), strtotime( $commission['created_at'] ) ) ) ?></td>
						<td class="column-status"><a href="<?php echo esc_url( add_query_arg( 'status', $commission['status'] ) ) ?>"><?php echo esc_attr( YITH_WCAF_Commission_Handler()->get_readable_status( $commission['status'] ) ) ?></a></td>
						<td class="column-product"><a href="<?php echo esc_url( get_permalink( $commission['product_id'] ) ) ?>"><?php echo esc_attr( $commission['product_name'] )?></a></td>
						<td class="column-rate"><?php echo number_format( $commission['rate'], 2 ) ?> %</td>
						<td class="column-amount"><?php echo wc_price( $commission['amount'] ) ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td class="empty-set" colspan="6"><?php _e( 'Sorry! There are no registered commissions yet', 'yith-woocommerce-affiliates' ) ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if( ! empty( $page_links ) ): ?>
		<nav class="woocommerce-pagination">
			<?php echo $page_links ?>
		</nav>
	<?php endif; ?>

	<?php do_action( 'yith_wcaf_after_dashboard_section', 'commissions' ) ?>
</div>