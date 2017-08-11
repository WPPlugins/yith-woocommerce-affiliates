<?php
/**
 * Affiliate Handler class
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

if ( ! class_exists( 'YITH_WCAF_Affiliate_Handler' ) ) {
	/**
	 * WooCommerce Affiliate Handler
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAF_Affiliate_Handler {

		/**
		 * Single instance of the class for each token
		 *
		 * @var \YITH_WCAF_Affiliate_Handler
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Constructor method
		 *
		 * @return \YITH_WCAF_Affiliate_Handler
		 * @since 1.0.0
		 */
		public function __construct() {
			// register affiliate panel
			add_action( 'yith_wcaf_affiliate_panel', array( $this, 'print_affiliate_panel' ) );
			add_action( 'admin_init', array( $this, 'add_affiliate' ) );
			add_action( 'admin_action_yith_wcaf_change_status', array( $this, 'handle_switch_status_panel_actions' ) );
			add_action( 'current_screen', array( $this, 'add_screen_option' ) );
			add_filter( 'manage_yith-plugins_page_yith_wcaf_panel_columns', array( $this, 'add_screen_columns' ) );
			add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

			// handle affiliate registration
			add_filter( 'woocommerce_process_registration_errors', array( $this, 'check_affiliate' ) );
			add_action( 'woocommerce_created_customer', array( $this, 'register_affiliate' ), 5, 1 );
			add_action( 'woocommerce_register_form_start', array( $this, 'print_affiliate_fields' ), 10 );
			add_action( 'woocommerce_register_form', array( $this, 'print_affiliate_fields' ), 10 );
			add_action( 'wp_loaded', array( $this, 'become_an_affiliate' ) );

			// profile screen update methods
			add_action( 'show_user_profile', array( $this, 'render_affiliate_extra_fields' ), 20 );
			add_action( 'edit_user_profile', array( $this, 'render_affiliate_extra_fields' ), 20 );
			add_action( 'personal_options_update', array( $this, 'save_affiliate_extra_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_affiliate_extra_fields' ) );

			// handle notifications
			add_action( 'yith_wcaf_new_affiliate', array( WC(), 'mailer' ), 5 );

			// handle ajax actions
			add_action( 'wp_ajax_json_search_affiliates', array( $this, 'get_affiliates_via_ajax' ) );
		}

		/* === AFFILIATE HANDLING METHODS === */

		/**
		 * Add an item to affiliate table
		 *
		 * @param $affiliate_args mixed<br/>
		 * [<br/>
		 *    'token' => '',        // affiliate token<br/>
		 *    'user_id' => 0,       // affiliate related user id<br/>
		 *    'enabled' => 1,       // affiliate enabled (0/1)<br/>
		 *    'rate' => 'NULL',     // affiliate rate (float; leave empty if there is no specific rate for this affiliate)<br/>
		 *    'earnings' => 0,      // affiliate earnings (float)<br/>
		 *    'refunds' => 0,       // affiliate refunds (float)<br/>
		 *    'paid' => 0,          // affiliate paid (float)<br/>
		 *    'click' => 0,         // affiliate clicks (int)<br/>
		 *    'conversion' => 0,    // affiliate conversions (int)<br/>
		 *    'payment_email' => '' // affiliate payment email (string)<br/>
		 * ]
		 * @return int Inserted row ID
		 * @since 1.0.0
		 */
		public function add( $affiliate_args ) {
			global $wpdb;

			$defaults = array(
				'token' => '',
				'user_id' => 0,
				'enabled' => 1,
				'rate' => 'NULL',
				'earnings' => 0,
				'refunds' => 0,
				'paid' => 0,
				'click' => 0,
				'conversion' => 0,
				'payment_email' => ''
			);

			$args = wp_parse_args( $affiliate_args, $defaults );

			if( $args['rate'] == 'NULL' ){
				unset( $args['rate'] );
			}

			$res = $wpdb->insert( $wpdb->yith_affiliates, $args );

			if( ! $res ){
				return false;
			}

			return $wpdb->insert_id;
		}

		/**
		 * Update an item of affiliate table
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @param $args mixed<br/>
		 * [<br/>
		 *    'token' => '',        // affiliate token<br/>
		 *    'user_id' => 0,       // affiliate related user id<br/>
		 *    'enabled' => 1,       // affiliate enabled (0/1)<br/>
		 *    'rate' => 'NULL',     // affiliate rate (float; leave empty if there is no specific rate for this affiliate)<br/>
		 *    'earnings' => 0,      // affiliate earnings (float)<br/>
		 *    'refunds' => 0,       // affiliate refunds (float)<br/>
		 *    'paid' => 0,          // affiliate paid (float)<br/>
		 *    'click' => 0,         // affiliate clicks (int)<br/>
		 *    'conversion' => 0,    // affiliate conversions (int)<br/>
		 *    'payment_email' => '' // affiliate payment email (string)<br/>
		 * ]
		 * @return int|bool False on failure; number of updated rows on success (usually 1)
		 * @since 1.0.0
		 */
		public function update( $affiliate_id, $args ) {
			global $wpdb;

			return $wpdb->update( $wpdb->yith_affiliates, $args, array( 'ID' => $affiliate_id ) );
		}

		/**
		 * Delete an item from affiliates table
		 *
		 * @param $affiliate_id int Affiliate id
		 * @return bool Status of the operation
		 * @since 1.0.0
		 */
		public function delete( $affiliate_id ) {
			global $wpdb;

			return $wpdb->delete( $wpdb->yith_affiliates, array( 'ID' => $affiliate_id ) );
		}

		/**
		 * Register a user as an enabled affiliate (admin panel action handling)
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_affiliate() {
			if( ! isset( $_REQUEST['yith_new_affiliate'] ) ) {
				return;
			}

			$user_id = isset( $_REQUEST['yith_new_affiliate'] ) ? intval( $_REQUEST['yith_new_affiliate'] ) : 0;

			if( empty( $user_id ) ){
				return;
			}

			$token = $this->get_default_user_token( $user_id );

			$this->add( array( 'user_id' => $user_id, 'token' => $token ) );
		}

		/* === FORM HANDLER METHODS === */

		/**
		 * Flag a registered user as an affiliates
		 *
		 * @return void
		 * @since 1.0.9
		 */
		public function become_an_affiliate() {
			if( isset( $_REQUEST['become_an_affiliate'] ) && $_REQUEST['become_an_affiliate'] == 1 ){
				if( is_user_logged_in() ){
					$customer_id = get_current_user_id();
					$affiliates = $this->get_affiliates( array( 'user_id' => $customer_id ) );
					$affiliate = isset( $affiliates[0] ) ? $affiliates[0] : false;

					if( ! $affiliate ){
						$id = $this->add( array( 'user_id' => $customer_id, 'enabled' => false, 'token' => $this->get_default_user_token( $customer_id ) ) );

						if( $id ){
							wc_add_notice( __( 'Your request has been processed correctly', 'yith-woocommerce-affiliates' ) );

							// trigger new affiliate action
							do_action( 'yith_wcaf_new_affiliate', $id );
						}
						else{
							wc_add_notice( __( 'An error occurred while trying to create the affiliate; try later.', 'yith-woocommerce-affiliates' ), 'error' );
						}
					}
					else{
						wc_add_notice( __( 'You have already affiliated with us!', 'yith-woocommerce-affiliates' ), 'error' );
					}
				}

				wp_redirect( esc_url( apply_filters( 'yith_wcaf_become_an_affiliate_redirection', remove_query_arg( 'become_an_affiliate' ) ) ) );
				die();
			}
		}

		/**
		 * Register a user as an affiliate (register form action handling)
		 *
		 * @param $customer_id int Customer ID
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function register_affiliate( $customer_id ) {
			// retrieve options
			$enabled_form = get_option( 'yith_wcaf_referral_registration_form_options' );

			// retrieve post data
			$first_name = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : false;
			$last_name = ! empty( $_POST['last_name'] ) ? trim( $_POST['last_name'] ) : false;
			$payment_email = ! empty( $_POST['payment_email'] ) ? trim( $_POST['payment_email'] ) : false;

			if(
				( ! empty( $_POST['register_affiliate'] ) && isset( $_POST['register_affiliate'] ) && wp_verify_nonce( $_POST['register_affiliate'], 'yith-wcaf-register-affiliate' ) ) ||
				( ! empty( $_POST['register'] ) && isset( $_POST['woocommerce-register-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-register-nonce'], 'woocommerce-register' ) && $enabled_form == 'any' )
			){
				$id = $this->add( array( 'user_id' => $customer_id, 'enabled' => false, 'payment_email' => $payment_email, 'token' => $this->get_default_user_token( $customer_id ) ) );

				if( $first_name || $last_name ){
					wp_update_user( array_merge(
						array( 'ID' => $customer_id ),
						( $first_name ) ? array( 'first_name' => $first_name ) : array(),
						( $last_name ) ? array( 'last_name' => $last_name ) : array()
					) );
				}

				// trigger new affiliate action
				do_action( 'yith_wcaf_new_affiliate', $id );
			}
		}

		/**
		 * Check affiliate additional data
		 *
		 * @param $validation_error \WP_Error Registration errors object
		 *
		 * @return \WP_Error
		 * @since 1.0.0
		 */
		public function check_affiliate( $validation_error  ) {
			$enabled_form = get_option( 'yith_wcaf_referral_registration_form_options' );

			if(
				( ! empty( $_POST['register_affiliate'] ) && isset( $_POST['register_affiliate'] ) && wp_verify_nonce( $_POST['register_affiliate'], 'yith-wcaf-register-affiliate' ) ) ||
				( ! empty( $_POST['register'] ) && isset( $_POST['woocommerce-register-nonce'] ) && wp_verify_nonce( $_POST['woocommerce-register-nonce'], 'woocommerce-register' ) && $enabled_form == 'any' )
			){
				if( ( empty( $_POST['payment_email'] ) || ! is_email( $_POST['payment_email'] ) ) && apply_filters( 'yith_wcaf_payment_email_required', true ) ){
					$validation_error->add( 'no_payment_email', __( 'Please, submit a valid email address where we can send PayPal payments', 'yith-wcaf' ) );
				}

				if( ! empty( $_POST['first_name'] ) && ! ( $name = sanitize_text_field( $_POST['first_name'] ) ) ){
					$validation_error->add( 'invalid_name', __( 'Please, enter a valid first name', 'yith-wcaf' ) );
				}

				if( ! empty( $_POST['last_name'] ) && ! ( $name = sanitize_text_field( $_POST['last_name'] ) ) ){
					$validation_error->add( 'invalid_surname', __( 'Please, enter a valid last name', 'yith-wcaf' ) );
				}
			}

			return $validation_error;
		}

		/**
		 * Print affiliates additional fields on my-account screen
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_affiliate_fields(){
			global $wp_current_filter;
			$enabled_form = get_option( 'yith_wcaf_referral_registration_form_options' );
			$show_name_field = get_option( 'yith_wcaf_referral_registration_show_name_field' );
			$show_surname_field = get_option( 'yith_wcaf_referral_registration_show_surname_field' );

			if( 'any' == $enabled_form && in_array( 'woocommerce_register_form_start', $wp_current_filter) && 'yes' == $show_name_field ):
				?>
				<p class="form-row form-row-wide">
					<label for="first_name"><?php _e( 'First name', 'yith-woocommerce-affiliates' ); ?></label>
					<input type="text" class="input-text" name="first_name" id="first_name" value="<?php if ( ! empty( $_POST['first_name'] ) ) echo esc_attr( $_POST['first_name'] ); ?>" />
				</p>
			<?php
			endif;

			if( 'any' == $enabled_form && in_array( 'woocommerce_register_form_start', $wp_current_filter ) && 'yes' == $show_surname_field ):
				?>
				<p class="form-row form-row-wide">
					<label for="last_name"><?php _e( 'Last name', 'yith-woocommerce-affiliates' ); ?></label>
					<input type="text" class="input-text" name="last_name" id="last_name" value="<?php if ( ! empty( $_POST['last_name'] ) ) echo esc_attr( $_POST['last_name'] ); ?>" />
				</p>
			<?php
			endif;

			if( 'any' == $enabled_form && in_array( 'woocommerce_register_form', $wp_current_filter ) && apply_filters( 'yith_wcaf_payment_email_required', true ) ):
				?>
				<p class="form-row form-row-wide">
					<label for="payment_email"><?php _e( 'Payment email address', 'yith-woocommerce-affiliates' ); ?> <span class="required">*</span></label>
					<input type="email" class="input-text" name="payment_email" id="payment_email" value="<?php if ( ! empty( $_POST['payment_email'] ) ) echo esc_attr( $_POST['payment_email'] ); ?>" />
				</p>
			<?php
			endif;
		}

		/* === HELPER METHODS === */

		/**
		 * Return current ref variable name
		 *
		 * @return string Ref variable name
		 * @since 1.0.0
		 */
		public function get_ref_name() {
			return get_option( 'yith_wcaf_referral_var_name', 'ref' );
		}

		/**
		 * Return number of affiliates matching filtering criteria
		 *
		 * @param $args mixed Filtering criteria<br/>
		 * [<br/>
		 *     'user_id' => false,              // affiliate related user id (int)<br/>
		 *     'user_login' => false,           // affiliate related user login, or part of it (string)<br/>
		 *     'user_email' => false,           // affiliate related user EMAIL, or part of it (string)<br/>
		 *     'payment_email' => false,        // affiliate payment email, or part of it (string)<br/>
		 *     'rate' => false,                 // affiliate rate range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'earnings' => false,             // affiliate earnings range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'paid' => false,                 // affiliate paid range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'balance' => false,              // affiliate balance range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'clicks' => false,               // affiliate clicks range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'conversions' => false,          // affiliate conversions range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'conv_rate' => false,            // affiliate conversion rate range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'status' => false,               // affiliate status (enabled/disabled)<br/>
		 *     's' => false                     // search string (string)<br/>
		 * ]
		 * @return int Number of counted affiliates
		 * @use YITH_WCAF_Affiliate_Handler::get_affiliates()
		 * @since 1.0.0
		 */
		public function count_affiliates( $args = array() ){
			global $wpdb;

			$defaults = array(
				'user_id' => false,
				'user_login' => false,
				'user_email' => false,
				'payment_email' => false,
				'rate' => false,
				'earnings' => false,
				'paid' => false,
				'balance' => false,
				'clicks' => false,
				'conversions' => false,
				'conv_rate' => false,
				'enabled' => false,
				's' => false
			);

			$args = wp_parse_args( $args, $defaults );
			return count( $this->get_affiliates( $args ) );
		}

		/**
		 * Return affiliates matching filtering criteria
		 *
		 * @param $args mixed Filtering criteria<br/>
		 * [<br/>
		 *     'user_id' => false,              // affiliate related user id (int)<br/>
		 *     'user_login' => false,           // affiliate related user login, or part of it (string)<br/>
		 *     'user_email' => false,           // affiliate related user EMAIL, or part of it (string)<br/>
		 *     'payment_email' => false,        // affiliate payment email, or part of it (string)<br/>
		 *     'rate' => false,                 // affiliate rate range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'earnings' => false,             // affiliate earnings range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'paid' => false,                 // affiliate paid range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'balance' => false,              // affiliate balance range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'clicks' => false,               // affiliate clicks range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'conversions' => false,          // affiliate conversions range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'conv_rate' => false,            // affiliate conversion rate range (array, with at lest one of this index: [min(float)|max(float)])<br/>
		 *     'status' => false,               // affiliate status (enabled/disabled)<br/>
		 *     's' => false                     // search string (string)<br/>
		 *     'order' => 'DESC',               // sorting direction (ASC/DESC)<br/>
		 *     'orderby' => 'ID',               // sorting column (any table valid column)<br/>
		 *     'limit' => 0,                    // limit (int)<br/>
		 *     'offset' => 0                    // offset (int)<br/>
		 * ]
		 * @return mixed Matching affiliates
		 * @since 1.0.0
		 */
		public function get_affiliates( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'user_id' => false,
				'user_login' => false,
				'user_email' => false,
				'payment_email' => false,
				'rate' => false,
				'earnings' => false,
				'paid' => false,
				'balance' => false,
				'clicks' => false,
				'conversions' => false,
				'conv_rate' => false,
				'enabled' => false,
				's' => false,
				'order' => 'DESC',
				'orderby' => 'ID',
				'limit' => 0,
				'offset' => 0
			);

			$args = wp_parse_args( $args, $defaults );

			$query = "SELECT
                       ya.*,
                       ( ya.earnings + ya.refunds ) AS totals,
                       ( ya.earnings - ya.paid ) AS balance,
                       ( ya.conversion / ya.click * 100 ) AS conv_rate,
                       u.user_login,
                       u.user_email,
                       u.display_name,
                       u.user_nicename
                      FROM {$wpdb->yith_affiliates} AS ya
                      LEFT JOIN {$wpdb->users} AS u ON u.ID = ya.user_id
                      WHERE 1 = 1";
			$query_arg = array();

			if( ! empty( $args['user_id'] ) ){
				$query .= ' AND ya.user_id = %d';
				$query_arg[] = $args['user_id'];
			}

			if( ! empty( $args['user_login'] ) ){
				$query .= ' AND u.user_login LIKE %s';
				$query_arg[] = '%' . $args['user_login'] . '%';
			}

			if( ! empty( $args['user_email'] ) ){
				$query .= ' AND u.user_email LIKE %s';
				$query_arg[] = '%' . $args['user_email'] . '%';
			}

			if( ! empty( $args['payment_email'] ) ){
				$query .= ' AND ya.payment_email LIKE %s';
				$query_arg[] = '%' . $args['payment_email'] . '%';
			}

			if( ! empty( $args['rate'] ) ) {
				if ( is_array( $args[ 'rate' ] ) && ( isset( $args[ 'rate' ][ 'min' ] ) || isset( $args[ 'rate' ][ 'max' ] ) ) ) {
					if ( ! empty( $args[ 'rate' ][ 'min' ] ) ) {
						$query .= ' AND ya.rate >= %f';
						$query_arg[ ] = $args[ 'rate' ][ 'min' ];
					}

					if ( ! empty( $args[ 'rate' ][ 'max' ] ) ) {
						$query .= ' AND ya.rate <= %f';
						$query_arg[ ] = $args[ 'rate' ][ 'max' ];
					}
				}
				elseif( $args['rate'] == 'NULL' ){
					$query .= ' AND ya.rate IS NULL';
				}
				elseif( $args['rate'] == 'NOT NULL' ){
					$query .= ' AND ya.rate IS NOT NULL';
				}
			}

			if( ! empty( $args['earnings'] ) && is_array( $args['earnings'] ) && ( isset( $args['earnings']['min'] ) || isset( $args['earnings']['max'] ) ) ){
				if( ! empty( $args['earnings']['min'] ) ){
					$query .= ' AND ( ya.earnings + ya.refunds ) >= %f';
					$query_arg[] = $args['earnings']['min'];
				}

				if( ! empty( $args['earnings']['max'] ) ){
					$query .= ' AND ( ya.earnings + ya.refunds ) <= %f';
					$query_arg[] = $args['earnings']['max'];
				}
			}

			if( ! empty( $args['paid'] ) && is_array( $args['paid'] ) && ( isset( $args['paid']['min'] ) || isset( $args['paid']['max'] ) ) ){
				if( ! empty( $args['paid']['min'] ) ){
					$query .= ' AND ya.paid >= %f';
					$query_arg[] = $args['paid']['min'];
				}

				if( ! empty( $args['paid']['max'] ) ){
					$query .= ' AND ya.paid <= %f';
					$query_arg[] = $args['paid']['max'];
				}
			}

			if( ! empty( $args['balance'] ) && is_array( $args['balance'] ) && ( isset( $args['balance']['min'] ) || isset( $args['balance']['max'] ) ) ){
				if( ! empty( $args['balance']['min'] ) ){
					$query .= ' AND ( ya.earnings - ya.paid ) >= %f';
					$query_arg[] = $args['balance']['min'];
				}

				if( ! empty( $args['balance']['max'] ) ){
					$query .= ' AND ( ya.earnings - ya.paid ) <= %f';
					$query_arg[] = $args['balance']['max'];
				}
			}

			if( ! empty( $args['click'] ) && is_array( $args['click'] ) && ( isset( $args['click']['min'] ) || isset( $args['click']['max'] ) ) ){
				if( ! empty( $args['click']['min'] ) ){
					$query .= ' AND ya.click >= %f';
					$query_arg[] = $args['click']['min'];
				}

				if( ! empty( $args['click']['max'] ) ){
					$query .= ' AND ya.click <= %f';
					$query_arg[] = $args['click']['max'];
				}
			}

			if( ! empty( $args['conversion'] ) && is_array( $args['conversion'] ) && ( isset( $args['conversion']['min'] ) || isset( $args['conversion']['max'] ) ) ){
				if( ! empty( $args['conversion']['min'] ) ){
					$query .= ' AND ya.conversion >= %f';
					$query_arg[] = $args['conversion']['min'];
				}

				if( ! empty( $args['conversion']['max'] ) ){
					$query .= ' AND ya.conversion <= %f';
					$query_arg[] = $args['conversion']['max'];
				}
			}

			if( ! empty( $args['conv_rate'] ) && is_array( $args['conv_rate'] ) && ( isset( $args['conv_rate']['min'] ) || isset( $args['conv_rate']['max'] ) ) ){
				if( ! empty( $args['conv_rate']['min'] ) ){
					$query .= ' AND ( ya.conversion / ya.click * 100 ) >= %f';
					$query_arg[] = $args['conv_rate']['min'];
				}

				if( ! empty( $args['conv_rate']['max'] ) ){
					$query .= ' AND ( ya.conversion / ya.click * 100 ) <= %f';
					$query_arg[] = $args['conv_rate']['max'];
				}
			}

			if( ! empty( $args['enabled'] ) ){
				$query .= ' AND ya.enabled = %d';
				$query_arg[] = ( $args['enabled'] == 'enabled' ) ? 1 : 0;
			}

			if( ! empty( $args['s'] ) ){
				$query .= ' AND ( u.user_login LIKE %s OR u.user_email LIKE %s OR ya.token LIKE %s OR ya.payment_email LIKE %s )';
				$search_string = '%' . $args['s'] . '%';

				$query_arg = array_merge( $query_arg, array(
					$search_string,
					$search_string,
					$search_string,
					$search_string
				) );
			}

			if( ! empty( $args['orderby'] ) ){
				$query .= sprintf( ' ORDER BY %s %s', $args['orderby'], $args['order'] );
			}

			if( ! empty( $args['limit'] ) ){
				$query .= sprintf( ' LIMIT %d, %d', ! empty( $args['offset'] ) ? $args['offset'] : 0, $args['limit'] );
			}

			if( ! empty( $query_arg ) ){
				$query = $wpdb->prepare( $query, $query_arg );
			}

			$res = $wpdb->get_results( $query, ARRAY_A );
			return $res;
		}

		/**
		 * Print json encoded list of affiliate matching filter (param $term in request used to filter)
		 * Array is formatted as affiliate_id => Verbose affiliate description
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function get_affiliates_via_ajax() {
			ob_start();

			check_ajax_referer( 'search-products', 'security' );

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				die(-1);
			}

			$term = wc_clean( stripslashes( $_GET['term'] ) );

			if ( empty( $term ) ) {
				die();
			}

			$found_affiliates = array();
			$found_affiliates_raw = array_merge( $this->get_affiliates( array( 'user_login' => $term ) ), $this->get_affiliates( array( 'user_email' => $term ) ) );

			if( ! empty( $found_affiliates_raw ) ){
				foreach( $found_affiliates_raw as $affiliate ){
					$user = get_user_by( 'id', $affiliate['user_id'] );

					$username = '';
					if ( $user->first_name || $user->last_name ) {
						$username .= esc_html( ucfirst( $user->first_name ) . ' ' . ucfirst( $user->last_name ) );
					}
					else {
						$username .= esc_html( ucfirst( $user->display_name ) );
					}

					$found_affiliates[ $affiliate['ID'] ] = $username . ' (#' . $user->ID . ' &ndash; ' . sanitize_email( $user->user_email ) . ')';
				}
			}

			wp_send_json( $found_affiliates );
		}

		/**
		 * Return affiliate matching passed token
		 *
		 * @param $token string Affiliate token to find
		 * @param $enabled string Whether to find all affiliate whatever the state (all), or only enabled (true) or disabled (false) ones
		 * @return mixed Result
		 * @since 1.0.0
		 */
		public function get_affiliate_by_token( $token, $enabled = 'all' ) {
			global $wpdb;

			$query = "SELECT
                       ya.*,
                       ( ya.earnings + ya.refunds ) AS totals,
                       ( ya.earnings - ya.paid ) AS balance,
                       ( ya.conversion / ya.click * 100 ) AS conv_rate,
                       u.user_login,
                       u.user_email,
                       u.display_name,
                       u.user_nicename
			          FROM {$wpdb->yith_affiliates} AS ya
			          LEFT JOIN {$wpdb->users} AS u ON ya.user_id = u.ID
			          WHERE ya.token = %s";

			$query_args = array(
				$token
			);

			if( isset( $enabled ) && is_bool( $enabled ) ){
				$query .= ' AND ya.enabled = %d';
				$query_args[] = $enabled;
			}

			$res = $wpdb->get_row( $wpdb->prepare( $query, $query_args ), ARRAY_A );
			return $res;
		}

		/**
		 * Return affiliate matching passed id
		 *
		 * @param $id int Affiliate id to find
		 * @param $enabled string Whether to find all affiliate whatever the state (all), or only enabled (true) or disabled (false) ones
		 * @return mixed Result
		 * @since 1.0.0
		 */
		public function get_affiliate_by_id( $affiliate_id, $enabled = 'all' ) {
			global $wpdb;

			$query = "SELECT
                       ya.*,
                       ( ya.earnings + ya.refunds ) AS totals,
                       ( ya.earnings - ya.paid ) AS balance,
                       ( ya.conversion / ya.click * 100 ) AS conv_rate,
                       u.user_login,
                       u.user_email,
                       u.display_name,
                       u.user_nicename
			          FROM {$wpdb->yith_affiliates} AS ya
			          LEFT JOIN {$wpdb->users} AS u ON ya.user_id = u.ID
			          WHERE ya.ID = %d";

			$query_args = array(
				$affiliate_id
			);

			if( isset( $enabled ) && is_bool( $enabled ) ){
				$query .= ' AND ya.enabled = %d';
				$query_args[] = $enabled;
			}

			$res = $wpdb->get_row( $wpdb->prepare( $query, $query_args ), ARRAY_A );
			return $res;
		}

		/**
		 * Return affiliate matching passed user id
		 *
		 * @param $id int User id to find
		 * @param $enabled string Whether to find all affiliate whatever the state (all), or only enabled (true) or disabled (false) ones
		 * @return mixed Result
		 * @since 1.0.0
		 */
		public function get_affiliate_by_user_id( $user_id, $enabled = 'all' ) {
			global $wpdb;

			$query = "SELECT
                       ya.*,
                       ( ya.earnings + ya.refunds ) AS earnings,
                       ( ya.earnings - ya.paid ) AS balance,
                       ( ya.conversion / ya.click * 100 ) AS conv_rate,
                       u.user_login,
                       u.user_email,
                       u.display_name,
                       u.user_nicename
			          FROM {$wpdb->yith_affiliates} AS ya
			          LEFT JOIN {$wpdb->users} AS u ON ya.user_id = u.ID
			          WHERE ya.user_id = %d";

			$query_args = array(
				$user_id
			);

			if( isset( $enabled ) && is_bool( $enabled ) ){
				$query .= ' AND ya.enabled = %d';
				$query_args[] = $enabled;
			}

			$res = $wpdb->get_row( $wpdb->prepare( $query, $query_args ), ARRAY_A );
			return $res;
		}

		/**
		 * Return affiliate rate for a specific affiliate id
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @return float Affiliate rate
		 * @since 1.0.0
		 */
		public function get_affiliate_rate( $affiliate_id ){
			$affiliate = $this->get_affiliate_by_id( $affiliate_id );

			if( ! $affiliate ){
				return false;
			}

			return (float) $affiliate['rate'];
		}

		/**
		 * Update affiliate rate for a specific affiliate id (set it null if no rate is passed)
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @param $rate float New affiliate rate
		 * @return int Operation result
		 * @since 1.0.0
		 */
		public function update_affiliate_rate( $affiliate_id, $rate = false ) {
			global $wpdb;

			if( $rate === false ){
				$res = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->yith_affiliates} SET rate = NULL WHERE ID = %d", $affiliate_id ) );
			}
			else{
				$res = $this->update( $affiliate_id, array( 'rate' => $rate ) );
			}

			return $res;
		}

		/**
		 * Return affiliate earnings for a specific affiliate id
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @return float Affiliate earnings
		 * @since 1.0.0
		 */
		public function get_affiliate_total( $affiliate_id ){
			$affiliate = $this->get_affiliate_by_id( $affiliate_id );

			if( ! $affiliate ){
				return 0;
			}

			return (float) $affiliate['earnings'];
		}

		/**
		 * Update affiliate total for a specific affiliate id (sum amount passed to total)
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @param $amount float Amount to sum to old total
		 * @return int Operation result
		 * @since 1.0.0
		 */
		public function update_affiliate_total( $affiliate_id, $amount ) {
			$total_user_commissions = $this->get_affiliate_total( $affiliate_id );
			$total_user_commissions += (float) $amount;
			$total_user_commissions = $total_user_commissions > 0 ? $total_user_commissions : 0;

			$this->update( $affiliate_id, array( 'earnings' => $total_user_commissions ) );
		}

		/**
		 * Return affiliate refunds for a specific affiliate id
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @return float Affiliate refunds
		 * @since 1.0.0
		 */
		public function get_affiliate_refunds( $affiliate_id ){
			$affiliate = $this->get_affiliate_by_id( $affiliate_id );

			if( ! $affiliate ){
				return 0;
			}

			return (float) $affiliate['refunds'];
		}

		/**
		 * Update affiliate refunds for a specific affiliate id (sum amount passed to total)
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @param $amount float Amount to sum to old total
		 * @return int Operation result
		 * @since 1.0.0
		 */
		public function update_affiliate_refunds( $affiliate_id, $amount ) {
			$total_user_refunds = $this->get_affiliate_refunds( $affiliate_id );
			$total_user_refunds += (float) $amount;
			$total_user_refunds = $total_user_refunds > 0 ? $total_user_refunds : 0;

			$this->update( $affiliate_id, array( 'refunds' => $total_user_refunds ) );
		}

		/**
		 * Return affiliate total payments for a specific affiliate id
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @return float Affiliate refunds
		 * @since 1.0.0
		 */
		public function get_affiliate_payments( $affiliate_id ){
			$affiliate = $this->get_affiliate_by_id( $affiliate_id );

			if( ! $affiliate ){
				return 0;
			}

			return (float) $affiliate['paid'];
		}

		/**
		 * Update affiliate total payments for a specific affiliate id (sum amount passed to total)
		 *
		 * @param $affiliate_id int Affiliate ID
		 * @param $amount float Amount to sum to old total
		 * @return int Operation result
		 * @since 1.0.0
		 */
		public function update_affiliate_payments( $affiliate_id, $amount ) {
			$total_user_payments = $this->get_affiliate_payments( $affiliate_id );
			$total_user_payments += (float) $amount;
			$total_user_payments = $total_user_payments > 0 ? $total_user_payments : 0;

			$this->update( $affiliate_id, array( 'paid' => $total_user_payments ) );
		}

		/**
		 * Return default token for a specific user id
		 *
		 * @param $user_id int User id
		 * @return string User default token
		 * @since 1.0.0
		 */
		public function get_default_user_token( $user_id ){
			$default_token = $user_id;
			return apply_filters( 'yith_wcaf_affiliate_token', $default_token, $user_id );
		}

		/**
		 * Return user object for the given token
		 *
		 * @param $token string Token to use to retrieve user
		 * @return \WP_User|bool User object, or false if token doesn't match any user
		 * @since 1.0.0
		 */
		public function get_user_by_token( $token ) {
			if( ! empty( $token ) ){
				$affiliate = $this->get_affiliate_by_token( $token, true );

				if( ! $affiliate ){
					return false;
				}

				$user = get_user_by( 'id', $affiliate['user_id'] );

				if( $user ){
					return $user;
				}
			}

			return false;
		}

		/**
		 * Check if given string is a valid affiliate token
		 *
		 * @param $token string Token to check
		 * @return bool
		 * @since 1.0.0
		 */
		public function is_valid_token( $token ) {
			$user = $this->get_user_by_token( $token );

			if( ! $user ){
				return false;
			}

			$current_user_id = get_current_user_id();
			$avoid_auto_commissions = get_option( 'yith_wcaf_commission_avoid_auto_referral', 'yes' );

			if( $avoid_auto_commissions == 'yes' && is_user_logged_in() && $user->ID == $current_user_id ){
				return false;
			}

			return apply_filters( 'yith_wcaf_is_valid_token', true, $token );
		}

		/**
		 * Returns true if user is an affiliate
		 *
		 * @param $user_id int|bool Id of the user to check; false if currently logged in user should be considered
		 * @return bool Whether user is an affiliate or not
		 * @since 1.0.0
		 */
		public function is_user_affiliate( $user_id = false ) {
			if( ! $user_id ){
				$user_id = get_current_user_id();
			}

			if( ! $user_id ){
				return false;
			}

			$affiliates = $this->get_affiliates( array( 'user_id' => $user_id ) );

			return apply_filters( 'yith_wcaf_is_user_affiliate', ! empty( $affiliates ), $user_id );
		}

		/**
		 * Returns true if user is an enabled affiliate
		 *
		 * @param $user_id int|bool Id of the user to check; false if currently logged in user should be considered
		 * @return bool Whether user is an enabled affiliate or not
		 * @since 1.0.0
		 */
		public function is_user_enabled_affiliate( $user_id = false ) {
			if( ! $user_id ){
				$user_id = get_current_user_id();
			}

			if( ! $user_id ){
				return false;
			}

			$affiliates = $this->get_affiliates( array( 'user_id' => $user_id, 'enabled' => 'enabled' ) );

			return apply_filters( 'yith_wcaf_is_user_enabled_affiliate', ! empty( $affiliates ), $user_id );
		}

		/**
		 * Returns count of affiliate, grouped by status
		 *
		 * @param $status string Specific status to count, or all to obtain a global statistic
		 * @return int|mixed Count per state, or array indexed by status, with status count
		 * @since 1.0.0
		 */
		public function per_status_count( $status = 'all' ) {
			global $wpdb;

			$res = $wpdb->get_results( "SELECT ya.enabled, COUNT( ya.enabled ) AS status_count FROM {$wpdb->yith_affiliates} AS ya GROUP BY enabled", ARRAY_A );

			$statuses = yith_wcaf_array_column( $res, 'enabled' );
			$counts = yith_wcaf_array_column( $res, 'status_count' );

			if( $status == 'all' ){
				return array_sum( $counts );
			}
			else {
				$status = ( $status == 'enabled' ) ? 1 : 0;

				if ( in_array( $status, $statuses ) ) {
					$index = array_search( $status, $statuses );

					if ( $index === FALSE ) {
						return 0;
					} else {
						return $counts[ $index ];
					}
				} else {
					return 0;
				}
			}
		}

		/**
         * Returns true if affiliate has some unpaid commissions
         *
         * @param $affiliate_id int Affiliate id
         * @return bool Whether affiliate has unpaid commissions or not
         * @since 1.0.10
		 */
		public function has_unpaid_commissions( $affiliate_id ) {
            $unpaid_commissions = YITH_WCAF_Commission_Handler_Premium()->get_commissions( array(
	            'affiliate_id' => $affiliate_id,
                'status__not_in' => YITH_WCAF_Commission_Handler()->payment_status
            ) );

            $found = false;

            if( ! empty( $unpaid_commissions ) ){
                foreach( $unpaid_commissions as $unpaid_commission ){
                    $available_status_change = YITH_WCAF_Commission_Handler()->get_available_status_change( $unpaid_commission['ID'] );
	                if( in_array( 'pending-payment', $available_status_change ) ){
	                    $found = true;
	                    break;
                    }
                }
            }

            return $found;
		}

		/* === PANEL HANDLING METHODS === */

		/**
		 * Print Affiliate panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_affiliate_panel() {
			// define variables to be used in template
			$affiliates_table = new YITH_WCAF_Affiliates_Table();
			$affiliates_table->prepare_items();

			include( YITH_WCAF_DIR . 'templates/admin/affiliate-panel.php' );
		}

		/**
		 * Add Screen option
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_screen_option() {
			if ( 'yith-plugins_page_yith_wcaf_panel' == get_current_screen()->id && isset( $_GET['tab'] ) && $_GET['tab'] == 'affiliates' ) {
				add_screen_option( 'per_page', array( 'label' => __( 'Affiliates', 'yith-woocommerce-affiliates' ), 'default' => 20, 'option' => 'edit_affiliates_per_page' ) );

			}
		}

		/**
		 * Save custom screen options
		 *
		 * @param $set bool Value to filter (default to false)
		 * @param $option string Custom screen option key
		 * @param $value mixed Custom screen option value
		 * @return mixed Value to be saved as user meta; false if no value should be saved
		 */
		public function set_screen_option( $set, $option, $value ){
			return ( isset( $_GET['tab'] ) && 'affiliates' == $_GET['tab'] && 'edit_affiliates_per_page' == $option ) ? $value : $set;
		}

		/**
		 * Add columns filters to commissions page
		 *
		 * @param $columns mixed Available columns
		 * @return mixed The columns array to print
		 * @since 1.0.0
		 */
		public function add_screen_columns( $columns ) {
			if( isset( $_GET['tab'] ) && $_GET['tab'] == 'affiliates' ) {
				$columns = array_merge(
					$columns,
					array(
						'id'         => __( 'ID', 'yith-woocommerce-affiliates' ),
						'token'      => __( 'Token', 'yith-woocommerce-affiliates' ),
						'status'     => __( 'Status', 'yith-woocommerce-affiliates' ),
						'affiliate'  => __( 'Affiliate', 'yith-woocommerce-affiliates' ),
						'rate'       => __( 'Rate', 'yith-woocommerce-affiliates' ),
						'earnings'   => __( 'Earnings', 'yith-woocommerce-affiliates' ),
						'refunds'    => __( 'Refunds', 'yith-woocommerce-affiliates' ),
						'paid'       => __( 'Paid', 'yith-woocommerce-affiliates' ),
						'balance'    => __( 'Balance', 'yith-woocommerce-affiliates' ),
						'click'      => __( 'Click', 'yith-woocommerce-affiliates' ),
						'conversion' => __( 'Conversion', 'yith-woocommerce-affiliates' ),
						'conv_rate'  => __( 'Conv. Rate', 'yith-woocommerce-affiliates' ),
						'actions'    => __( 'Action', 'yith-woocommerce-affiliates' )
					)
				);
			}

			return $columns;
		}

		/**
		 * Handle affiliate user status change
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function handle_switch_status_panel_actions() {
			$affiliate_id = isset( $_REQUEST['affiliate_id'] ) ? $_REQUEST['affiliate_id'] : 0;
			$new_status = isset( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], array( 'enabled', 'disabled' ) ) ? $_REQUEST['status'] : '';

			if( ! $affiliate_id || ! $new_status ){
				return;
			}

			$enabled = $_REQUEST['status'] == 'enabled' ? 1 : 0;

			$res = $this->update( $affiliate_id, array( 'enabled' => $enabled ) );

			wp_redirect( esc_url_raw( add_query_arg( array( 'page' => 'yith_wcaf_panel', 'tab' => 'affiliates', 'commission_status_change' => $res ), admin_url( 'admin.php' ) ) ) );
			die();
		}

		/* === EDIT PROFILE METHODS === */

		/**
		 * Render affiliate fields
		 *
		 * @param $user \WP_User User object
		 * @return void
		 * @since  1.0.0
		 */
		public function render_affiliate_extra_fields( $user ) {
			$affiliate = false;

			if( isset( $user->ID ) ) {
				$affiliates = $this->get_affiliates( array( 'user_id' => $user->ID ) );
				$affiliate  = isset( $affiliates[0] ) ? $affiliates[0] : false;
			}

			$is_affiliate = $affiliate ? true : false;
			$is_enabled = isset( $affiliate['enabled'] ) ? $affiliate['enabled'] : 0;
			$token = isset( $affiliate['token'] ) ? $affiliate['token'] : '';
			$token = ( empty( $token ) && isset( $user->ID ) ) ? $this->get_default_user_token( $user->ID ) : $token;
			$rate = isset( $affiliate['rate'] ) ? $affiliate['rate'] : '';
			$payment_email = isset( $affiliate['payment_email'] ) ? $affiliate['payment_email'] : '';

			?>
			<hr />
			<h3><?php _e( 'Affiliate details', 'yith-woocommerce-affiliates' )?></h3>
			<table class="form-table">
				<tr>
					<th><label for="affiliate"><?php _e( 'Affiliate', 'yith-woocommerce-affiliates' )?></label></th>
					<td>
						<input type="checkbox" name="affiliate" id="affiliate" value="1" <?php checked( $is_affiliate, true ) ?> />
						<span class="description"><?php _e( 'Check if this user is an affiliate', 'yith-woocommerce-affiliates' ) ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="enabled"><?php _e( 'Enabled', 'yith-woocommerce-affiliates' )?></label></th>
					<td>
						<input type="checkbox" name="enabled" id="enabled" value="1" <?php checked( $is_enabled, true ) ?> />
						<span class="description"><?php _e( 'If this user is an affiliate, you can choose to enable or disable it', 'yith-wcaf' ) ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="token"><?php _e( 'Token', 'yith-woocommerce-affiliates' )?></label></th>
					<td>
						<input type="text" name="token" id="token" value="<?php echo esc_attr( $token ) ?>" />
						<span class="description"><?php _e( 'Token for the user (default to user ID)', 'yith-woocommerce-affiliates' ) ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="rate"><?php _e( 'Rate', 'yith-woocommerce-affiliates' )?></label></th>
					<td>
						<input type="number" min="0" max="100" step="any" name="rate" id="rate" value="<?php echo esc_attr( $rate ) ?>" />
						<span class="description"><?php _e( 'User-specific rate to apply, if any (general rates will be applied if left empty)', 'yith-wcaf' ) ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="payment_email"><?php _e( 'Payment email', 'yith-woocommerce-affiliates' )?></label></th>
					<td>
						<input type="email" name="payment_email" id="rate" value="<?php echo esc_attr( $payment_email ) ?>" />
						<span class="description"><?php _e( 'Address email where affiliate wants to receive PayPal payments', 'yith-woocommerce-affiliates' ) ?></span>
					</td>
				</tr>

			</table>
		<?php
		}

		/**
		 * Save affiliate fields
		 *
		 * @param $user_id int User id
		 * @return bool Whether method actually saved option or not
		 * @since  1.0.0
		 */
		public function save_affiliate_extra_fields( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			$affiliates = $this->get_affiliates( array( 'user_id' => $user_id ) );
			$affiliate = isset( $affiliates[0] ) ? $affiliates[0] : false ;

			$is_affiliate = isset( $_POST['affiliate'] ) ? $_POST['affiliate'] : false;
			$is_enabled = isset( $_POST['enabled'] ) ? $_POST['enabled'] : 0;
			$token = ( isset( $_POST['token'] ) && $_POST['token'] != '' ) ? trim( $_POST['token'] ) : $this->get_default_user_token( $user_id );
			$rate = ( isset( $_POST['rate'] ) && $_POST['rate'] != '' ) ? doubleval( $_POST['rate'] ) : false;
			$payment_email = isset( $_POST['payment_email'] ) ? trim( $_POST['payment_email'] ) : '';

			if( $is_affiliate && ! $affiliate ){
				$this->add(
					array_merge(
						array(
							'user_id' => $user_id,
							'token' => $token,
							'enabled' => $is_enabled,
							'payment_email' => $payment_email
						),
						$rate !== false ? array( 'rate' => $rate ) : array()
					)
				);
			}
			elseif( $is_affiliate && $affiliate ){
				$this->update(
					$affiliate['ID'],
					array(
						'token' => $token,
						'enabled' => $is_enabled,
						'payment_email' => $payment_email
					)
				);

				$this->update_affiliate_rate( $affiliate['ID'], $rate );
			}
			elseif( ! $is_affiliate && $affiliate ){
				$this->delete( $affiliate['ID'] );
			}
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCAF_Affiliate_Handler
		 * @since 1.0.2
		 */
		public static function get_instance(){
			if( class_exists( 'YITH_WCAF_Affiliate_Handler_Premium' ) ) {
				if ( is_null( YITH_WCAF_Affiliate_Handler_Premium::$instance ) ) {
					YITH_WCAF_Affiliate_Handler_Premium::$instance = new YITH_WCAF_Affiliate_Handler_Premium;
				}

				return YITH_WCAF_Affiliate_Handler_Premium::$instance;
			}
			else{
				if ( is_null( YITH_WCAF_Affiliate_Handler::$instance ) ) {
					YITH_WCAF_Affiliate_Handler::$instance = new YITH_WCAF_Affiliate_Handler;
				}

				return YITH_WCAF_Affiliate_Handler::$instance;
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WCAF_Affiliate_Handler class
 *
 * @return \YITH_WCAF_Affiliate_Handler
 * @since 1.0.0
 */
function YITH_WCAF_Affiliate_Handler(){
	return YITH_WCAF_Affiliate_Handler::get_instance();
}