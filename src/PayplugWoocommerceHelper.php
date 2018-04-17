<?php

namespace Payplug\PayplugWoocommerce;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class.
 *
 * @package Payplug\PayplugWoocommerce
 */
class PayplugWoocommerceHelper {

	/**
	 * @return bool
	 */
	public static function is_pre_30() {
		$wc = function_exists( 'WC' ) ? WC() : $GLOBALS['woocommerce'];

		return version_compare( $wc->version, '3.0.0', '<' );
	}

	/**
	 * @return string
	 */
	public static function get_setting_link() {
		$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;
		$section_slug      = $use_id_as_section ? 'payplug' : strtolower( 'PayplugGateway' );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	/**
	 * Get minimum amount allowed by PayPlug.
	 *
	 * This amount is in cents.
	 *
	 * @return int
	 */
	public static function get_minimum_amount() {
		return 100;
	}

	/**
	 * Get maximum amount allowed by PayPlug.
	 *
	 * This amount is in cents.
	 *
	 * @return int
	 */
	public static function get_maximum_amount() {
		return 2000000;
	}
}