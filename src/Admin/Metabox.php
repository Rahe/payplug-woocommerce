<?php

namespace Payplug\PayplugWoocommerce\Admin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\PayplugWoocommerce\PayplugWoocommerceHelper;
use WP_Post;

/**
 * PayPlug metadata metabox.
 *
 * @package Payplug\PayplugWoocommerce\Admin
 */
class Metabox {

	/**
	 * Metabox constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'register_payplug_metabox' ], 100 );
	}

	/**
	 * Register a custom metabox to display metadata for the current order.
	 *
	 * This metabox is only register if the current order has been paid via PayPlug.
	 */
	public function register_payplug_metabox() {
		global $post;
		$screen = get_current_screen();
		if ( is_null( $screen ) || 'shop_order' !== $screen->post_type ) {
			return;
		}

		$order = wc_get_order( $post );
		if ( false === $order ) {
			return;
		}

		$payment_method = PayplugWoocommerceHelper::is_pre_30() ? $order->payment_method : $order->get_payment_method();
		if ( 'payplug' !== $payment_method ) {
			return;
		}

		add_meta_box(
			'payplug-transaction-details',
			__( 'PayPlug payment details', 'payplug' ),
			[ $this, 'render' ],
			'shop_order',
			'side'
		);
	}

	/**
	 * Render the metabox content.
	 *
	 * @param WP_Post $post
	 */
	public function render( $post ) {
		$order = wc_get_order( $post );
		if ( false === $order ) {
			return;
		}

		$order_id         = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();
		$payplug_metadata = get_post_meta( $order_id, '_payplug_metadata', true );
		$date_format      = get_option( 'date_format', 'j F Y' );
		$time_format      = get_option( 'time_format', 'G \h i \m\i\n' );
		if ( empty( $payplug_metadata ) ) : ?>
            <p><?php _e( 'No metadata available for the current order.', 'payplug' ); ?></p>
		<?php else : ?>
            <ul>
                <li><span><?php _e( 'Transaction ID', 'payplug' ); ?>
                        :</span> <?php echo esc_html( $payplug_metadata['transaction_id'] ); ?></li>
                <li><span><?php _e( 'Transaction state', 'payplug' ); ?>
                        :</span> <?php true === $payplug_metadata['paid'] ? _e( 'Paid', 'payplug' ) : _e( 'Pending', 'payplug' ); ?>
                </li>
                <li><span><?php _e( 'Amount', 'payplug' ); ?>
                        :</span> <?php echo wc_price( (int) $payplug_metadata['amount'] / 100 ); ?></li>
                <li><span><?php _e( 'Paid at', 'payplug' ); ?>
                        :</span> <?php echo esc_html( date_i18n( sprintf( '%s %s', $date_format, $time_format ), $payplug_metadata['paid_at'] ) ); ?>
                </li>
                <li><span><?php _e( 'Card', 'payplug' ); ?>
                        :</span> <?php echo esc_html( sprintf( '%s (%s)', $payplug_metadata['card_brand'], $payplug_metadata['card_country'] ) ); ?>
                </li>
                <li><span><?php _e( 'Last four digits', 'payplug' ); ?>
                        :</span> <?php echo esc_html( sprintf( '**** **** **** %s', $payplug_metadata['card_last4'] ) ); ?>
                </li>
                <li><span><?php _e( '3-D Secure', 'payplug' ); ?>
                        :</span> <?php true === $payplug_metadata['3ds'] ? _e( 'Yes', 'payplug' ) : _e( 'No', 'payplug' ); ?>
                </li>
                <li><span><?php _e( 'Expiration date', 'payplug' ); ?>
                        :</span> <?php echo esc_html( sprintf( '%s/%s', zeroise( $payplug_metadata['card_exp_month'], 2 ), zeroise( $payplug_metadata['card_exp_year'], 2 ) ) ); ?>
                </li>
                <li><span><?php _e( 'Mode', 'payplug' ); ?>
                        :</span> <?php echo true === $payplug_metadata['live'] ? _e( 'Live', 'payplug' ) : _e( 'Test', 'payplug' ); ?>
                </li>
            </ul>
		<?php endif;
	}
}