<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Exception\UnknownAPIResourceException;
use Payplug\Notification;
use Payplug\PayplugWoocommerce\PayplugWoocommerceHelper;
use Payplug\Resource\IVerifiableAPIResource;

class PayplugIpnResponse {

	/**
	 * Extract useful metadata from PayPlug response.
	 *
	 * @param IVerifiableAPIResource $resource
	 *
	 * @return array
	 */
	public static function extract_transaction_metadata( $resource ) {
		return [
			'transaction_id' => sanitize_text_field( $resource->id ),
			'paid'           => (bool) $resource->is_paid,
			'amount'         => sanitize_text_field( $resource->amount ),
			'3ds'            => (bool) $resource->is_3ds,
			'live'           => (bool) $resource->is_live,
			'paid_at'        => sanitize_text_field( $resource->hosted_payment->paid_at ),
			'card_last4'     => sanitize_text_field( $resource->card->last4 ),
			'card_exp_month' => sanitize_text_field( $resource->card->exp_month ),
			'card_exp_year'  => sanitize_text_field( $resource->card->exp_year ),
			'card_brand'     => sanitize_text_field( $resource->card->brand ),
			'card_country'   => sanitize_text_field( $resource->card->country ),
		];
	}

	public function __construct() {
		add_action( 'woocommerce_api_paypluggateway', [ $this, 'handle_ipn_response' ] );
	}

	public function handle_ipn_response() {
		$input = file_get_contents( 'php://input' );

		try {
			$resource = Notification::treat( $input );
		} catch ( UnknownAPIResourceException $e ) {
			PayplugGateway::log( sprintf( 'Error while parsing IPN payload : %s', $e->getMessage() ), 'error' );
			exit;
		}

		if ( ! $this->validate_ipn( $resource ) ) {
			PayplugGateway::log( sprintf( 'Resource %s is not supported (Transaction %s).', wc_clean( $resource->object ), wc_clean( $resource->id ) ), 'error' );
			exit;
		}

		if ( ! method_exists( $this, sprintf( 'process_%s_resource', wc_clean( $resource->object ) ) ) ) {
			PayplugGateway::log( sprintf( 'No method found to process resource %s (Transaction %s).', wc_clean( $resource->object ), wc_clean( $resource->id ) ), 'error' );
			exit;
		}

		$order_id = wc_clean( $resource->metadata['order_id'] );
		$order    = wc_get_order( $order_id );
		if ( false === $order ) {
			PayplugGateway::log( sprintf( 'Coudn\'t find order #%s (Transaction %s).', $order_id, wc_clean( $resource->id ) ), 'error' );
			exit;
		}

		call_user_func( [ $this, sprintf( 'process_%s_resource', $resource->object ) ], $order, $resource );
		exit;
	}

	/**
	 * Validate IPN notification.
	 *
	 * @param IVerifiableAPIResource $resource
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

		$order_id = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();

		PayplugGateway::log( sprintf( 'Begin processing payment IPN %s for order #%s', $resource->id, $order_id ) );

		// Ignore paid orders
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			PayplugGateway::log( sprintf( 'Order #%s is already complete. Ignoring IPN.', $order_id ) );

			return;
		}

		// Ignore cancelled orders
		if ( $order->has_status( 'cancelled' ) ) {
			PayplugGateway::log( sprintf( 'Order #%s has been cancelled. Ignoring IPN', $order_id ) );

			return;
		}

		if ( $resource->is_paid ) {
			$payplug_metadata = self::extract_transaction_metadata( $resource );
			update_post_meta( $order_id, '_payplug_metadata', $payplug_metadata );

			PayplugWoocommerceHelper::is_pre_30() ? update_post_meta( $order_id, '_transaction_id', $resource->id ) : $order->set_transaction_id( $resource->id );
			$order->add_order_note( sprintf( __( 'PayPlug IPN OK | Transaction %s', 'payplug' ), $resource->id ) );
			$order->payment_complete( $resource->id );
			if ( PayplugWoocommerceHelper::is_pre_30() ) {
				$order->reduce_order_stock();
			}

			PayplugGateway::log( sprintf( 'Order #%s is already complete. Ignoring IPN.', $order_id ) );

			return;
		}

		if ( ! empty( $resource->failure ) ) {
			$order->update_status(
				'failed',
				sprintf( __( 'PayPlug IPN OK | Transaction %s failed : %s', 'payplug' ), $resource->id, wc_clean( $resource->failure->message ) )
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