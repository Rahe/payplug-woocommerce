<?php
namespace Payplug\PayplugWoocommerce;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\PayplugWoocommerce\Admin\Notices;

class PayplugWoocommerce {

	/**
	 * @var PayplugWoocommerce
	 */
	private static $instance;

	/**
	 * PayPlug admin notices.
	 *
	 * @var Notices
	 */
	public $notices;

	/**
	 * Get the singleton instance.
	 *
	 * @return PayplugWoocommerce
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Singleton instance can't be cloned.
	 */
	private function __clone() {}

	/**
	 * Singleton instance can't be serialized.
	 */
	private function __wakeup() {}

	/**
	 * PayplugWoocommerce constructor.
	 */
	private function __construct() {

		// Bail early if WooCommerce is not activated
		if ( ! defined( 'WC_VERSION' ) ) {
			add_action( 'admin_notices', function () {
				?>
				<div id="message" class="updated notice">
					<p><?php _e( 'PayPlug require WooCommerce to be activated to work.', 'payplug' ); ?></p>
				</div>
				<?php
			} );
			return;
		}

		$this->notices = new Notices();

		add_action( 'woocommerce_payment_gateways', [ $this, 'register_payplug_gateway' ] );
		add_filter( 'plugin_action_links_' . PAYPLUG_GATEWAY_PLUGIN_BASENAME, [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Register PayPlug gateway.
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	public function register_payplug_gateway( $methods ) {
		$methods[] = __NAMESPACE__ . '\\Gateway\\PayplugGateway';
		return $methods;
	}

	/**
	 * Add additional action links.
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links = [] ) {
		$plugin_links = [
			'<a href="' . esc_url( PayplugWoocommerceHelper::get_setting_link() ) . '">' . esc_html__( 'Settings', 'payplug' ) . '</a>',
		];
		return array_merge( $plugin_links, $links );
	}
}