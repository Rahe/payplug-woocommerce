<?php

class PayPlugTest extends \Codeception\TestCase\WPTestCase {

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
	public function testConstants() {
		$this->assertTrue( defined( 'PAYPLUG_GATEWAY_VERSION' ) );
		$this->assertTrue( defined( 'PAYPLUG_GATEWAY_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'PAYPLUG_GATEWAY_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'PAYPLUG_GATEWAY_PLUGIN_BASENAME' ) );
	}

}