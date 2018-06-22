<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Authentication;
use Payplug\Core\HttpClient;
use Payplug\Exception\BadRequestException;
use Payplug\Payment;
use Payplug\Payplug;
use Payplug\PayplugWoocommerce\PayplugWoocommerceHelper;
use WC_Payment_Gateway_CC;
use WC_Payment_Tokens;

/**
 * PayPlug WooCommerce Gateway.
 *
 * @package Payplug\PayplugWoocommerce\Gateway
 */
class PayplugGateway extends WC_Payment_Gateway_CC {

	/**
	 * @var PayplugGatewayRequirements
	 */
	private $requirements;

	/**
	 * @var PayplugPermissions
	 */
	private $permissions;

	/**
	 * @var \WC_Logger
	 */
	protected static $log;

	/**
	 * @var bool
	 */
	protected static $log_enabled;

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'.
	 *     emergency|alert|critical|error|warning|notice|info|debug
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'payplug_gateway' ) );
		}
	}

	public function __construct() {
		$this->id                 = 'payplug';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = _x( 'PayPlug', 'Gateway method title', 'payplug' );
		$this->method_description = __( 'Let your customers pay with PayPlug', 'payplug' );
		$this->supports           = array(
			'products',
			'refunds',
			'tokenization',
		);

		$this->init_settings();
		$this->requirements = new PayplugGatewayRequirements( $this );
		if ( $this->user_logged_in() ) {
			$this->init_payplug();
		}
		$this->init_form_fields();

		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->mode           = 'yes' === $this->get_option( 'mode', 'no' ) ? 'live' : 'test';
		$this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
		$this->email          = $this->get_option( 'email' );
		$this->payment_method = $this->get_option( 'payment_method' );
		$this->oneclick       = 'yes' === $this->get_option( 'oneclick', 'no' );

		self::$log_enabled = $this->debug;

		if ( 'test' === $this->mode ) {
			$this->description .= ' ' . __( 'You are in TEST MODE. In test mode you can use the card 4242424242424242 with any valid expiration date and CVC.', 'payplug' );
			$this->description = trim( $this->description );
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ] );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Get payment icons.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icons = apply_filters( 'payplug_payment_icons', [
			'visa'       => '<img src="' . WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.svg" class="stripe-visa-icon stripe-icon" alt="Visa" />',
			'mastercard' => '<img src="' . WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.svg" class="stripe-mastercard-icon stripe-icon" alt="Mastercard" />',
		] );

		$icons_str = '';
		foreach ( $icons as $icon ) {
			$icons_str .= $icon;
		}

		return $icons_str;
	}

	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			return $this->requirements->satisfy_requirements() && ! empty( $this->get_api_key( $this->get_current_mode() ) );
		}

		return parent::is_available();
	}

	/**
	 * Load gateway settings.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}

	/**
	 * Register gateway settings.
	 */
	public function init_form_fields() {
		$fields = [
			'enabled'                 => [
				'title'       => __( 'Enable/Disable', 'payplug' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable PayPlug', 'payplug' ),
				'description' => __( 'This gateway can only be enable for shop using euro has currency.', 'payplug' ),
				'default'     => 'no',
			],
			'title'                   => [
				'title'       => __( 'Title', 'payplug' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'payplug' ),
				'default'     => __( 'PayPlug', 'payplug' ),
				'desc_tip'    => true,
			],
			'description'             => [
				'title'       => __( 'Description', 'payplug' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'payplug' ),
				'default'     => __( "Pay via PayPlug.", 'payplug' ),
			],
			'title_connexion'         => [
				'title' => __( 'Connexion', 'payplug' ),
				'type'  => 'title',
			],
			'email'                   => [
				'type'    => 'hidden',
				'default' => '',
			],
			'login'                   => [
				'type'    => 'login',
				'default' => '',
			],
			'payplug_test_key'        => [
				'type'    => 'hidden',
				'default' => '',
			],
			'payplug_live_key'        => [
				'type'    => 'hidden',
				'default' => '',
			],
			'title_testmode'          => [
				'title' => __( 'Mode', 'payplug' ),
				'type'  => 'title',
			],
			'mode'                    => [
				'title'       => '',
				'label'       => '',
				'type'        => 'yes_no',
				'yes'         => 'Live',
				'no'          => 'Test',
				'description' => __( 'Place the payment gateway in test mode using test API keys.', 'payplug' ),
				'default'     => 'no',
				'hide_label'  => true,
			],
			'title_settings'          => [
				'title' => __( 'Settings', 'payplug' ),
				'type'  => 'title',
			],
			'payment_method'          => [
				'title'       => __( 'Payment method', 'payplug' ),
				'type'        => 'radio',
				'description' => __( 'Choose which payment method will be used.', 'payplug' ),
				'default'     => 'redirect',
				'desc_tip'    => true,
				'options'     => array(
					'redirect' => __( 'Redirect', 'payplug' ),
					'embedded' => __( 'Embedded', 'payplug' ),
				),
			],
			'debug'                   => [
				'title'   => __( 'Debug', 'payplug' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Debug Mode', 'payplug' ),
				'default' => 'no',
			],
			'title_advanced_settings' => [
				'title'       => __( 'Advanced Settings', 'payplug' ),
				'description' => __( 'Those settings require a premium account. But you can try them in TEST mode.',
					'payplug' ),
				'type'        => 'title',
			],
			'oneclick'                => [
				'title'       => __( 'One Click Payment', 'payplug' ),
				'type'        => 'checkbox',
				'label'       => __( 'Activate', 'payplug' ),
				'description' => __( 'Offers your users to save their credit card for later.', 'payplug' ),
				'default'     => 'no',
				'desc_tip'    => true,
			],
		];

		// Disable One-Click checkbox if the user doesn't have the permission to use it.
		if ( $this->user_logged_in() && 'live' === $this->get_current_mode() ) {
			$fields['oneclick']['disabled'] = ! $this->permissions->has_permissions( PayplugPermissions::SAVE_CARD );
		}

		/**
		 * Filter PayPlug gateway settings.
		 *
		 * @param array $fields
		 */
		$fields            = apply_filters( 'payplug_gateway_settings', $fields );
		$this->form_fields = $fields;
	}

	/**
	 * Set global configuration for PayPlug instance.
	 */
	public function init_payplug() {
		$current_mode = $this->get_current_mode();
		$key          = $this->get_api_key( $current_mode );

		Payplug::setSecretKey( $key );
		HttpClient::addDefaultUserAgentProduct(
			'PayPlug-WooCommerce',
			PAYPLUG_GATEWAY_VERSION,
			sprintf( 'WooCommerce/%s', WC()->version )
		);

		// Register IPN handler
		new PayplugIpnResponse();
		$this->permissions = new PayplugPermissions( $this );
	}

	/**
	 * Embedded payment form scripts.
	 *
	 * Register scripts and additionnal data needed for the
	 * embedded payment form.
	 */
	public function scripts() {
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() && ! isset( $_GET['change_payment_method'] ) ) {
			return;
		}

		// If PayPlug is not enabled bail.
		if ( 'no' === $this->enabled ) {
			return;
		}

		// If keys are not set bail.
		if ( empty( $this->get_api_key( $this->mode ) ) ) {
			PayplugGateway::log( 'Keys are not set correctly.' );

			return;
		}

		if ( 'embedded' !== $this->payment_method ) {

			return;
		}

		wp_register_script( 'payplug', 'https://api.payplug.com/js/1.2/form.js', [], '1.2', true );
		wp_register_script( 'payplug-checkout', PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/js/payplug-checkout.js', [
			'jquery',
			'payplug'
		], time(), true );
		wp_localize_script( 'payplug-checkout', 'payplug_checkout_params', [
			'ajax_url' => \WC_AJAX::get_endpoint( 'payplug_create_order' ),
			'nonce'    => [
				'checkout' => wp_create_nonce( 'woocommerce-process_checkout' ),
			],
		] );
		wp_enqueue_script( 'payplug-checkout' );
	}

	/**
	 * Returns a users saved tokens for this gateway.
	 *
	 * Only payment tokens for the current mode will be returned
	 *
	 * @return array
	 */
	public function get_tokens() {
		if ( sizeof( $this->tokens ) > 0 ) {
			return $this->tokens;
		}

		if ( is_user_logged_in() ) {
			$user_tokens  = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->id );
			$this->tokens = array_filter( $user_tokens, function ( \WC_Payment_Token_CC $token ) {
				return $this->mode === $token->get_meta( 'mode', true );
			} );
		}

		return $this->tokens;
	}

	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$this->saved_payment_methods();
	}

	/**
	 * Handle admin display.
	 */
	public function admin_options() {
		wp_enqueue_style(
			'payplug-gateway-style',
			PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/css/app.css',
			[],
			PAYPLUG_GATEWAY_VERSION
		);

		$payplug_requirements = new PayplugGatewayRequirements( $this ); ?>

		<h2 class="title--logo"><?php esc_html( $this->get_method_title() ) ?></h2>
		<p><?php _e( sprintf( 'Version %s', PAYPLUG_GATEWAY_VERSION ) ); ?></p>
		<div class="payplug-requirements">
			<?php echo $payplug_requirements->curl_requirement(); ?>
			<?php echo $payplug_requirements->php_requirement(); ?>
			<?php echo $payplug_requirements->openssl_requirement(); ?>
			<?php echo $payplug_requirements->account_requirement(); ?>
			<?php echo $payplug_requirements->currency_requirement(); ?>
		</div>
		<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

		<?php if ( $this->user_logged_in() ) : ?>
			<table class="form-table">
				<?php $this->generate_settings_html( $this->get_form_fields() ); ?>
			</table>
		<?php else:
			$GLOBALS['hide_save_button'] = true; ?>
			<h3 class="wc-settings-sub-title"><?php _e( 'Connexion', 'payplug' ); ?></h3>
			<table class="form-table">
				<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="payplug_email"><?php _e( 'Email', 'payplug' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'Email', 'payplug' ); ?></span></legend>
							<input class="input-text regular-input" type="text" name="payplug_email" id="payplug_email"
							       value="" placeholder="<?php _e( 'your@email.com', 'payplug' ); ?>"/>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="payplug_password"><?php _e( 'Password', 'payplug' ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e( 'Password', 'payplug' ); ?></span>
							</legend>
							<input class="input-text regular-input" type="password" name="payplug_password"
							       id="payplug_password" value=""/>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<td class="forminp">
						<input class="button" type="submit" value="<?php _e( 'Login', 'payplug' ); ?>">
						<?php wp_nonce_field( 'payplug_user_login', '_loginaction' ); ?>
					</td>
				</tr>
				</tbody>
			</table>
		<?php
		endif;
	}

	/**
	 * Process admin options.
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$data = $this->get_post_data();

		// Handle logout process
		if (
			isset( $data['submit_logout'] )
			&& false !== check_admin_referer( 'payplug_user_logout', '_logoutaction' )
		) {

			$this->permissions->clear_permissions();

			$data                     = get_option( $this->get_option_key() );
			$data['payplug_test_key'] = '';
			$data['payplug_live_key'] = '';
			$data['enabled']          = 'no';
			$data['mode']             = 'no';
			update_option( $this->get_option_key(),
				apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $data ) );
			\WC_Admin_Settings::add_message( __( 'Successfully logged out.', 'payplug' ) );

			return true;
		}

		// Handle login process
		if (
			isset( $data['payplug_email'] )
			&& false !== check_admin_referer( 'payplug_user_login', '_loginaction' )
		) {
			$email    = $data['payplug_email'];
			$password = $data['payplug_password'];
			$response = $this->get_user_api_keys( $email, $password );
			if ( is_wp_error( $response ) ) {
				\WC_Admin_Settings::add_error( $response->get_error_message() );

				return false;
			}

			$this->init_form_fields();
			$fields = $this->get_form_fields();
			$data   = [];

			// Load existing values if the user is re-login.
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field['type'], [ 'title', 'login' ] ) ) {
					continue;
				}

				switch ( $key ) {
					case 'enabled':
						$val = 'yes';
						break;
					case 'mode':
						$val = 'no';
						break;
					case 'payplug_test_key':
						$val = esc_attr( $response['test'] );
						break;
					case 'payplug_live_key':
						$val = esc_attr( $response['live'] );
						break;
					case 'email':
						$val = esc_html( $email );
						break;
					default:
						$val = $this->get_option( $key );
				}

				$data[ $key ] = $val;
			}

			$this->set_post_data( $data );
			update_option( $this->get_option_key(),
				apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $data ) );
			\WC_Admin_Settings::add_message( __( 'Successfully logged in.', 'payplug' ) );

			return true;
		}

		// Don't let user without live key leave TEST mode.
		$mode_fieldkey     = $this->get_field_key( 'mode' );
		$live_key_fieldkey = $this->get_field_key( 'payplug_live_key' );
		if ( isset( $data[ $mode_fieldkey ] ) && '1' === $data[ $mode_fieldkey ] && empty( $data[ $live_key_fieldkey ] ) ) {
			$data[ $mode_fieldkey ] = '0';
			$this->set_post_data( $data );
			\WC_Admin_Settings::add_error( __( 'Your account does not currently support LIVE mode, it need to be approved first. If your account has already been approved, please log out and log back in.', 'payplug' ) );
		}

		// Check user permissions before activating one-click feature.
		$oneclick_fieldkey = $this->get_field_key( 'oneclick' );
		if ( '1' === $data[ $oneclick_fieldkey ] && false === $this->permissions->has_permissions( PayplugPermissions::SAVE_CARD ) ) {
			$data[ $oneclick_fieldkey ] = '0';
			\WC_Admin_Settings::add_error( __( 'Only Premium accounts can use one click in LIVE mode.', 'payplug' ) );
		}

		parent::process_admin_options();
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process_payment( $order_id ) {

		PayplugGateway::log( sprintf( 'Processing payment for order #%s', $order_id ) );

		$order       = wc_get_order( $order_id );
		$customer_id = PayplugWoocommerceHelper::is_pre_30() ? $order->customer_user : $order->get_customer_id();
		$amount      = (int) PayplugWoocommerceHelper::get_payplug_amount( $order->get_total() );
		$amount      = $this->validate_order_amount( $amount );
		if ( is_wp_error( $amount ) ) {
			PayplugGateway::log( sprintf( 'Invalid amount %s for the order.', $amount ), 'error' );
			throw new \Exception( $amount->get_error_message() );
		}

		$payment_token_id = ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] )
			? wc_clean( $_POST[ 'wc-' . $this->id . '-payment-token' ] )
			: false;

		if ( $payment_token_id ) {
			PayplugGateway::log( sprintf( 'Payment token found.', $amount ) );

			return $this->process_payment_with_token( $order, $amount, $customer_id, $payment_token_id );
		}

		return $this->process_standard_payment( $order, $amount, $customer_id );
	}

	/**
	 * @param \WC_Order $order
	 * @param int $amount
	 * @param int $customer_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process_standard_payment( $order, $amount, $customer_id ) {

		$order_id         = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();
		$customer_details = $this->prepare_customer_data( $order );

		try {
			$payment_data = [
				'amount'           => $amount,
				'currency'         => get_woocommerce_currency(),
				'allow_save_card'  => $this->oneclick && $this->permissions->has_permissions( PayplugPermissions::SAVE_CARD ) && (int) $customer_id > 0,
				'customer'         => [
					'first_name' => $this->limit_length( $customer_details['first_name'] ),
					'last_name'  => $this->limit_length( $customer_details['last_name'] ),
					'email'      => $this->limit_length( $customer_details['email'], 255 ),
					'address1'   => $this->limit_length( $customer_details['address1'], 255 ),
					'postcode'   => $this->limit_length( $customer_details['postcode'], 16 ),
					'city'       => $this->limit_length( $customer_details['city'] ),
					'country'    => $this->limit_length( $customer_details['country'], 2 ),
				],
				'hosted_payment'   => [
					'return_url' => esc_url_raw( $order->get_checkout_order_received_url() ),
					'cancel_url' => esc_url_raw( $order->get_cancel_order_url_raw() ),
				],
				'notification_url' => esc_url_raw( WC()->api_request_url( 'PayplugGateway' ) ),
				'metadata'         => [
					'order_id'    => $order_id,
					'customer_id' => ( (int) $customer_id > 0 ) ? $customer_id : 'guest',
					'domain'      => $this->limit_length( esc_url_raw( home_url() ), 500 ),
				],
			];

			/**
			 * Filter the payment data before it's used
			 *
			 * @param array $payment_data
			 * @param int $order_id
			 * @param array $customer_details
			 */
			$payment_data = apply_filters( 'payplug_gateway_payment_data', $payment_data, $order_id, $customer_details );
			$payment      = Payment::create( $payment_data );

			$payplug_metadata = PayplugWoocommerceHelper::extract_transaction_metadata( $payment );
			update_post_meta( $order_id, '_payplug_metadata', $payplug_metadata );

			PayplugGateway::log( sprintf( 'Payment creation complete for order #%s', $order_id ) );

			return [
				'result'   => 'success',
				'redirect' => $payment->hosted_payment->payment_url,
				'cancel'   => $payment->hosted_payment->cancel_url,
			];
		} catch ( \Exception $e ) {
			PayplugGateway::log( sprintf( 'Error while processing payment for order #%s : %s', $order_id, wc_print_r( $e->getErrorObject() ) ), 'error' );
			throw new \Exception( __( 'Payment processing failed. Please retry.', 'payplug' ) );
		}
	}

	/**
	 * @param \WC_Order $order
	 * @param int $amount
	 * @param int $customer_id
	 * @param string $token_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process_payment_with_token( $order, $amount, $customer_id, $token_id ) {

		$order_id         = PayplugWoocommerceHelper::is_pre_30() ? $order->id : $order->get_id();
		$customer_details = $this->prepare_customer_data( $order );
		$payment_token    = WC_Payment_Tokens::get( $token_id );
		if ( ! $payment_token || (int) $customer_id !== (int) $payment_token->get_user_id() ) {
			PayplugGateway::log( 'Could not find the payment token or the payment doesn\'t belong to the current user.', 'error' );
			throw new \Exception( __( 'Invalid payment method.', 'payplug' ) );
		}

		try {
			$payment_data = [
				'amount'           => $amount,
				'currency'         => get_woocommerce_currency(),
				'payment_method'   => $payment_token->get_token(),
				'customer'         => [
					'first_name' => $this->limit_length( $customer_details['first_name'] ),
					'last_name'  => $this->limit_length( $customer_details['last_name'] ),
					'email'      => $this->limit_length( $customer_details['email'], 255 ),
					'address1'   => $this->limit_length( $customer_details['address1'], 255 ),
					'postcode'   => $this->limit_length( $customer_details['postcode'], 16 ),
					'city'       => $this->limit_length( $customer_details['city'] ),
					'country'    => $this->limit_length( $customer_details['country'], 2 ),
				],
				'notification_url' => esc_url_raw( WC()->api_request_url( 'PayplugGateway' ) ),
				'metadata'         => [
					'order_id'    => $order_id,
					'customer_id' => ( (int) $customer_id > 0 ) ? $customer_id : 'guest',
					'domain'      => $this->limit_length( esc_url_raw( home_url() ), 500 ),
				],
			];

			/**
			 * Filter the payment data before it's used
			 *
			 * @param array $payment_data
			 * @param int $order_id
			 * @param array $customer_details
			 */
			$payment_data = apply_filters( 'payplug_gateway_payment_data', $payment_data, $order_id, $customer_details );
			$payment      = Payment::create( $payment_data );

			PayplugGateway::log( sprintf( 'Payment process complete for order #%s', $order_id ) );

			return [
				'result'   => 'success',
				'redirect' => $order->get_checkout_order_received_url(),
			];
		} catch ( \Exception $e ) {
			PayplugGateway::log( sprintf( 'Error while processing order #%s : %s', $order_id, wc_print_r( $e->getErrorObject() ) ), 'error' );
			throw new \Exception( __( 'Payment processing failed. Please retry.', 'payplug' ) );
		}
	}

	/**
	 * Process refund for an order paid with PayPlug gateway.
	 *
	 * @param int $order_id
	 * @param null $amount
	 * @param string $reason
	 *
	 * @return bool|\WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		PayplugGateway::log( sprintf( 'Processing refund for order #%s', $order_id ) );

		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_transaction_id() ) {
			PayplugGateway::log( sprintf( 'The order #%s was not found or does not have PayPlug transaction ID associated with it.', $order_id ), 'error' );

			return new \WP_Error( 'process_refund_error', __( 'No PayPlug transaction found for the order. No refund was made.', 'payplug' ) );
		}

		$customer_id = PayplugWoocommerceHelper::is_pre_30() ? $order->customer_user : $order->get_customer_id();

		$data = [
			'metadata' => [
				'order_id'    => $order_id,
				'customer_id' => ( (int) $customer_id > 0 ) ? $customer_id : 'guest',
			]
		];

		if ( ! is_null( $amount ) ) {
			$data['amount'] = PayplugWoocommerceHelper::get_payplug_amount( $amount );
		}

		if ( ! empty( $reason ) ) {
			$data['metadata']['reason'] = $reason;
		}

		try {
			$payment = Payment::retrieve( $order->get_transaction_id() );
			$refund  = $payment->refund( $data );

			$refund_meta_key = sprintf( '_pr_%s', wc_clean( $refund->id ) );
			if ( PayplugWoocommerceHelper::is_pre_30() ) {
				update_post_meta( $order_id, $refund_meta_key, $refund->id );
			} else {
				$order->add_meta_data( $refund_meta_key, $refund->id, true );
				$order->save();
			}

			$note = sprintf( __( 'Refund %s : Refunded %s', 'payplug' ), wc_clean( $refund->id ), wc_price( ( (int) $refund->amount ) / 100 ) );
			if ( ! empty( $refund->metadata['reason'] ) ) {
				$note .= sprintf( ' (%s)', esc_html( $refund->metadata['reason'] ) );
			}
			$order->add_order_note( $note );

			PayplugGateway::log( 'Refund process complete for the order.' );

			return true;
		} catch ( \Exception $e ) {
			PayplugGateway::log( sprintf( 'Refund request error from PayPlug API : %s', wc_clean( $e->getMessage() ) ), 'error' );

			return new \WP_Error( 'process_refund_error', __( 'Refund process failed. Please retry.', 'payplug' ) );
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public function prepare_customer_data( $order ) {
		return [
			'first_name' => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_first_name : $order->get_billing_first_name(),
			'last_name'  => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_last_name : $order->get_billing_last_name(),
			'email'      => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_email : $order->get_billing_email(),
			'address1'   => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_address_1 : $order->get_billing_address_1(),
			'address2'   => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_address_2 : $order->get_billing_address_2(),
			'postcode'   => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_postcode : $order->get_billing_postcode(),
			'city'       => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_city : $order->get_billing_city(),
			'country'    => PayplugWoocommerceHelper::is_pre_30() ? $order->billing_country : $order->get_billing_country(),
		];
	}

	/**
	 * Check the order amount to ensure it's on the allowed range.
	 *
	 * @param int $amount
	 *
	 * @return int|\WP_Error
	 */
	public function validate_order_amount( $amount ) {
		if (
			$amount < PayplugWoocommerceHelper::get_minimum_amount()
			|| $amount > PayplugWoocommerceHelper::get_maximum_amount()
		) {
			return new \WP_Error(
				'invalid order amount',
				sprintf( __( 'Total amount of %s is not in the allowed range.', 'payplug' ), ( $amount / 100 ) )
			);
		}

		return $amount;
	}

	/**
	 * Limit string length.
	 *
	 * @param string $value
	 * @param int $maxlength
	 *
	 * @return string
	 */
	public function limit_length( $value, $maxlength = 100 ) {
		return ( strlen( $value ) > $maxlength ) ? substr( $value, 0, $maxlength ) : $value;
	}

	/**
	 * Get user's keys.
	 *
	 * @param string $email
	 * @param string $password
	 *
	 * @return array|\WP_Error
	 */
	public function get_user_api_keys( $email, $password ) {
		if ( empty( $email ) || empty( $password ) ) {
			return new \WP_Error( 'missing_login_data', __( 'Please fill all login fields', 'payplug' ) );
		}

		try {
			$response = Authentication::getKeysByLogin( $email, $password );
			if ( empty( $response ) || ! isset( $response['httpResponse'] ) ) {
				return new \WP_Error( 'invalid_credentials', __( 'Your credentials are invalid.', 'payplug' ) );
			}

			return $response['httpResponse']['secret_keys'];
		} catch ( BadRequestException $e ) {
			return new \WP_Error( 'invalid_credentials', __( 'Your credentials are invalid.', 'payplug' ) );
		}
	}

	/**
	 * Generate Hidden HTML.
	 *
	 * @param string $key
	 * @param array $data
	 *
	 * @return string
	 */
	public function generate_hidden_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<input
				type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>"
				id="<?php echo esc_attr( $field_key ); ?>"
				value="<?php echo esc_attr( $this->get_option( $key ) ); ?>"/>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Yes/No Input HTML.
	 *
	 * @param string $key
	 * @param array $data
	 *
	 * @return string
	 */
	public function generate_yes_no_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'no'                => 'No',
			'yes'               => 'Yes',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => [],
			'hide_label'        => false,
		);

		$data    = wp_parse_args( $data, $defaults );
		$checked = 'yes' === $this->get_option( $key ) ? '1' : '0';

		ob_start();
		?>
		<tr valign="top">
			<?php if ( ! $data['hide_label'] ) : ?>
				<th scope="row" class="titledesc">
					<?php echo $this->get_tooltip_html( $data ); ?>
					<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				</th>
			<?php endif; ?>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<div class="radio--custom">
						<input class="radio radio-yes <?php echo esc_attr( $data['class'] ); ?>"
						       type="radio"
						       name="<?php echo esc_attr( $field_key ); ?>"
						       id="<?php echo esc_attr( $field_key ); ?>-yes"
						       value="1"
							<?php checked( '1', $checked ); ?>
							<?php disabled( $data['disabled'], true ); ?>
							<?php echo $this->get_custom_attribute_html( $data ); ?>>
						<label for="<?php echo esc_attr( $field_key ); ?>-yes"><?php echo esc_html( $data['yes'] ); ?></label>
					</div>
					<div class="radio--custom">
						<input class="radio radio-no <?php echo esc_attr( $data['class'] ); ?>"
						       type="radio"
						       name="<?php echo esc_attr( $field_key ); ?>"
						       id="<?php echo esc_attr( $field_key ); ?>-no"
						       value="0"
							<?php checked( '0', $checked ); ?>
							<?php disabled( $data['disabled'], true ); ?>
							<?php echo $this->get_custom_attribute_html( $data ); ?>>
						<label for="<?php echo esc_attr( $field_key ); ?>-no"><?php echo esc_html( $data['no'] ); ?></label>
					</div>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Radio Input HTML.
	 *
	 * @param  string $key
	 * @param  array $data
	 *
	 * @return string
	 */
	public function generate_radio_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => [],
			'options'           => [],
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo $this->get_tooltip_html( $data ); ?>
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<?php foreach ( $data['options'] as $option_key => $option_value ) : ?>
						<input class="radio <?php echo esc_attr( $data['class'] ); ?>"
						       type="radio"
						       name="<?php echo esc_attr( $field_key ); ?>"
						       id="<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $option_key ); ?>"
						       value="<?php echo esc_attr( $option_key ); ?>"
							<?php checked( $option_key, $this->get_option( $key ) ); ?>
							<?php disabled( $data['disabled'], true ); ?>
							<?php echo $this->get_custom_attribute_html( $data ); ?>>
						<label for="<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $option_value ); ?></label>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Login HTML.
	 *
	 * @param string $key
	 * @param array $data
	 *
	 * @return string
	 */
	public function generate_login_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = [];

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<td class="forminp">
				<p><?php echo $this->get_option( 'email' ); ?></p>
				<p>
					<input type="submit" name="submit_logout" value="<?php _e( 'Logout', 'payplug' ); ?>">
					<?php wp_nonce_field( 'payplug_user_logout', '_logoutaction' ); ?>
					|
					<a href="https://portal.payplug.com"
					   target="_blank"><?php _e( 'Go to my dashboard', 'payplug' ); ?></a>
				</p>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate Radio Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param  string $key
	 * @param  string|null $value Posted Value
	 *
	 * @return string
	 */
	public function validate_radio_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wc_clean( stripslashes( $value ) );
	}

	/**
	 * Validate Yes/No Field.
	 *
	 * @param  string $key
	 * @param  string $value Posted Value
	 *
	 * @return string
	 */
	public function validate_yes_no_field( $key, $value ) {
		return ( '1' === (string) $value ) ? 'yes' : 'no';
	}

	/**
	 * Get PayPlug gateway mode.
	 *
	 * @return string
	 */
	public function get_current_mode() {
		return ( 'yes' === $this->get_option( 'mode' ) ) ? 'live' : 'test';
	}

	/**
	 * Get user API key.
	 *
	 * @param string $mode
	 *
	 * @return string
	 */
	public function get_api_key( $mode = 'test' ) {

		switch ( $mode ) {
			case 'test':
				$key = $this->get_option( 'payplug_test_key' );
				break;
			case 'live':
				$key = $this->get_option( 'payplug_live_key' );
				break;
			default:
				$key = '';
				break;
		}

		return $key;
	}

	/**
	 * Check if user is logged in and we have an API key for TEST mode.
	 *
	 * @return bool
	 */
	public function user_logged_in() {
		return ! empty( $this->get_option( 'payplug_test_key' ) );
	}
}