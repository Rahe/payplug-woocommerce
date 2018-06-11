<?php


class AdminFiltersTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	// tests
	public function test_CheckAdminFilter() {
		// Gateways
		$this->assertNotFalse( has_filter('woocommerce_payment_gateways', [Payplug\PayplugWoocommerce\PayplugWoocommerce::get_instance(), 'register_payplug_gateway']) );

		// Plugin action link
		$this->assertNotFalse( has_filter('plugin_action_links_' . PAYPLUG_GATEWAY_PLUGIN_BASENAME, [Payplug\PayplugWoocommerce\PayplugWoocommerce::get_instance(), 'plugin_action_links']) );
	}

	public function test_action_links() {
		$woocmmerce = Payplug\PayplugWoocommerce\PayplugWoocommerce::get_instance();
		$this->assertNotEmpty( $woocmmerce->plugin_action_links([]) );
	}

}