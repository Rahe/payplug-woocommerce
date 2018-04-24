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