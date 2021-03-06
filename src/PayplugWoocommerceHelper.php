<?php

namespace Payplug\PayplugWoocommerce;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Resource\APIResource;

/**
 * Helper class.
 *
 * @package Payplug\PayplugWoocommerce
 */
class PayplugWoocommerceHelper {

	/**
	 * Check if current WooCommerce version is below 3.0.0
	 *
	 * @return bool
	 */
	public static function is_pre_30() {
		$wc = function_exists( 'WC' ) ? WC() : $GLOBALS['woocommerce'];

		return version_compare( $wc->version, '3.0.0', '<' );
	}

	/**
	 * Get a URL to the PayPlug gateway settings page.
	 *
	 * @return string
	 */
	public static function get_setting_link() {
		$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;
		$section_slug      = $use_id_as_section ? 'payplug' : strtolower( 'PayplugGateway' );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	/**
	 * Get all country code supported by PayPlug.
	 *
	 * Those are ISO 3166-1 alpha-2. You can find more information on https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
	 *
	 * @return array
	 */
	public static function get_supported_countries() {
		return [
			'AD',
			'AO',
			'AX',
			'BG',
			'BO',
			'BY',
			'CH',
			'CR',
			'DE',
			'EE',
			'FI',
			'GB',
			'GL',
			'GT',
			'HR',
			'IN',
			'JM',
			'KM',
			'KZ',
			'LS',
			'MD',
			'MM',
			'MT',
			'NA',
			'NO',
			'PE',
			'PN',
			'RE',
			'SC',
			'SK',
			'ST',
			'TF',
			'TN',
			'UA',
			'VC',
			'WS',
			'AE',
			'AQ',
			'AZ',
			'BH',
			'BQ',
			'BZ',
			'CI',
			'CU',
			'DJ',
			'EG',
			'FJ',
			'GD',
			'GM',
			'GU',
			'HT',
			'IO',
			'JO',
			'KN',
			'LA',
			'LT',
			'ME',
			'MN',
			'MU',
			'NC',
			'NP',
			'PF',
			'PR',
			'RO',
			'SD',
			'SL',
			'SV',
			'TG',
			'TO',
			'UG',
			'VE',
			'YE',
			'AM',
			'AW',
			'BF',
			'BN',
			'BW',
			'CG',
			'CO',
			'AF',
			'CZ',
			'EC',
			'EU',
			'GA',
			'GI',
			'GS',
			'HN',
			'IM',
			'JE',
			'KI',
			'KY',
			'LR',
			'MC',
			'ML',
			'MS',
			'MZ',
			'NL',
			'PA',
			'PM',
			'QA',
			'SB',
			'SJ',
			'SS',
			'TD',
			'TM',
			'TZ',
			'VA',
			'WF',
			'AR',
			'BA',
			'BI',
			'BR',
			'CA',
			'CK',
			'CV',
			'DK',
			'EH',
			'FK',
			'GE',
			'GN',
			'GW',
			'HU',
			'IQ',
			'JP',
			'KP',
			'LB',
			'LU',
			'MF',
			'MO',
			'MV',
			'NE',
			'NR',
			'PG',
			'PS',
			'RS',
			'SE',
			'SM',
			'SX',
			'TH',
			'TR',
			'UM',
			'VG',
			'YT',
			'AL',
			'AU',
			'BE',
			'BM',
			'BV',
			'CF',
			'CN',
			'CY',
			'DZ',
			'ET',
			'FR',
			'GH',
			'GR',
			'HM',
			'IL',
			'IT',
			'KH',
			'KW',
			'LK',
			'MA',
			'MK',
			'MR',
			'MY',
			'NI',
			'OM',
			'PL',
			'PY',
			'SA',
			'SI',
			'SR',
			'TC',
			'TL',
			'TW',
			'UZ',
			'VU',
			'ZW',
			'AI',
			'AT',
			'BD',
			'BL',
			'BT',
			'CD',
			'CM',
			'CX',
			'DO',
			'ES',
			'FO',
			'GG',
			'GQ',
			'HK',
			'IE',
			'IS',
			'KG',
			'KS',
			'LI',
			'LY',
			'MH',
			'MQ',
			'MX',
			'NG',
			'NZ',
			'PK',
			'PW',
			'RW',
			'SH',
			'SO',
			'SZ',
			'TK',
			'TV',
			'UY',
			'VN',
			'ZM',
			'AG',
			'AS',
			'BB',
			'BJ',
			'BS',
			'CC',
			'CL',
			'CW',
			'DM',
			'ER',
			'FM',
			'GF',
			'GP',
			'GY',
			'ID',
			'IR',
			'KE',
			'KR',
			'LC',
			'LV',
			'MG',
			'MP',
			'MW',
			'NF',
			'NU',
			'PH',
			'PT',
			'RU',
			'SG',
			'SN',
			'SY',
			'TJ',
			'TT',
			'US',
			'VI',
			'ZA'
		];
	}

	/**
	 * Ensure country code is supported by PayPlug.
	 *
	 * @param string $country The ISO 3166-1 alpha-2 code for the country
	 *
	 * @return bool
	 * @author Clément Boirie
	 */
	public static function is_country_supported( $country ) {
		$country = trim( $country );
		if ( empty( $country ) ) {
			return false;
		}

		return in_array( strtoupper( $country ), self::get_supported_countries() );
	}

	/**
	 * Get minimum amount allowed by PayPlug.
	 *
	 * This amount is in cents.
	 *
	 * @return int
	 */
	public static function get_minimum_amount() {
		return 99;
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

	/**
	 * Convert amount in cents.
	 *
	 * @param float $amount
	 *
	 * @return int
	 */
	public static function get_payplug_amount( $amount ) {
		if ( is_null( $amount ) ) {
			return $amount;
		}

		return absint( wc_format_decimal( ( (float) $amount * 100 ), wc_get_price_decimals() ) );
	}

	/**
	 * Extract useful metadata from PayPlug response.
	 *
	 * @param APIResource $resource
	 *
	 * @return array
	 */
	public static function extract_transaction_metadata( $resource ) {
		return [
			'transaction_id'  => sanitize_text_field( $resource->id ),
			'paid'            => (bool) $resource->is_paid,
			'refunded'        => (bool) $resource->is_refunded,
			'amount'          => sanitize_text_field( $resource->amount ),
			'amount_refunded' => sanitize_text_field( $resource->amount_refunded ),
			'3ds'             => (bool) $resource->is_3ds,
			'live'            => (bool) $resource->is_live,
			'paid_at'         => isset( $resource->hosted_payment->paid_at ) ? sanitize_text_field( $resource->hosted_payment->paid_at ) : sanitize_text_field( $resource->created_at ),
			'card_last4'      => sanitize_text_field( $resource->card->last4 ),
			'card_exp_month'  => sanitize_text_field( $resource->card->exp_month ),
			'card_exp_year'   => sanitize_text_field( $resource->card->exp_year ),
			'card_brand'      => sanitize_text_field( $resource->card->brand ),
			'card_country'    => sanitize_text_field( $resource->card->country ),
		];
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return array|bool
	 * @author Clément Boirie
	 */
	public static function get_transaction_metadata( $order ) {

		if ( PayplugWoocommerceHelper::is_pre_30() ) {
			return get_post_meta( $order->id, '_payplug_metadata', true );
		} else {
			return $order->get_meta( '_payplug_metadata', true );
		}
	}

	/**
	 * Save transaction metadata extracted from PayPlug response.
	 *
	 * @param \WC_Order $order
	 * @param array $metadata
	 *
	 * @return void
	 */
	public static function save_transaction_metadata( $order, $metadata ) {

		if ( PayplugWoocommerceHelper::is_pre_30() ) {
			update_post_meta( $order->id, '_payplug_metadata', $metadata );
		} else {
			$order->add_meta_data( '_payplug_metadata', $metadata, true );

			if ( is_callable( [ $order, 'save' ] ) ) {
				$order->save();
			}
		}
	}
}