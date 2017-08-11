<?php
/**
 * Affiliate Dashboard Summary
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

<div class="yith-wcaf yith-wcaf-dashboard-summary woocommerce">

	<?php
	if( function_exists( 'wc_print_notices' ) ){
		wc_print_notices();
	}
	?>

	<?php do_action( 'yith_wcaf_before_dashboard_summary' ) ?>

	<p class="myaccount_user">
		<?php echo $greeting_message ?>
	</p>

	<div class="dashboard-content">
		<?php if( $show_left_column ): ?>
			<div class="left-column <?php echo ( ! $show_right_column ) ? 'full-width' : '' ?>">
				<!--AFFILIATE STATS-->
				<?php if( $show_referral_stats ): ?>
					<div class="dashboard-title">
						<h2><?php _e( 'Stats', 'yith-woocommerce-affiliates' ) ?></h2>
					</div>

					<table class="shop_table">
						<tbody>
						<tr>
							<th><?php _e( 'Affiliate rate', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo number_format( $referral_stats['rate'], 2 ) ?> %</td>
						</tr>

						<tr>
							<th><?php _e( 'Total Earnings', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo wc_price( $referral_stats['earnings'] ) ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Total Paid', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo wc_price( $referral_stats['paid'] ) ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Total Refunded', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo wc_price( $referral_stats['refunds'] ) ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Balance', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo wc_price( $referral_stats['balance'] ) ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Visits', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo $referral_stats['click'] ?></td>
						</tr>

						<tr>
							<th><?php _e( 'Conversion rate', 'yith-woocommerce-affiliates' )?></th>
							<td><?php echo ! empty( $referral_stats['conv_rate'] ) ? number_format( $referral_stats['conv_rate'], 2 ) : __( 'N/A', 'yith-woocommerce-affiliates' ) ?> %</td>
						</tr>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if( $show_right_column ): ?>
			<div class="right-column <?php echo ( ! $show_left_column ) ? 'full-width' : '' ?>">
				<!--CLICKS SUMMARY-->
				<?php if( $show_dashboard_links ): ?>
					<div class="dashboard-title">
						<h2><?php _e( 'Menu', 'yith-woocommerce-affiliates' ) ?></h2>
					</div>
					<ul class="dashboard-links">
						<li><a href="<?php echo $dashboard_links['commissions'] ?>"><?php _e( 'Commissions', 'yith-woocommerce-affiliates' ) ?></a></li>
						<li><a href="<?php echo $dashboard_links['clicks'] ?>"><?php _e( 'Clicks', 'yith-woocommerce-affiliates' ) ?></a></li>
						<li><a href="<?php echo $dashboard_links['payments'] ?>"><?php _e( 'Payments', 'yith-woocommerce-affiliates' ) ?></a></li>
						<li><a href="<?php echo $dashboard_links['generate_link'] ?>"><?php _e( 'Generate link', 'yith-woocommerce-affiliates' ) ?></a></li>
						<li><a href="<?php echo $dashboard_links['settings'] ?>"><?php _e( 'Settings', 'yith-woocommerce-affiliates' ) ?></a></li>
						<?php do_action( 'yith_wcaf_after_dashboard_links', $dashboard_links ) ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<!--COMMISSION SUMMARY-->
	<?php if( $show_commissions_summary ): ?>
		<div class="dashboard-title">
			<h2><?php _e( 'Recent Commissions', 'yith-woocommerce-affiliates' ) ?></h2>
			<span class="view-all">(<a href="<?php echo $dashboard_links['commissions'] ?>"><?php _e( 'View all', 'yith-woocommerce-affiliates' ) ?></a>)</span>
		</div>

		<table class="shop_table">
			<thead>
			<tr>
				<th class="commission-ID"><span class="nobr"><?php _e( 'ID', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="commission-status"><span class="nobr"><?php _e( 'Status', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="commission-rate"><span class="nobr"><?php _e( 'Rate', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="commission-amount"><span class="nobr"><?php _e( 'Amount', 'yith-woocommerce-affiliates' ) ?></span></th>
			</tr>
			</thead>
			<tbody>
			<?php if( ! empty( $commissions ) ): ?>
				<?php foreach( $commissions as $commission ): ?>
					<tr>
						<td class="commission-ID">#<?php echo esc_attr( $commission['ID'] ) ?></td>
						<td class="commission-status"><?php echo esc_attr( YITH_WCAF_Commission_Handler()->get_readable_status( $commission['status'] ) ) ?></td>
						<td class="commission-rate"><?php echo number_format( $commission['rate'], 2 ) ?> %</td>
						<td class="commission-amount"><?php echo wc_price( $commission['amount'] ) ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4" class="empty-set"><?php _e( 'Sorry! There are no registered commissions yet', 'yith-woocommerce-affiliates' ) ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<!--CLICKS SUMMARY-->
	<?php if( $show_clicks_summary ): ?>
		<div class="dashboard-title">
			<h2><?php _e( 'Recent Clicks', 'yith-woocommerce-affiliates' ) ?></h2>
			<span class="view-all">(<a href="<?php echo $dashboard_links['clicks'] ?>"><?php _e( 'View all', 'yith-woocommerce-affiliates' ) ?></a>)</span>
		</div>

		<table class="shop_table">
			<thead>
			<tr>
				<th class="click-link"><span class="nobr"><?php _e( 'Link', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="click-origin"><span class="nobr"><?php _e( 'Origin', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="click-status"><span class="nobr"><?php _e( 'Status', 'yith-woocommerce-affiliates' ) ?></span></th>
				<th class="click-date"><span class="nobr"><?php _e( 'Date', 'yith-woocommerce-affiliates' ) ?></span></th>
			</tr>
			</thead>
			<tbody>
			<?php if( ! empty( $clicks ) ): ?>
				<?php foreach( $clicks as $click ): ?>
					<tr>
						<td class="click-link"><?php echo esc_url( $click['link'] ) ?></td>
						<td class="click-origin"><?php echo ! empty( $click['origin_base'] ) ? esc_url( $click['origin_base'] ) : __( 'N/A', 'yith-woocommerce-affiliates' ) ?></td>
						<td class="click-status"><?php echo ! empty( $click['order_id'] ) ? __( 'Converted', 'yith-woocommerce-affiliates' ) : __( 'Not converted', 'yith-woocommerce-affiliates' ) ?></td>
						<td class="click-date"><time datetime="<?php echo date( 'Y-m-d', strtotime( $click['click_date'] ) ); ?>" title="<?php echo esc_attr( strtotime( $click['click_date'] ) ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $click['click_date'] ) ); ?></time></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4" class="empty-set"><?php _e( 'Sorry! There are no registered commissions yet', 'yith-woocommerce-affiliates' ) ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php do_action( 'yith_wcaf_after_dashboard_summary' ) ?>

</div>