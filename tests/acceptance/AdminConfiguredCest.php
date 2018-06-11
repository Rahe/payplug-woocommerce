<?php

use \Codeception\Step\Argument\PasswordArgument;

class AdminConfiguredCest {
	/**
	 * Login before
	 *
	 * @param AcceptanceTester $I
	 */
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

		$I->click('.forminp input[type="submit"]');
	}

	/**
	 * Logout after
	 *
	 * @param AcceptanceTester $I
	 */
	public function _after( AcceptanceTester $I ) {
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
		$I->canSee( 'Your shop must use Euro as your currency.' );
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
