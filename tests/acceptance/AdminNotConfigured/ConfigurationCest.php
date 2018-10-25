<?php

use \Codeception\Step\Argument\PasswordArgument;

class ConfigurationCest {

	public $setup = false;


	/**
	 * Login before
	 *
	 * @param AcceptanceTester $I
	 */
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	/**
	 * @after restoreEuro
	 */
	public function checkEuro( AcceptanceTester $I ) {
		$I->wantToTest( 'I have error message if Euro not selected as currency' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings' );

		# Change the currency
		$I->selectOption( 'woocommerce_currency', 'BBD' );
		$I->click( '.woocommerce-save-button' );

		# Check disabled on payplug
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$I->canSee( 'Your shop currency must be set up with Euro.' );
	}

	/**
	 * Restore the Euro previous parameter
	 *
	 * @param AcceptanceTester $I
	 */
	protected function restoreEuro( AcceptanceTester $I ) {
		$I->amOnAdminPage( 'admin.php?page=wc-settings' );

		# Change the currency
		$I->selectOption( 'woocommerce_currency', 'EUR' );
		$I->click( '.woocommerce-save-button' );
	}
}
