<?php
/**
 * Shortcode class
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

if ( ! class_exists( 'YITH_WCAF_Shortcode' ) ) {
	/**
	 * WooCommerce Affiliate Shortcode
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAF_Shortcode {

		/**
		 * Performs all required add_shortcode
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public static function init() {
			add_shortcode( 'yith_wcaf_registration_form', array( 'YITH_WCAF_Shortcode', 'registration_form' ) );
			add_shortcode( 'yith_wcaf_affiliate_dashboard', array( 'YITH_WCAF_Shortcode', 'affiliate_dashboard' ) );
			add_shortcode( 'yith_wcaf_link_generator', array( 'YITH_WCAF_Shortcode', 'link_generator' ) );
		}

		/**
		 * Returns output for affiliates registration form
		 *
		 * @param $atts mixed Array of shortcodes attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function registration_form( $atts = array() ){
			$defaults = array(
				'show_name_field' => get_option( 'yith_wcaf_referral_registration_show_name_field' ),
				'show_surname_field' => get_option( 'yith_wcaf_referral_registration_show_surname_field' )
			);

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			$template_name = 'registration-form.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Returns output for llink generato form
		 *
		 * @param $atts mixed Array of shortcodes attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function link_generator( $atts = array() ){
			// only for consistency with other methods
			$defaults = array();

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			$original_url = isset( $_REQUEST['original_url'] ) ? esc_url( $_REQUEST['original_url'] ) : false;

			// check if original url is a local url
			if( $original_url ) {
				$parsed_original_url = parse_url( $original_url );
				$original_host = str_replace( 'www.', '', $parsed_original_url['host'] );
				$server_name = str_replace( 'www.', '', $_SERVER['SERVER_NAME'] );

				$is_hosted = $original_host == $server_name;

				if (  ! apply_filters( 'yith_wcaf_is_hosted', $is_hosted, $original_host, $server_name )  ) {
					$original_url = false;
				}
			}

			// generate referral url
			$request_token = false;
			$request_user_name = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : false;
			$request_user = get_user_by( 'login', $request_user_name );

			if( $request_user ){
				$request_affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $request_user->ID );

				if( $request_affiliate ){
					$request_token = $request_affiliate['token'];
				}
			}

			$generated_url = YITH_WCAF()->get_referral_url( $request_token, $original_url );

			if ( is_user_logged_in() ) {
				$user_id         = get_current_user_id();
				$user            = get_user_by( 'id', $user_id );
				$affiliate       = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );
				$affiliate_id    = isset( $affiliate['ID'] ) ? $affiliate['ID'] : false;
				$affiliate_token = isset( $affiliate['token'] ) ? $affiliate['token'] : false;
				$referral_link   = ! empty( $affiliate_token ) ? YITH_WCAF()->get_referral_url() : '';
			}

			$atts = array_merge(
				$atts,
				array(
					'user_id' => isset( $user_id ) ? $user_id : false,
					'user' => isset( $user ) ? $user : false,
					'affiliate' => isset( $affiliate ) ? $affiliate : false,
					'affiliate_id' => isset( $affiliate_id ) ? $affiliate_id : false,
					'affiliate_token' => isset( $affiliate_token ) ? $affiliate_token : false,
					'referral_link' => ! empty( $referral_link ) ? $referral_link : false,
					'username' => $request_user_name,
					'original_url' => $original_url,
					'generated_url' => $generated_url

				)
			);

			$template_name = 'link-generator.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Returns output for affiliates dashboard
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard( $atts = array() ){
			global $wp;
			
			$atts = (array) $atts;

			// if user is not an enabled affiliate, show registration form
			if( ! YITH_WCAF_Affiliate_Handler()->is_user_enabled_affiliate() ){
				$show_name = get_option( 'yith_wcaf_referral_registration_show_name_field' );
				$show_surname = get_option( 'yith_wcaf_referral_registration_show_surname_field' );

				return self::registration_form( array_merge( $atts, array(
					'show_name_field' => $show_name,
					'show_surname_field' => $show_surname
				) ) );
			}

			// if set "commissions" query var, show commissions table
			if( isset( $wp->query_vars['commissions'] ) ){
				return self::affiliate_dashboard_commissions( $atts );
			}

			// if set "clicks" query var, show clicks table
			if( isset( $wp->query_vars['clicks'] ) ){
				return self::affiliate_dashboard_clicks( $atts );
			}

			// if set "payments" query var, show payments table
			if( isset( $wp->query_vars['payments'] ) ){
				return self::affiliate_dashboard_payments( $atts );
			}

			// if set "generate-link" query var, show generate click shortcode
			if( isset( $wp->query_vars['generate-link'] ) ){
				return self::link_generator( $atts );
			}

			// if set "settings" query var, show settings
			if ( isset( $wp->query_vars['settings'] ) ) {
				return self::affiliate_dashboard_settings( $atts );
			}

			// otherwise, show summary
			return self::affiliate_dashboard_summary( $atts );
		}

		/**
		 * Print commissions section of the dashboard
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard_commissions( $atts = array() ){
			$defaults = array(
				'pagination' => 'yes',
				'per_page' => isset( $_REQUEST['per_page'] ) ? intval( wc_clean( $_REQUEST['per_page'] ) ) : 10,
				'current_page' => max( 1, get_query_var( 'commissions' ) )
			);

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			if( ! is_user_logged_in() ){
				return '';
			}

			$user_id = get_current_user_id();
			$user = get_user_by( 'id', $user_id );
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );

			// sets filters from query string params
			$filters_set = false;
			$query_args = array();

			// filter by stauts
			if( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], YITH_WCAF_Commission_Handler()->get_available_status() ) ){
				$status = sanitize_text_field( $_REQUEST['status'] );
				$query_args['status'] = $status;
			}

			// filter by product
			if( isset( $_REQUEST['product_id'] ) && ! empty( $_REQUEST['product_id'] ) ){
				$product_id = intval( $_REQUEST['product_id'] );
				$query_args['product_id'] = $product_id;
				$filters_set = true;
			}

			// filter by date
			if( ( isset( $_REQUEST['to'] ) && ! empty( $_REQUEST['to'] ) ) || ( isset( $_REQUEST['from'] ) && ! empty( $_REQUEST['from'] ) ) ){
				$from = ! empty( $_REQUEST['from'] ) ? sanitize_text_field( $_REQUEST['from'] ) : '';
				$from_query = ! empty( $from ) ? date( 'Y-m-d 00:00:00', strtotime( $from ) ) : '';
				$to = ! empty( $_REQUEST['to'] ) ? sanitize_text_field( $_REQUEST['to'] ) : '';
				$to_query = ! empty( $to ) ? date( 'Y-m-d 23:59:59', strtotime( $to ) ) : '';
				$interval = array();

				if( $from_query ){
					$interval['start_date'] = $from_query;
				}

				if( $to_query ){
					$interval['end_date'] = $to_query;
				}

				$query_args['interval'] = $interval;
				$filters_set = true;
			}

			// count commissions, with filter, if any
			$commissions_count = YITH_WCAF_Commission_Handler()->count_commission( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args
			) );

			// sets pagination filters
			$page_links = '';
			if( $pagination == 'yes' && $commissions_count > 1 ){
				$pages = ceil( $commissions_count / $per_page );

				if( $current_page > $pages ){
					$current_page = $pages;
				}

				$offset = ( $current_page - 1 ) * $per_page;

				if( $pages > 1 ){
					$page_links = paginate_links( array(
						'base' => YITH_WCAF()->get_affiliate_dashboard_url( 'commissions', '%#%' ),
						'format' => '%#%',
						'current' => $current_page,
						'total' => $pages,
						'show_all' => true
					) );
				}

				$query_args[ 'limit' ] = $per_page;
				$query_args[ 'offset' ] = $offset;
			}

			$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created_at';
			$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

			// retrieve commissions
			$commissions = YITH_WCAF_Commission_Handler()->get_commissions( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args,
				array(
					'orderby' => $orderby,
					'order' => $order
				)
			) );

			$atts = array_merge(
				$atts,
				array(
					'user_id' => $user_id,
					'user' => $user,
					'affiliate_id' => $affiliate['ID'],
					'affiliate' => $affiliate,
					'commissions' => $commissions,
					'filter_set' => $filters_set,
					'page_links' => $page_links,
					'status' => isset( $status ) ? $status : false,
					'product_id' => isset( $product_id ) ? $product_id : false,
					'product_name' => isset( $product_id ) ? sprintf( '#%d – %s', $product_id, get_the_title( $product_id ) ) : '',
					'from' => isset( $from ) ? $from : false,
					'to' => isset( $to ) ? $to : false,
					'dashboard_commissions_link' => YITH_WCAF()->get_affiliate_dashboard_url( 'commissions', 1 ),
					'ordered' => $orderby,
					'to_order' => $order == 'DESC' ? 'ASC' : 'DESC'
				)
			);

			$template_name = 'dashboard-commissions.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Print clicks section of the dashboard
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard_clicks( $atts = array() ){
			$defaults = array(
				'pagination' => 'yes',
				'per_page' => isset( $_REQUEST['per_page'] ) ? intval( wc_clean( $_REQUEST['per_page'] ) ) : 10,
				'current_page' => max( 1, get_query_var( 'clicks' ) )
			);

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			if( ! is_user_logged_in() ){
				return '';
			}

			$user_id = get_current_user_id();
			$user = get_user_by( 'id', $user_id );
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );

			// sets filters from query string params
			$filters_set = false;
			$query_args = array();

			// filter by status
			if( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'converted', 'not-converted' ) ) ){
				$status = $_REQUEST['status'];
				$status_query = ( $_REQUEST['status'] == 'converted' ) ? 'yes' : 'no';
				$query_args['converted'] = $status_query;
			}

			// filter by date
			if( ( isset( $_REQUEST['to'] ) && ! empty( $_REQUEST['to'] ) ) || ( isset( $_REQUEST['from'] ) && ! empty( $_REQUEST['from'] ) ) ){
				$from = ! empty( $_REQUEST['from'] ) ? sanitize_text_field( $_REQUEST['from'] ) : '';
				$from_query = ! empty( $from ) ? date( 'Y-m-d 00:00:00', strtotime( $from ) ) : '';
				$to = ! empty( $_REQUEST['to'] ) ? sanitize_text_field( $_REQUEST['to'] ) : '';
				$to_query = ! empty( $to ) ? date( 'Y-m-d 23:59:59', strtotime( $to ) ) : '';
				$interval = array();

				if( $from_query ){
					$interval['start_date'] = $from_query;
				}

				if( $to_query ){
					$interval['end_date'] = $to_query;
				}

				$query_args['interval'] = $interval;
				$filters_set = true;
			}

			// count commissions, with filter, if any
			$clicks_count = YITH_WCAF_Click_Handler()->count_hits( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args
			) );

			// sets pagination filters
			$page_links = '';
			if( $pagination == 'yes' && $clicks_count > 1 ){

				$pages = ceil( $clicks_count / $per_page );

				if( $current_page > $pages ){
					$current_page = $pages;
				}

				$offset = ( $current_page - 1 ) * $per_page;

				if( $pages > 1 ){
					$page_links = paginate_links( array(
						'base' => YITH_WCAF()->get_affiliate_dashboard_url( 'clicks', '%#%' ),
						'format' => '%#%',
						'current' => $current_page,
						'total' => $pages,
						'show_all' => true
					) );
				}

				$query_args[ 'limit' ] = $per_page;
				$query_args[ 'offset' ] = $offset;
			}

			$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'click_date';
			$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

			// retrieve clicks
			$clicks = YITH_WCAF_Click_Handler()->get_hits( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args,
				array(
					'orderby' => $orderby,
					'order' => $order
				)
			) );

			$atts = array_merge(
				$atts,
				array(
					'user_id' => $user_id,
					'user' => $user,
					'affiliate_id' => $affiliate['ID'],
					'affiliate' => $affiliate,
					'clicks' => $clicks,
					'filter_set' => $filters_set,
					'page_links' => $page_links,
					'status' => isset( $status ) ? $status : false,
					'from' => isset( $from ) ? $from : false,
					'to' => isset( $to ) ? $to : false,
					'dashboard_clicks_link' => YITH_WCAF()->get_affiliate_dashboard_url( 'clicks', 1 ),
					'ordered' => $orderby,
					'to_order' => $order == 'DESC' ? 'ASC' : 'DESC'
				)
			);

			$template_name = 'dashboard-clicks.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Print payments section of the dashboard
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard_payments( $atts = array() ) {
			$defaults = array(
				'pagination' => 'yes',
				'per_page' => isset( $_REQUEST['per_page'] ) ? intval( wc_clean( $_REQUEST['per_page'] ) ) : 10,
				'current_page' => max( 1, get_query_var( 'payments' ) )
			);

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			if( ! is_user_logged_in() ){
				return '';
			}

			$user_id = get_current_user_id();
			$user = get_user_by( 'id', $user_id );
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );

			// sets filters from query string params
			$filters_set = false;
			$query_args = array();

			// filter by stauts
			if( isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'on-hold', 'pending', 'completed' ) ) ){
				$status = sanitize_text_field( $_REQUEST['status'] );
				$query_args['status'] = $status;
			}

			// filter by date
			if( ( isset( $_REQUEST['to'] ) && ! empty( $_REQUEST['to'] ) ) || ( isset( $_REQUEST['from'] ) && ! empty( $_REQUEST['from'] ) ) ){
				$from = ! empty( $_REQUEST['from'] ) ? sanitize_text_field( $_REQUEST['from'] ) : '';
				$from_query = ! empty( $from ) ? date( 'Y-m-d 00:00:00', strtotime( $from ) ) : '';
				$to = ! empty( $_REQUEST['to'] ) ? sanitize_text_field( $_REQUEST['to'] ) : '';
				$to_query = ! empty( $to ) ? date( 'Y-m-d 23:59:59', strtotime( $to ) ) : '';
				$interval = array();

				if( $from_query ){
					$interval['start_date'] = $from_query;
				}

				if( $to_query ){
					$interval['end_date'] = $to_query;
				}

				$query_args['interval'] = $interval;
				$filters_set = true;
			}

			// count commissions, with filter, if any
			$payments_count = YITH_WCAF_Payment_Handler()->count_payments( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args
			) );

			// sets pagination filters
			$page_links = '';
			if( $pagination == 'yes' && $payments_count > 1 ){
				$pages = ceil( $payments_count / $per_page );

				if( $current_page > $pages ){
					$current_page = $pages;
				}

				$offset = ( $current_page - 1 ) * $per_page;

				if( $pages > 1 ){
					$page_links = paginate_links( array(
						'base' => YITH_WCAF()->get_affiliate_dashboard_url( 'payments', '%#%' ),
						'format' => '%#%',
						'current' => $current_page,
						'total' => $pages,
						'show_all' => true
					) );
				}

				$query_args[ 'limit' ] = $per_page;
				$query_args[ 'offset' ] = $offset;
			}

			$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created_at';
			$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

			// retrieve commissions
			$payments = YITH_WCAF_Payment_Handler()->get_payments( array_merge(
				array(
					'user_id' => $user_id
				),
				$query_args,
				array(
					'orderby' => $orderby,
					'order' => $order
				)
			) );

			$template_name = 'dashboard-payments.php';

			$atts = array_merge(
				$atts,
				array(
					'user_id' => $user_id,
					'user' => $user,
					'affiliate_id' => $affiliate['ID'],
					'affiliate' => $affiliate,
					'payments' => $payments,
					'filter_set' => $filters_set,
					'page_links' => $page_links,
					'status' => isset( $status ) ? $status : false,
					'from' => isset( $from ) ? $from : false,
					'to' => isset( $to ) ? $to : false,
					'dashboard_payments_link' => YITH_WCAF()->get_affiliate_dashboard_url( 'payments', 1 ),
					'ordered' => $orderby,
					'to_order' => $order == 'DESC' ? 'ASC' : 'DESC'
				)
			);

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Print settings section of the dashboard
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard_settings( $atts = array() ){
			// only for consistency with other methods
			$defaults = array();

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			if( ! is_user_logged_in() ){
				return '';
			}

			$change = false;
			$user_id = get_current_user_id();
			$user = get_user_by( 'id', $user_id );
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );
			$payment_email = isset( $affiliate['payment_email'] ) ? $affiliate['payment_email'] : false;
			$notify_pending_commissions = isset( $user->_yith_wcaf_notify_pending_commission ) ? $user->_yith_wcaf_notify_pending_commission : apply_filters( 'yith_wcaf_default_notify_user_pending_commission', 'no', $user_id );
			$notify_paid_commissions = isset( $user->_yith_wcaf_notify_paid_commission ) ? $user->_yith_wcaf_notify_paid_commission : apply_filters( 'yith_wcaf_default_notify_user_paid_commission', 'no', $user_id );

			if( ! empty( $_REQUEST['payment_email'] ) ){
				$payment_email = sanitize_email( $_REQUEST['payment_email'] );

				YITH_WCAF_Affiliate_Handler()->update( $affiliate['ID'], array( 'payment_email' => $payment_email ) );
				$change = true;
			}

			if( isset( $_REQUEST['settings_submit'] ) ){
				$notify_pending_commissions = isset( $_REQUEST['notify_pending_commissions'] ) ? 'yes' : 'no';
				$notify_paid_commissions = isset( $_REQUEST['notify_paid_commissions'] ) ? 'yes' : 'no';

				update_user_meta( $user_id, '_yith_wcaf_notify_pending_commission', $notify_pending_commissions );
				update_user_meta( $user_id, '_yith_wcaf_notify_paid_commission', $notify_paid_commissions );
				$change = true;
			}

			if( $change ){
				wc_add_notice( __( 'Changes correctly saved!', 'yith-woocommerce-affiliates' ) );
			}

			$atts = array_merge(
				$atts,
				array(
					'user_id' => $user_id,
					'user' => $user,
					'affiliate_id' => $affiliate['ID'],
					'affiliate' => $affiliate,
					'payment_email' => $payment_email,
					'notify_pending_commissions' => $notify_pending_commissions,
					'notify_paid_commissions' => $notify_paid_commissions
				)
			);

			$template_name = 'dashboard-settings.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}

		/**
		 * Print dashboard summary
		 *
		 * @param $atts mixed Array of shortcode attributes
		 *
		 * @return string Shortcode content
		 * @since 1.0.0
		 */
		public static function affiliate_dashboard_summary( $atts = array() ){
			$defaults = array(
				'show_commissions_summary' => 'yes',
				'number_of_commissions' => 3,
				'show_clicks_summary' => 'yes',
				'number_of_clicks' => 3,
				'show_referral_stats' => 'yes',
				'show_dashboard_links' => 'yes'
			);

			$atts = shortcode_atts( $defaults, $atts );
			extract( $atts );

			if( ! is_user_logged_in() ){
				return '';
			}

			$user_id = get_current_user_id();
			$user = get_user_by( 'id', $user_id );
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_user_id( $user_id );

			$commissions = array();
			if( $show_commissions_summary == 'yes' ){
				$commissions = YITH_WCAF_Commission_Handler()->get_commissions( array(
					'user_id' => $user_id,
					'order_by' => 'created_at',
					'order' => 'DESC',
					'limit' => $number_of_commissions
				) );
			}

			$clicks = array();
			if( $show_clicks_summary == 'yes' ){
				$clicks = YITH_WCAF_Click_Handler()->get_hits( array(
					'user_id' => $user_id,
					'order_by' => 'click_date',
					'order' => 'DESC',
					'limit' => $number_of_clicks
				) );
			}

			$referral_stats = array();
			if( $show_referral_stats == 'yes' ){
				$paid_commissions_number = YITH_WCAF_Commission_Handler()->count_commission( array( 'user_id' => $user_id, 'status' => 'paid' ) );
				$commissions_number = YITH_WCAF_Commission_Handler()->count_commission( array( 'user_id' => $user_id ) );

				$referral_stats = array(
					'earnings' => $affiliate['earnings'],
					'paid' => $affiliate['paid'],
					'balance' => $affiliate['balance'],
					'refunds' => $affiliate['refunds'],
					'click' => $affiliate['click'],
					'conv_rate' => $affiliate['conv_rate'],
					'rate' => YITH_WCAF_Rate_Handler()->get_rate( $affiliate['ID'] ),
					'paid_count' => $paid_commissions_number,
					'unpaid_count' => $commissions_number - $paid_commissions_number
				);
			}

			$dashboard_links = apply_filters( 'yith_wcaf_dashboard_links', array(
				'dashboard' => YITH_WCAF()->get_affiliate_dashboard_url(),
				'commissions' => YITH_WCAF()->get_affiliate_dashboard_url( 'commissions', 1 ),
				'clicks' => YITH_WCAF()->get_affiliate_dashboard_url( 'clicks', 1 ),
				'payments' => YITH_WCAF()->get_affiliate_dashboard_url( 'payments', 1 ),
				'generate_link' => YITH_WCAF()->get_affiliate_dashboard_url( 'generate-link' ),
				'settings' => YITH_WCAF()->get_affiliate_dashboard_url( 'settings' )
			) );

			$greeting_message = sprintf(
				__( 'Hello <strong>%1$s</strong> (not %1$s? <a href="%2$s">Sign out</a>).', 'yith-woocommerce-affiliates' ) . ' ',
				$user->display_name,
				wc_get_endpoint_url( 'customer-logout', '', wc_get_page_permalink( 'myaccount' ) )
			);

			$greeting_message .= sprintf( __( 'From your affiliate dashboard you can view your recent commissions and visits, consult your affiliate stats and <a href="%1$s">manage settings</a> for your profile', 'yith-wcaf' ),
				$dashboard_links['settings']
			);

			$greeting_message = apply_filters( 'yith_wcaf_dashboard_grreting_message', $greeting_message );

			$atts = array_merge(
				$atts,
				array(
					'user_id' => $user_id,
					'user' => $user,
					'affiliate_id' => $affiliate['ID'],
					'affiliate' => $affiliate,
					'commissions' => $commissions,
					'clicks' => $clicks,
					'referral_stats' => $referral_stats,
					'dashboard_links' => $dashboard_links,
					'greeting_message' => $greeting_message,
					'show_left_column' => $show_referral_stats == 'yes',
					'show_right_column' =>  $show_dashboard_links == 'yes'
				)
			);

			$template_name = 'dashboard-summary.php';

			ob_start();

			yith_wcaf_get_template( $template_name, $atts, 'shortcodes' );

			return ob_get_clean();
		}
	}
}