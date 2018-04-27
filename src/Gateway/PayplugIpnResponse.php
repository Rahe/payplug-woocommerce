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
use WC_Payment_Token_CC;

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

		PayplugGateway::log( sprintf( 'Order #%s : Begin processing payment IPN %s', $order_id, $resource->id ) );

		// Ignore paid orders
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			PayplugGateway::log( sprintf( 'Order #%s : Order is already complete. Ignoring IPN.', $order_id ) );

			return;
		}

		// Ignore cancelled orders
		if ( $order->has_status( 'cancelled' ) ) {
			PayplugGateway::log( sprintf( 'Order #%s : Order has been cancelled. Ignoring IPN', $order_id ) );

			return;
		}

		if ( $resource->is_paid ) {

			$this->maybe_save_card( $resource );

			$payplug_metadata = self::extract_transaction_metadata( $resource );
			update_post_meta( $order_id, '_payplug_metadata', $payplug_metadata );

			PayplugWoocommerceHelper::is_pre_30() ? update_post_meta( $order_id, '_transaction_id', wc_clean( $resource->id ) ) : $order->set_transaction_id( wc_clean( $resource->id ) );
			$order->add_order_note( sprintf( __( 'PayPlug IPN OK | Transaction %s', 'payplug' ), wc_clean( $resource->id ) ) );
			$order->payment_complete( wc_clean( $resource->id ) );
			if ( PayplugWoocommerceHelper::is_pre_30() ) {
				$order->reduce_order_stock();
			}

			PayplugGateway::log( sprintf( 'Order #%s : Payment IPN %s processing completed.', $order_id, $resource->id ) );

			return;
		}

		if ( ! empty( $resource->failure ) ) {
			$order->update_status(
				'failed',
				sprintf( __( 'PayPlug IPN OK | Transaction %s failed : %s', 'payplug' ), $resource->id, wc_clean( $resource->failure->message ) )
			);

			PayplugGateway::log( sprintf( 'Order #%s : Payment IPN %s processing completed.', $order_id, $resource->id ) );

			return;
		}
	}

	/**
	 * Process refund notification.
	 *
	 * @param \WC_Order $order
	 * @param $resource
	 */
	public function process_refund_resource( $order, $resource ) {

	}

	/**
	 * Save card from the transaction.
	 *
	 * @param IVerifiableAPIResource $resource
	 *
	 * @return bool
	 */
	protected function maybe_save_card( $resource ) {

		if ( ! $resource->save_card || ! isset( $resource->card ) ) {
			return false;
		}

		if ( ! isset( $resource->metadata['customer_id'] ) ) {
			return false;
		}

		$customer = get_user_by( 'id', $resource->metadata['customer_id'] );
		if ( ! $customer || 0 === (int) $customer->ID ) {
			return false;
		}

		PayplugGateway::log( sprintf( 'Saving card from transaction %s for customer %s', wc_clean( $resource->id ), $customer->ID ) );

		$token = new WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( $customer->ID );
		$token->add_meta_data( 'mode', $resource->is_live ? 'live' : 'test', true );
		$token->save();

		return true;
	}
}