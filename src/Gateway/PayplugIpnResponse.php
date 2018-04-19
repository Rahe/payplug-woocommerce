<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Exception\UnknownAPIResourceException;
use Payplug\Notification;
use Payplug\PayplugWoocommerce\PayplugWoocommerceHelper;

class PayplugIpnResponse {

	public function __construct() {
		add_action( 'woocommerce_api_paypluggateway', [ $this, 'handle_ipn_response' ] );
	}

	public function handle_ipn_response() {
		$input = file_get_contents( 'php://input' );

		try {
			$resource = Notification::treat( $input );
		} catch ( UnknownAPIResourceException $e ) {
			PayplugGateway::log( sprintf( 'PayPlug IPN - Error while parsing IPN payload : %s', $e->getMessage() ), 'error' );
			exit;
		}

		if ( ! $this->validate_ipn( $resource ) ) {
			PayplugGateway::log( sprintf( 'PayPlug IPN - Resource %s is not supported.', $resource->object ), 'error' );
			exit;
		}

		if ( ! method_exists( $this, sprintf( 'process_%s_resource', $resource->object ) ) ) {
			PayplugGateway::log( sprintf( 'PayPlug IPN - Can\'t process resource %s', $resource->object ), 'error' );
			exit;
		}

		$order_id = $resource->metadata['order_id'];
		$order    = wc_get_order( $order_id );
		if ( false === $order ) {
			PayplugGateway::log( sprintf( 'PayPlug IPN - Coudn\'t find order #%s .', $order_id ), 'error' );
			exit;
		}

		call_user_func( [ $this, sprintf( 'process_%s_resource', $resource->object ) ], $order, $resource );
		exit;
	}

	/**
	 * Validate IPN notification.
	 *
	 * @param $resource
	 *
	 * @return bool
	 */
	public function validate_ipn( $resource ) {
		return isset( $resource->object ) && in_array( $resource->object, [ 'payment', 'refund' ] );
	}

	/**
	 * Process payment notification.
	 *
	 * @param \WC_Order $order
	 * @param $resource
	 *
	 * @return void
	 * @throws \WC_Data_Exception
	 */
	public function process_payment_resource( $order, $resource ) {

		// Ignore paid orders
		$order_id = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			PayplugGateway::log( 'Order #' . $order_id . ' is already complete.' );

			return;
		}

		if ( $resource->is_paid ) {
			PayplugWoocommerceHelper::is_pre_30() ? update_post_meta( $order_id, '_transaction_id', $resource->id ) : $order->set_transaction_id( $resource->id );
			$order->add_order_note( sprintf( __( 'PayPlug IPN OK | Transaction #%s', 'payplug' ), $resource->id ) );
			$order->payment_complete( $resource->id );
			if ( PayplugWoocommerceHelper::is_pre_30() ) {
				$order->reduce_order_stock();
			}

			return;
		}

		if ( ! empty( $resource->failure ) ) {
			$order->update_status(
				'failed',
				sprintf( __( 'PayPlug IPN OK | Transaction #%s failed : %s', 'payplug' ), $resource->id, wc_clean( $resource->failure->message ) )
			);

			return;
		}
	}

	/**
	 * Process refund notification.
	 *
	 * @param \WC_Order $order
	 * @param $resource
	 *
	 * @author Clément Boirie
	 */
	public function process_refund_resource( $order, $resource ) {

	}
}