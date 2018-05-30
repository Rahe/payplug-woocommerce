<?php

use \Codeception\Step\Argument\PasswordArgument;

class AdminNotConfiguredCest {
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
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->click('.forminp input[name="submit_logout"]');
	}

	public function checkLogged( AcceptanceTester $I ) {
		$I->wantToTest( 'I am logged' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->canSee( 'Go to my dashboard' );
	}

	/**
	 * @after checkLogged
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

		# Check payment disabled on admin list
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout' );
		$I->seeElement( 'tr[data-gateway_id="payplug"] .woocommerce-input-toggle--disabled' );

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