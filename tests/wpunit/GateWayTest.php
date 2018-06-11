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

		$this->assertWPError( $gateway->get_user_api_keys( '', 'ele' ), 'LOl' );
	}

	public function testGetUserApiKeysMissingPassWord() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->get_user_api_keys( 'ele', '' ) );
	}

	public function testGetUserApiKeysMissingPassWordAndEmail() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->get_user_api_keys( '', '' ) );
	}

	public function testGetUserApiKeysInvalidCredentials() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->get_user_api_keys( 'not', 'right' ) );
	}

	public function testLimitStringLengthDefault() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertEquals( 4, strlen( $gateway->limit_length( "TEST" ) ) );

		$this->assertEquals( 100, strlen( $gateway->limit_length( "A44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL" ) ) );

		$this->assertEquals( 100, strlen( $gateway->limit_length( "ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL" ) ) );
	}

	public function testLimitStringLength255() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertEquals( 4, strlen( $gateway->limit_length( "TEST", 255 ) ) );

		$this->assertEquals( 100, strlen( $gateway->limit_length( "A44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtL", 255 ) ) );

		$this->assertEquals( 255, strlen( $gateway->limit_length( "ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAAA", 255 ) ) );

		$this->assertEquals( 255, strlen( $gateway->limit_length( "ADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAAADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QvzH4pMkEuZ408CBIECZIb7L5zVZQEN1uonf4srt4UZ293EPrs6Acr0giEGInMSjGtkzI5TFpIJccuQjZcrchsi3OlFZNKXtLADSFDSFDSFSDFSFA44QAAAAA", 255 ) ) );

	}

	public function testValidateOrderAmountOutOfRange() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertWPError( $gateway->validate_order_amount( 50 ) );
		$this->assertWPError( $gateway->validate_order_amount( 2000050 ) );
	}

	public function testValidateOrderAmountInRange() {
		$gateway = new \Payplug\PayplugWoocommerce\Gateway\PayplugGateway();

		$this->assertEquals( 100, $gateway->validate_order_amount( 100 ) );
		$this->assertEquals( 2000000, $gateway->validate_order_amount( 2000000 ) );
		$this->assertEquals( 500, $gateway->validate_order_amount( 500 ) );
	}

}
