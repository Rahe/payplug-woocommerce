<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Exception\NotFoundException;
use Payplug\Payplug;
use Payplug\Core\HttpClient;

/**
 * Handle calls to PayPlug PHP client.
 *
 * @package Payplug\PayplugWoocommerce\Gateway
 */
class PayplugApi {

	/**
	 * @var PayplugGateway
	 */
	protected $gateway;

	/**
	 * PayplugApi constructor.
	 *
	 * @param PayplugGateway $gateway
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Configure PayPlug client.
	 */
	public function init() {
		$current_mode = $this->gateway->get_current_mode();
		$key          = $this->gateway->get_api_key( $current_mode );

		Payplug::setSecretKey( $key );
		HttpClient::addDefaultUserAgentProduct(
			'PayPlug-WooCommerce',
			PAYPLUG_GATEWAY_VERSION,
			sprintf( 'WooCommerce/%s', WC()->version )
		);
	}

	/**
	 * Retrieve payment data from PayPlug API.
	 *
	 * @param string $transaction_id
	 *
	 * @return null|\Payplug\Resource\Payment
	 * @throws \Payplug\Exception\ConfigurationException
	 */
	public function payment_retrieve( $transaction_id ) {
		return $this->do_request_with_fallback( '\Payplug\Payment::retrieve', $transaction_id );
	}

	/**
	 * Create a payment.
	 *
	 * @param array $data
	 *
	 * @return null|\Payplug\Resource\Payment
	 */
	public function payment_create( $data ) {
		return $this->do_request( '\Payplug\Payment::create', [ $data ] );
	}

	/**
	 * Retrieve all refunds associated with a payment.
	 *
	 * @param string $transaction_id
	 *
	 * @return \Payplug\Resource\Refund[]
	 * @throws \Payplug\Exception\ConfigurationException
	 */
	public function refund_list( $transaction_id ) {
		return $this->do_request_with_fallback( '\Payplug\Refund::listRefunds', $transaction_id );
	}

	/**
	 * Create a refund.
	 *
	 * @param string $transaction_id
	 * @param array $data
	 *
	 * @return null|\Payplug\Resource\Refund
	 * @throws \Payplug\Exception\ConfigurationException
	 * @author Clément Boirie
	 */
	public function refund_create( $transaction_id, $data ) {
		return $this->do_request_with_fallback( '\Payplug\Refund::create', [ $transaction_id, $data ] );
	}

	/**
	 * Invoke PayPlug API. If it fail it switch to the other mode and retry the same request.
	 *
	 * @param callable $callback
	 * @param array $params
	 *
	 * @return object
	 * @throws \Payplug\Exception\ConfigurationException
	 */
	protected function do_request_with_fallback( $callback, $params = [] ) {
		try {
			$response = $this->do_request( $callback, $params );
		} catch ( NotFoundException $e ) {
			try {
				$this->switch_mode();
				$response = $this->do_request( $callback, $params );
				$this->restore_mode();
			} catch ( \Exception $e ) {
				$this->restore_mode();
				throw $e;
			}
		}

		return $response;
	}

	/**
	 * Invoke PayPlug API.
	 *
	 * @param callable $callback
	 * @param array $params
	 *
	 * @return object
	 */
	protected function do_request( $callback, $params = [] ) {

		if ( ! is_array( $params ) ) {
			$params = [ $params ];
		}

		return call_user_func_array( $callback, $params );
	}

	/**
	 * Reconfigure PayPlug client with new secret keys.
	 *
	 * @throws \Payplug\Exception\ConfigurationException
	 */
	protected function switch_mode() {
		$switched_mode = 'test' === $this->gateway->get_current_mode() ? 'live' : 'test';
		$new_key       = $this->gateway->get_api_key( $switched_mode );
		if ( empty( $new_key ) ) {
			throw new \Exception( sprintf(
				'No secret key available for the %s mode', $switched_mode
			) );
		}

		Payplug::setSecretKey( $new_key );
	}

	/**
	 * Restore PayPlug client secret keys for the current mode.
	 *
	 * @throws \Payplug\Exception\ConfigurationException
	 */
	protected function restore_mode() {
		$key = $this->gateway->get_api_key( $this->gateway->get_current_mode() );
		if ( empty( $key ) ) {
			throw new \Exception( sprintf(
				'No secret key available for the %s mode', $this->gateway->get_current_mode()
			) );
		}

		Payplug::setSecretKey( $key );
	}
}