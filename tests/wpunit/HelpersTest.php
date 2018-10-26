<?php

class HelpersTest extends \Codeception\TestCase\WPTestCase {

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
	public function testGetSettingsLinkWoocommerce25() {
		WC()->version = '2.5';
		$expected     = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( 'PayplugGateway' ) );
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_setting_link(), $expected );
	}

	public function testGetSettingsLinkWoocommerce26AndAbove() {
		WC()->version = '2.6';
		$expected     = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_setting_link(), $expected );
	}

	public function testMinimumPaymentAmount() {
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_minimum_amount(), 100 );

	}

	public function testMaximumPaymentAmount() {
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_maximum_amount(), 2000000 );

	}

	public function testAmountToCentsConversion() {
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_payplug_amount( 25.5 ), 2550 );
	}

	public function testAmountToCentsConversion5Decimals() {
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_payplug_amount( 25.53454353 ), 2553 );
	}


	public function testAmountToCentsConversionNull() {
		$this->assertEquals( Payplug\PayplugWoocommerce\PayplugWoocommerceHelper::get_payplug_amount( null ), null );
	}
}