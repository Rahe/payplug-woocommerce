<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
use const OPENSSL_VERSION_TEXT;
use const PHP_VERSION;
use function sprintf;
use function var_dump;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayplugGatewayRequirements {

	const PHP_MIN = '5.6';
	const OPENSSL_MIN = 268439871;

	/**
	 * @var PayplugGateway
	 */
	private $gateway;

	/**
	 * PayplugGatewayRequirements constructor.
	 *
	 * @param PayplugGateway $gateway
	 */
	public function __construct( PayplugGateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * @return string
	 */
	public function curl_requirement() {
		return ( $this->valid_curl() )
			? '<p class="success">' . __( 'The PHP Curl extention is installed and available.', 'payplug' ) . '</p>'
			: '<p class="failed">' . __( 'The PHP Curl extention was not found.', 'payplug' ) . '</p>';
	}

	/**
	 * @return string
	 */
	public function php_requirement() {
		return ( $this->valid_php() )
			? '<p class="success">' . __( 'Your PHP version is up-to-date.', 'payplug' ) . '</p>'
			: '<p class="failed">' . __( sprintf( 'Your PHP version %s is not supported. The minimum supported version is 5.6.', PHP_VERSION ), 'payplug' ) . '</p>';
	}

	/**
	 * @return string
	 */
	public function openssl_requirement() {
		return ( $this->valid_openssl() )
			? '<p class="success">' . __( 'Your OpenSSL version is up-to-date.', 'payplug' ) . '</p>'
			: '<p class="failed">' . __( sprintf( 'Your OpenSSL version %s is not supported. The minimum supported version is 1.0.1.', OPENSSL_VERSION_TEXT ), 'payplug' ) . '</p>';
	}

	/**
	 * @return string
	 */
	public function currency_requirement() {
		return ( $this->valid_currency() )
			? '<p class="success">' . __( 'Your shop use Euro as your currency.', 'payplug' ) . '</p>'
			: '<p class="failed">' . __( 'Your shop must use Euro as your currency.', 'payplug' ) . '</p>';
	}

	/**
	 * @return string
	 */
	public function account_requirement() {
		return ( $this->valid_account() )
			? '<p class="success">' . __( 'You are logged in to your PayPlug account.', 'payplug' ) . '</p>'
			: '<p class="failed">' . __( 'You must logged in to your PayPlug account.', 'payplug' ) . '</p>';
	}

	/**
	 * Check if CURL is available and support SSL
	 *
	 * @return bool
	 */
	public function valid_curl() {
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
			return false;
		}

		// Also Check for SSL support
		$curl_version = curl_version();
		if ( ! ( CURL_VERSION_SSL & $curl_version['features'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if PHP version is equal or above the minimum supported.
	 *
	 * @return bool
	 */
	public function valid_php() {
		return version_compare( PHP_VERSION, self::PHP_MIN, '>=' );
	}

	/**
	 * Check if OPENSSL version is equal or above the minimum supported.
	 *
	 * @return bool
	 */
	public function valid_openssl() {
		return OPENSSL_VERSION_NUMBER > self::OPENSSL_MIN;
	}

	/**
	 * Check if the shop currency is Euro.
	 *
	 * @return bool
	 */
	public function valid_currency() {
		return 'EUR' === get_woocommerce_currency();
	}

	/**
	 * Check if the user is logged in.
	 *
	 * @return bool
	 */
	public function valid_account() {
		return $this->gateway->user_logged_in();
	}
}