<?php
/**
 * Admin class
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

if ( ! class_exists( 'YITH_WCAF_Admin' ) ) {
	/**
	 * WooCommerce Affiliates Admin
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAF_Admin {
		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCAF_Admin
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Docs url
		 *
		 * @var string Official documentation url
		 * @since 1.0.0
		 */
		public $doc_url = 'http://yithemes.com/docs-plugins/yith-woocommerce-affiliates/';

		/**
		 * Premium landing url
		 *
		 * @var string Premium landing url
		 * @since 1.0.0
		 */
		public $premium_landing_url = 'http://yithemes.com/themes/plugins/yith-woocommerce-affiliates/';

		/**
		 * List of available tab for affiliates panel
		 *
		 * @var array
		 * @access public
		 * @since 1.0.0
		 */
		public $available_tabs = array();

		/**
		 * Constructor method
		 *
		 * @return \YITH_WCAF_Admin
		 * @since 1.0.0
		 */
		public function __construct() {
			// sets available tab
			$this->available_tabs = apply_filters( 'yith_wcaf_available_admin_tabs', array(
				'commissions' => __( 'Commissions', 'yith-woocommerce-affiliates' ),
				'affiliates' => __( 'Affiliates', 'yith-woocommerce-affiliates' ),
				'payments' => __( 'Payments', 'yith-woocommerce-affiliates' ),
				'stats' => __( 'Stats', 'yith-woocommerce-affiliates' ),
				'settings' => __( 'Settings', 'yith-woocommerce-affiliates' ),
				'premium' => __( 'Premium', 'yith-woocommerce-affiliates' )
			) );

			// register plugin panel
			add_action( 'admin_init', array( $this, 'remove_http_ref' ) );
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'admin_menu', array( $this, 'add_bubble' ), 99 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_filter( 'woocommerce_screen_ids', array( $this, 'register_woocommerce_screen' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'yith_wcaf_premium_tab', array( $this, 'print_premium_tab' ) );

			// register plugin links & meta row
			add_filter( 'plugin_action_links_' . YITH_WCAF_INIT, array( $this, 'action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta' ), 10, 2 );

			// print stat plugin panel
			add_action( 'yith_wcaf_stat_panel', array( $this, 'print_stat_panel' ) );
		}

		/* === HELPER METHODS === */

		/**
		 * Return array of screen ids for affiliate plugin
		 *
		 * @return mixed Array of available screens
		 * @since 1.0.0
		 */
		public function get_screen_ids() {
			$base = sanitize_title( __( 'YITH Plugins', 'yith-plugin-fw' ) );

			$screen_ids = array(
				$base . '_page_yith_wcaf_panel'
			);

			return apply_filters( 'yith_wcaf_screen_ids', $screen_ids );
		}

		/* === PLUGIN PANEL METHODS === */

		/**
		 * Register panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function register_panel() {
			$args = array(
				'create_menu_page' => true,
				'parent_slug'   => '',
				'page_title'    => __( 'Affiliates', 'yith-woocommerce-affiliates' ),
				'menu_title'    => __( 'Affiliates', 'yith-woocommerce-affiliates' ),
				'capability'    => 'manage_options',
				'parent'        => '',
				'parent_page'   => 'yit_plugin_panel',
				'page'          => 'yith_wcaf_panel',
				'admin-tabs'    => $this->available_tabs,
				'options-path'  => YITH_WCAF_DIR . 'plugin-options'
			);

			/* === Fixed: not updated theme  === */
			if( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once( YITH_WCAF_DIR . 'plugin-fw/lib/yit-plugin-panel-wc.php' );
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Enqueue admin side scripts
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue() {
			// enqueue scripts
			$screen = get_current_screen();
			$path = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'unminified/' : '';
			$suffix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

			$page_prefix = sanitize_title( __( 'YIT Plugins', 'yith-plugin-fw' ) );

			if( in_array( $screen->id, $this->get_screen_ids() ) ) {
				wp_register_script( 'yith-wcaf', YITH_WCAF_URL . 'assets/js/admin/' . $path . 'yith-wcaf' . $suffix . '.js', array( 'jquery' ), false, true );

				do_action( 'yith_wcaf_before_admin_script_enqueue' );

				wp_enqueue_script( 'yith-wcaf' );
				wp_localize_script( 'yith-wcaf', 'yith_wcaf', array(
					'empty_row' => sprintf( '<tr class="no-items"><td class="colspanchange" colspan="5">%s</td></tr>', __( 'No items found.', 'yith-woocommerce-affiliates' ) ),
					'tabs_badges' => array(
						'commissions' => YITH_WCAF_Commission_Handler()->per_status_count( 'pending' ),
						'affiliates' => YITH_WCAF_Affiliate_Handler()->per_status_count( 'disabled' ),
						'payments' => defined( 'YITH_WCAF_PREMIUM' ) ? YITH_WCAF_Payment_Handler()->per_status_count( 'on-hold' ) : 0
					)
				) );
			}

			// enqueue styles
			if( in_array( $screen->id, $this->get_screen_ids() ) || $screen->id == 'shop_order' ) {
				wp_register_style( 'yith-wcaf', YITH_WCAF_URL . 'assets/css/admin/yith-wcaf.css' );

				do_action( 'yith_wcaf_before_admin_style_enqueue' );

				wp_enqueue_style( 'yith-wcaf' );
			}
		}

		/**
		 * Register affiliate panel as woocommerce screen
		 *
		 * @param $screens mixed Array of current woocommerce screens
		 * @return mixed Filtered array of woocommerce screens
		 * @since 1.0.0
		 */
		public function register_woocommerce_screen( $screens ) {
			$screens = array_merge(
				$screens,
				$this->get_screen_ids()
			);

			return $screens;
		}

		/**
		 * Print bubble to admin menu
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_bubble() {
			global $submenu;

			$count = YITH_WCAF_Commission_Handler()->per_status_count( 'pending' ) + YITH_WCAF_Affiliate_Handler()->per_status_count( 'disabled' ) + ( defined( 'YITH_WCAF_Premium' ) ? YITH_WCAF_Payment_Handler()->per_status_count( 'on-hold' ) : 0 );

			if( $count == 0 ){
				return;
			}

			$bubble = " <span class='update-plugins count-{$count}'><span class='plugin-count'>{$count}</span></span>";

			if( ! empty( $submenu ) && isset( $submenu['yit_plugin_panel'] ) ){
				foreach( $submenu['yit_plugin_panel'] as & $sub ){
					if( $sub[2] == 'yith_wcaf_panel' ){
						$sub[0] .= $bubble;
					}
				}
			}
		}

		/**
		 * Print notices before commission panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function admin_notices() {
			$screen = get_current_screen();
			$message = '';

			if( in_array( $screen->id, $this->get_screen_ids() ) ){
				$messages = array(
					'success' => array(),
					'notice' => array()
				);

				// affiliates notice
				if( isset( $_GET['commissions_paid'] ) ){
					if( $_GET['commissions_paid'] == 1 ){
						$messages['success'][] = __( 'Payments correctly registered', 'yith-woocommerce-affiliates' );
					}
					else{
						$messages['notice'][] = __( 'There was an error while processing payments', 'yith-woocommerce-affiliates' );
					}
				}

				// commissions notice
				if( isset( $_GET['commission_status_change'] ) ){
					if( $_GET['commission_status_change'] == 1 ){
						$messages['success'][] = __( 'Status changed correctly', 'yith-woocommerce-affiliates' );
					}
					else{
						$messages['notice'][] = __( 'An error occurred with status changing', 'yith-woocommerce-affiliates' );
					}
				}

				if( isset( $_GET['commission_deleted'] ) ){
					if( $_GET['commission_deleted'] == 1 ){
						$messages['success'][] = __( 'Item successfully deleted', 'yith-woocommerce-affiliates' );
					}
					else{
						$messages['notice'][] = __( 'An error occurred while deleting item', 'yith-woocommerce-affiliates' );
					}
				}

				if( isset( $_GET['commission_payment_failed'] ) ){
					$errors = explode( ',', $_GET['commission_payment_failed'] );
					$messages['notice'][] = sprintf( __( 'An error occurred during payment processing: %s', 'yith-woocommerce-affiliates' ), implode( ', ', $errors ) );
				}

				if( ! empty( $_GET['commission_paid'] ) ){
					$paid_commissions = explode( ',', $_GET['commission_paid'] );
					$messages['success'][] = sprintf( __( 'Commissions %s correctly paid', 'yith-woocommerce-affiliates' ), implode( ', ', $paid_commissions ) );
				}

				if( ! empty( $_GET['commission_unpaid'] ) ){
					$unpaid_commissions = explode( ',', $_GET['commission_unpaid'] );
					$messages['notice'][] = sprintf( __( 'Commissions %s could not be paid', 'yith-woocommerce-affiliates' ), implode( ', ', $unpaid_commissions ) );
				}

				// payment notice
				if( ! empty( $_GET['payment_failed'] ) ){
					foreach( $_GET as $key => $value ){
						$matches = array();
						if( preg_match( '^payment_error_([0-9_]*)$^', $key, $matches ) ){
							$payments = explode( '_', $matches[1] );
							$errors = explode( ',', $value );
							$messages['notice'][] = sprintf( _n( 'There was an error while processing payment %s: %s', 'There was an error while processing payments %s: %s', count( $payments ), 'yith-wcaf' ), implode( ', ', $payments ), implode( ', ', $errors ) );
						}
					}
				}

				if( ! empty( $_GET['payment_success'] ) ){
					foreach( $_GET as $key => $value ){
						$matches = array();
						if( preg_match( '^payment_success_([0-9_]*)$^', $key, $matches ) ){
							$payments = explode( '_', $matches[1] );
							$messages['success'][] = sprintf( _n( 'Payment %s completed successfully', 'Payments %s completed successfully', count( $payments ), 'yith-wcaf' ), implode( ', ', $payments ) );
						}
					}
				}

				if( ! empty( $messages['success'] ) ){
					$message .= '<div class="updated notice is-dismissible">';
					$message .= '<p>';
					$message .= implode( '<br/>', $messages['success'] );
					$message .= '</p>';
					$message .= '</div>';
				}

				if( ! empty( $messages['notice'] ) ){
					$message .= '<div class="error notice is-dismissible">';
					$message .= '<p>';
					$message .= implode( '<br/>', $messages['notice'] );
					$message .= '</p>';
					$message .= '</div>';
				}

				echo $message;
			}
		}

		/**
		 * Remove args for http refer and nonce from query string
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function remove_http_ref() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'yith_wcaf_panel' && ! empty( $_GET['_wp_http_referer'] ) ) {
				wp_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
				exit;
			}
		}

		/**
		 * Prints premium tab
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_premium_tab() {
			include( YITH_WCAF_DIR . 'templates/admin/premium-tab.php' );
		}

		/* === PLUGIN LINK METHODS === */

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri(){
			return defined( 'YITH_REFER_ID' ) ? $this->premium_landing_url . '?refer_id=' . YITH_REFER_ID : $this->premium_landing_url .'?refer_id=1030585';
		}

		/**
		 * Add plugin action links
		 *
		 * @param mixed $links Plugins links array
		 *
		 * @return array Filtered link array
		 * @since 1.0.0
		 */
		public function action_links( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=yith_wcaf_panel&tab=settings' ) . '">' . __( 'Settings', 'yith-woocommerce-affiliates' ) . '</a>'
			);

			if( ! defined( 'YITH_WCAF_PREMIUM_INIT' ) ){
				$plugin_links[] = '<a target="_blank" href="' . $this->get_premium_landing_uri() . '">' . __( 'Premium Version', 'yith-woocommerce-affiliates' ) . '</a>';
			}

			return array_merge( $links, $plugin_links );
		}

		/**
		 * Adds plugin row meta
		 *
		 * @param $plugin_meta array Array of unfiltered plugin meta
		 * @param $plugin_file string Plugin base file path
		 *
		 * @return array Filtered array of plugin meta
		 * @since 1.0.0
		 */
		public function add_plugin_meta( $plugin_meta, $plugin_file ){
			if ( $plugin_file == plugin_basename( YITH_WCAF_DIR . 'init.php' ) ) {
				// documentation link
				$plugin_meta['documentation'] = '<a target="_blank" href="' . $this->doc_url . '">' . __( 'Plugin Documentation', 'yith-woocommerce-affiliates' ) . '</a>';
			}

			return $plugin_meta;
		}

		/* === STATS METHODS === */

		/**
		 * Print plugin stat panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_stat_panel() {
			$page_title = __( 'Global stats', 'yith-woocommerce-affiliates' );
			$title_suffix = '';

			// init stat filters
			$from = isset( $_REQUEST['_from'] ) ? $_REQUEST['_from'] : '';
			$to = isset( $_REQUEST['_to'] ) ? $_REQUEST['_to'] : '';
			$need_reset = false;

			$filters = array();

			if( ! empty( $from ) ){
				$filters['interval']['start_date'] = date( 'Y-m-d 00:00:00', strtotime( $from ) );
			}

			if( ! empty( $to ) ){
				$filters['interval']['end_date'] = date( 'Y-m-d 23:59:59', strtotime( $to ) );
			}

			if( ! empty( $from ) || ! empty( $to ) ){
				$title_suffix = sprintf( ' (%s - %s)', ! empty( $from ) ? date_i18n( wc_date_format(), strtotime( $from ) ) : __( 'Ever', 'yith-woocommerce-affiliates' ), ! empty( $to ) ? date_i18n( wc_date_format(), strtotime( $to ) ) : __( 'Today', 'yith-woocommerce-affiliates' ) );
			}

			if( ! empty( $from ) || ! empty( $to ) ){
				$need_reset = true;
				$reset_link = esc_url( add_query_arg( array( 'page' => 'yith_wcaf_panel', 'tab' => 'stats' ), admin_url( 'admin.php' ) ) );
			}

			// define variables to be used in the template
			$total_amount = YITH_WCAF_Commission_Handler()->get_commission_stats( 'total_amount', array_merge( $filters, array( 'status' => array( 'pending', 'pending-payment', 'paid' ) ) ) );
			$total_paid = YITH_WCAF_Commission_Handler()->get_commission_stats( 'total_amount', array_merge( $filters, array( 'status' => array( 'pending-payment', 'paid' ) ) ) );

			$total_clicks = YITH_WCAF_Click_Handler()->get_hit_stats( 'total_clicks', $filters );
			$total_conversions = YITH_WCAF_Click_Handler()->get_hit_stats( 'total_conversions', $filters );
			$avg_conv_rate = ! empty( $total_clicks ) ? $total_conversions / $total_clicks * 100 : 0;
			$avg_conv_rate = ! empty( $avg_conv_rate ) ? number_format( $avg_conv_rate, 2 ) . '%' : __( 'N/A', 'yith-woocommerce-affiliates' );

			$page_title .= $title_suffix;

			// includes panel template
			include( YITH_WCAF_DIR . 'templates/admin/stat-panel.php' );
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCAF_Admin
		 * @since 1.0.2
		 */
		public static function get_instance(){
			if( class_exists( 'YITH_WCAF_Admin_Premium' ) ) {
				if ( is_null( YITH_WCAF_Admin_Premium::$instance ) ) {
					YITH_WCAF_Admin_Premium::$instance = new YITH_WCAF_Admin_Premium;
				}

				return YITH_WCAF_Admin_Premium::$instance;
			}
			else{
				if ( is_null( YITH_WCAF_Admin::$instance ) ) {
					YITH_WCAF_Admin::$instance = new YITH_WCAF_Admin;
				}

				return YITH_WCAF_Admin::$instance;
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WCAF_Admin class
 *
 * @return \YITH_WCAF_Admin
 * @since 1.0.0
 */
 function YITH_WCAF_Admin(){
	 $instance = apply_filters( 'yith_wcaf_admin_single_instance', null );

	 if( ! $instance ){
		 $instance = YITH_WCAF_Admin::get_instance();
	 }

	 return $instance;
}