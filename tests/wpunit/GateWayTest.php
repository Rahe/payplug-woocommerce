<?php

class GateWayTest extends \Codeception\TestCase\WPTestCase {

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
	public function testGetUserApiKeysMissingEmail() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->retrieve_user_api_keys( '', 'ele' ) );
	}

	public function testGetUserApiKeysMissingPassWord() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->retrieve_user_api_keys( 'ele', '' ) );
	}

	public function testGetUserApiKeysMissingPassWordAndEmail() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->retrieve_user_api_keys( '', '' ) );
	}

	public function testGetUserApiKeysInvalidCredentials() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->retrieve_user_api_keys( 'not', 'right' ) );
	}

	public function testLimitStringLengthDefault() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		// Under string limit, should not change.
		$this->assertEquals( 4, strlen( $gateway->limit_length( 'TEST' ) ) );

		// At string limit, should not change.
		$this->assertEquals( 100, strlen( $gateway->limit_length( 'A44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL' ) ) );

		// Above string limit, should equal to default limit.
		$this->assertEquals( 100, strlen( $gateway->limit_length( 'ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL' ) ) );
	}

	public function testLimitStringLength255() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		// Under string limit, should not change.
		$this->assertEquals( 4, strlen( $gateway->limit_length( 'TEST', 255 ) ) );

		// Under string limit, should not change.
		$this->assertEquals( 100, strlen( $gateway->limit_length( 'A44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL', 255 ) ) );

		// At string limit, should not change.
		$this->assertEquals( 255, strlen( $gateway->limit_length( 'ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAAA', 255 ) ) );

		// Above string limit, should equal to passed limit.
		$this->assertEquals( 255, strlen( $gateway->limit_length( 'ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAAADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAA', 255 ) ) );

	}

	public function testValidateOrderAmountOutOfRange() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		// Under the limit.
		$this->assertWPError( $gateway->validate_order_amount( 50 ) );

		// Above the limit.
		$this->assertWPError( $gateway->validate_order_amount( 2000050 ) );
	}

	public function testValidateOrderAmountInRange() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		// At valid minimum amount.
		$this->assertEquals( 99, $gateway->validate_order_amount( 99 ) );

		// At valid maximum amount.
		$this->assertEquals( 2000000, $gateway->validate_order_amount( 2000000 ) );

		// Into limits valid limits.
		$this->assertEquals( 500, $gateway->validate_order_amount( 500 ) );
	}

}
