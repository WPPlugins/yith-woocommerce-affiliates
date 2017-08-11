<?php
/**
 * Utility functions
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

if( ! function_exists( 'yith_wcaf_locate_template' ) ){
	/**
	 * Locate template for Affiliate plugin
	 *
	 * @param $filename string Template name (with or without extension)
	 * @param $section string Subdirectory where to search
	 * @return string Found template
	 */
	function yith_wcaf_locate_template( $filename, $section = '' ){
		$ext = strpos( $filename, '.php' ) === false ? '.php' : '';

		$template_name      = $section . '/' . $filename . $ext;
		$template_path      = WC()->template_path() . 'yith-wcaf/';
		$default_path       = YITH_WCAF_DIR . 'templates/';

		if( defined( 'YITH_WCAF_PREMIUM' ) ){
			$premium_template   = str_replace( '.php', '-premium.php', $template_name );
			$located_premium    = wc_locate_template( $premium_template, $template_path, $default_path );
			$template_name      = file_exists( $located_premium ) ?  $premium_template : $template_name;
		}

		return wc_locate_template( $template_name, $template_path, $default_path );
	}
}

if( ! function_exists( 'yith_wcaf_get_template' ) ){
	/**
	 * Get template for Affiliate plugin
	 *
	 * @param $filename string Template name (with or without extension)
	 * @param $args mixed Array of params to use in the template
	 * @param $section string Subdirectory where to search
	 */
	function yith_wcaf_get_template( $filename, $args = array(), $section = '' ){
		$ext = strpos( $filename, '.php' ) === false ? '.php' : '';

		$template_name      = $section . '/' . $filename . $ext;
		$template_path      = WC()->template_path() . 'yith-wcaf/';
		$default_path       = YITH_WCAF_DIR . 'templates/';

		if( defined( 'YITH_WCAF_PREMIUM' ) ){
			$premium_template   = str_replace( '.php', '-premium.php', $template_name );
			$located_premium    = wc_locate_template( $premium_template, $template_path, $default_path );
			$template_name      = file_exists( $located_premium ) ?  $premium_template : $template_name;
		}

		wc_get_template( $template_name, $args, $template_path, $default_path );
	}
}

if( ! function_exists( 'yith_wcaf_array_column' ) ){
	/**
	 * Implement array column for PHP older then 5.5
	 *
	 * @param $input array Input multidimensional array
	 * @param $columnKey string Array column
	 * @param $indexKey string Array to be used as keys for result
	 * @return Array Column extracted
	 * @since 1.0.1
	 */
	function yith_wcaf_array_column( $input = null, $columnKey = null, $indexKey = null ){
		if( function_exists( 'array_column' ) ){
			return array_column( $input, $columnKey, $indexKey );
		}
		else{
			// Using func_get_args() in order to check for proper number of
			// parameters and trigger errors exactly as the built-in array_column()
			// does in PHP 5.5.
			$argc = func_num_args();
			$params = func_get_args();
			if ($argc < 2) {
				trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
				return null;
			}
			if (!is_array($params[0])) {
				trigger_error(
					'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
					E_USER_WARNING
				);
				return null;
			}
			if (!is_int($params[1])
			    && !is_float($params[1])
			    && !is_string($params[1])
			    && $params[1] !== null
			    && !(is_object($params[1]) && method_exists($params[1], '__toString'))
			) {
				trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
				return false;
			}
			if (isset($params[2])
			    && !is_int($params[2])
			    && !is_float($params[2])
			    && !is_string($params[2])
			    && !(is_object($params[2]) && method_exists($params[2], '__toString'))
			) {
				trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
				return false;
			}
			$paramsInput = $params[0];
			$paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
			$paramsIndexKey = null;
			if (isset($params[2])) {
				if (is_float($params[2]) || is_int($params[2])) {
					$paramsIndexKey = (int) $params[2];
				} else {
					$paramsIndexKey = (string) $params[2];
				}
			}
			$resultArray = array();
			foreach ($paramsInput as $row) {
				$key = $value = null;
				$keySet = $valueSet = false;
				if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
					$keySet = true;
					$key = (string) $row[$paramsIndexKey];
				}
				if ($paramsColumnKey === null) {
					$valueSet = true;
					$value = $row;
				} elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
					$valueSet = true;
					$value = $row[$paramsColumnKey];
				}
				if ($valueSet) {
					if ($keySet) {
						$resultArray[$key] = $value;
					} else {
						$resultArray[] = $value;
					}
				}
			}
			return $resultArray;
		}
	}
}

if( ! function_exists( 'yith_wcaf_get_current_affiliate_token' ) ){
	/**
	 * Returns current affiliate token, if any; otherwise false
	 * 
	 * @return string|bool Affiliate token or false
	 * @since 1.0.9
	 */
	function yith_wcaf_get_current_affiliate_token(){
		if( ! did_action( 'init' ) ){
			_doing_it_wrong( 'yith_wcaf_get_current_affiliate_token', __( 'yith_wcaf_get_current_affiliate_token() should be called after init', 'yith-woocommerce-affiliates' ), '1.0.9' );
			return false;
		}

		return YITH_WCAF_Affiliate()->get_token();
	}
}

if( ! function_exists( 'yith_wcaf_get_current_affiliate' ) ){
	/**
	 * Returns current affiliate token, if any; otherwise false
	 *
	 * @return string|bool Affiliate token or false
	 * @since 1.0.9
	 */
	function yith_wcaf_get_current_affiliate(){
		if( ! did_action( 'init' ) ){
			_doing_it_wrong( 'yith_wcaf_get_current_affiliate', __( 'yith_wcaf_get_current_affiliate() should be called after init', 'yith-woocommerce-affiliates' ), '1.0.9' );
			return false;
		}

		return YITH_WCAF_Affiliate()->get_affiliate();
	}
}

if( ! function_exists( 'yith_wcaf_get_current_affiliate_user' ) ){
	/**
	 * Returns current affiliate token, if any; otherwise false
	 *
	 * @return string|bool Affiliate token or false
	 * @since 1.0.9
	 */
	function yith_wcaf_get_current_affiliate_user(){
		if( ! did_action( 'init' ) ){
			_doing_it_wrong( 'yith_wcaf_get_current_affiliate_user', __( 'yith_wcaf_get_current_affiliate_user() should be called after init', 'yith-woocommerce-affiliates' ), '1.0.9' );
			return false;
		}

		return YITH_WCAF_Affiliate()->get_user();
	}
}