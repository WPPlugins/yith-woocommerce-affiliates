<?php
/**
 * Uninstall plugin
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

// If uninstall not called from WordPress exit
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete option from options table
delete_option( 'yith_wcaf_db_version' );

//delete pages created for this plugin
wp_delete_post( get_option( 'yith_wcaf_dashboard_page_id' ), true );

//remove any additional options and custom table
$sql = "DROP TABLE `" . $wpdb->yith_affiliates . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_commissions . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_commission_notes . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_clicks . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_payments . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_payment_commission . "`";
$wpdb->query( $sql );
$sql = "DROP TABLE `" . $wpdb->yith_payment_notes . "`";
$wpdb->query( $sql );

?>