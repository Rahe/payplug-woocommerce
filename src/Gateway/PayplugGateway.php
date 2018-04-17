<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Authentication;
use Payplug\Exception\BadRequestException;
use Payplug\Payplug;
use WC_Payment_Gateway;

/**
 * PayPlug WooCommerce Gateway.
 *
 * @package Payplug\PayplugWoocommerce\Gateway
 */
class PayplugGateway extends WC_Payment_Gateway {

	private $permissions;

	public function __construct() {
		$this->id                 = 'payplug';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = _x( 'PayPlug', 'Gateway method title', 'payplug' );
		$this->method_description = __( 'Let your customers pay with PayPlug', 'payplug' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_settings();
		$this->init_form_fields();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
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
		$this->form_fields = [
			'enabled'                 => [
				'title'       => __( 'Enable/Disable', 'payplug' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable PayPlug', 'payplug' ),
				'description' => __( 'This gateway can only be enable for shop using euro has currency.', 'payplug' ),
				'default'     => 'yes',
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
				'description' => __( 'Choose which payment method will be used.', 'payplug' ),
				'default'     => 'no',
				'desc_tip'    => true,
			],
		];
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

	public function process_admin_options() {
		$data = $this->get_post_data();

		// Handle logout process
		if (
			isset( $data['submit_logout'] )
			&& false !== check_admin_referer( 'payplug_user_logout', '_logoutaction' )
		) {
			$data                     = get_option( $this->get_option_key() );
			$data['payplug_test_key'] = '';
			$data['payplug_live_key'] = '';
			$data['mode']             = 'no';
			update_option( $this->get_option_key(),
				apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $data ) );
			\WC_Admin_Settings::add_message( __( 'Successfully logged out.', 'payplug' ) );

			return;
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

				return;
			}

			$fields = $this->get_form_fields();
			$data   = [];

			// Load existing values if the user is re-login.
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field['type'], [ 'title', 'login' ] ) ) {
					continue;
				}

				switch ( $key ) {
					case 'payplug_test_key':
						$val = $response['test'];
						break;
					case 'payplug_live_key':
						$val = $response['live'];
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

			return;
		}

		// Don't let user without live key leave TEST mode.
		$testmode_fieldkey = $this->get_field_key( 'mode' );
		$live_key_fieldkey = $this->get_field_key( 'payplug_live_key' );
		if ( isset( $data[ $testmode_fieldkey ] ) && empty( $data[ $live_key_fieldkey ] ) ) {
			$data[ $testmode_fieldkey ] = '0';
			$this->set_post_data( $data );
			\WC_Admin_Settings::add_error( __( 'Your account does not currently support LIVE mode, it need to be approved first. If your account has already been approved, please log out and log back in.', 'payplug' ) );
		}

		parent::process_admin_options();
	}

	public function process_payment( $order_id ) {
		return parent::process_payment( $order_id ); // TODO: Change the autogenerated stub
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return parent::process_refund( $order_id, $amount, $reason ); // TODO: Change the autogenerated stub
	}

	/**
	 * Get a Payplug API instance
	 *
	 * @return Payplug
	 */
	public function get_payplug() {
		$current_mode = $this->get_current_mode();
		$key          = $this->get_api_key( $current_mode );

		return new Payplug( $key );
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
			<th scope="row" class="titledesc">
				<?php echo $this->get_tooltip_html( $data ); ?>
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
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
					<a href="https://portal.payplug.com"><?php _e( 'Go to my dashboard' ); ?></a>
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