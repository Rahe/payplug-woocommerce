<?php

class MiscCest {
	/**
	 * Admin pages as woocommerce deactivated
	 *
	 * @param AcceptanceTester $I
	 */
	public function testNoticeWoocommerce( AcceptanceTester $I ) {
		$I->wantToTest( 'I want to test there is an notice if plugin activated but no Woocommerce' );

		// As admin
		$I->loginAsAdmin();

		// Deactivate the plugin
		$I->amOnPluginsPage();
		$I->deactivatePlugin('woocommerce');

		// Notice to activate Woocommerce
		$I->see( 'PayPlug require WooCommerce to be activated to work.' );
	}
}