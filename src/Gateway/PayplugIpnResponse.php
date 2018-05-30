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

		call_user_func( [ $this, sprintf( 'process_%s_resource', $resource->object ) ], $resource );
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
	 * @param $resource
	 *
	 * @return void
	 * @throws \WC_Data_Exception
	 */
	public function process_payment_resource( $resource ) {
		$order_id = wc_clean( $resource->metadata['order_id'] );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			PayplugGateway::log( sprintf( 'Coudn\'t find order #%s (Transaction %s).', $order_id, wc_clean( $resource->id ) ), 'error' );

			return;
		}

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
	 * @param $resource
	 */
	public function process_refund_resource( $resource ) {
		$transaction_id = wc_clean( $resource->payment_id );
		$order          = $this->get_order_from_transaction_id( $transaction_id );
		if ( ! $order ) {
			PayplugGateway::log( sprintf( 'Coudn\'t find order for transaction %s (Refund %s).', wc_clean( $resource->payment_id ), wc_clean( $resource->id ) ), 'error' );

			return;
		}
		$order_id = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();

		PayplugGateway::log( sprintf( 'Order #%s : Begin processing refund IPN %s', $order_id, $resource->id ) );

		$refund_exist = $this->refund_exist_for_order( $order_id, $resource->id );
		if ( $refund_exist ) {
			PayplugGateway::log( sprintf( 'Order %s : Refund has already been processed. Ignoring IPN.', $order_id ) );

			return;
		}

		$refund = wc_create_refund( [
			'amount'         => ( (int) $resource->amount ) / 100,
			'reason'         => isset( $resource->metadata['reason'] ) ? $resource->metadata['reason'] : null,
			'order_id'       => (int) $order_id,
			'refund_id'      => 0,
			'refund_payment' => false,
		] );
		if ( is_wp_error( $refund ) ) {
			PayplugGateway::log( $refund->get_error_message() );
		}

		$refund_meta_key = sprintf( '_pr_%s', wc_clean( $resource->id ) );
		if ( PayplugWoocommerceHelper::is_pre_30() ) {
			update_post_meta( $order_id, $refund_meta_key, $resource->id );
		} else {
			$order->add_meta_data( $refund_meta_key, $resource->id, true );
			$order->save();
		}

		$note = sprintf( __( 'Refund %s : Refunded %s', 'payplug' ), wc_clean( $resource->id ), wc_price( ( (int) $resource->amount ) / 100 ) );
		if ( ! empty( $resource->metadata['reason'] ) ) {
			$note .= sprintf( ' (%s)', esc_html( $resource->metadata['reason'] ) );
		}
		$order->add_order_note( $note );

		PayplugGateway::log( sprintf( 'Order #%s : Refund IPN %s processing completed.', $order_id, $resource->id ) );
	}

	/**
	 * @param $transaction_id
	 *
	 * @return bool|\WC_Order|\WC_Refund
	 */
	protected function get_order_from_transaction_id( $transaction_id ) {
		global $wpdb;

		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_transaction_id'
				AND meta_value = %s
				",
				$transaction_id
			)
		);

		return ! is_null( $order_id ) ? wc_get_order( $order_id ) : false;
	}

	/**
	 * @param string $refund_id
	 *
	 * @return bool
	 */
	protected function refund_exist_for_order( $order_id, $refund_id ) {
		global $wpdb;

		$sql = "
			SELECT p.ID
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm
				ON p.ID = pm.post_id
			WHERE 1=1
			AND p.post_type = %s
			AND p.ID = %d
			AND pm.meta_key LIKE '_pr_" . esc_sql( $refund_id ) . "'
			AND pm.meta_value = %s
			LIMIT 1
		";

		$results = $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				'shop_order',
				(int) $order_id,
				$refund_id,
				$refund_id
			)
		);

		error_log( $wpdb->last_query );

		return ! empty( $results ) ? true : false;
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