<?php

use \Codeception\Step\Argument\PasswordArgument;


class ClassicCest {

	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	/**
	 * @param AcceptanceTester $I
	 */
	public function checkPayplugOnAdminPaymentList( AcceptanceTester $I ) {
		$I->wantToTest( 'I Have payplug on the payment list' );

		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout' );

		$I->expect( 'That Payplug checkout is listed on the checkout provider page.' );

		$I->see( 'PayPlug' );
		$I->see( 'Enable PayPlug for your customers' );
	}

	/**
	 * @param AcceptanceTester $I
	 */
	public function checkNotLogged( AcceptanceTester $I ) {
		$I->wantToTest( 'I have messages to configure the plugin' );

		$I->loginAsAdmin();

		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->expect( 'That i\'m not logged to my payplug account.' );

		$I->see( 'The PHP cURL extension is installed and activated on your server.' );
		$I->see( 'The PHP version on your server is valid.' );
		$I->see( 'OpenSSL is up to date.' );
		$I->see( 'You must be logged in with your PayPlug account.' );
		$I->see( 'Your shop currency has been set up with Euro.' );
	}

	/**
	 * @param AcceptanceTester $I
	 *
	 */
	public function checkLoginFail( AcceptanceTester $I ) {
		$I->wantToTest( 'I have login fail message' );

		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->expect( 'That a wrong email and password displays an error message.' );

		$I->fillField( 'payplug_email', 'nope' );
		$I->fillField( 'payplug_password', 'nope' );

		$I->click( '.forminp input[type="submit"]' );
		$I->see( 'Invalid credentials.' );
	}

	/**
	 * @param AcceptanceTester $I
	 *
	 */
	public function checkLoginSuccess( AcceptanceTester $I ) {
		$I->wantToTest( 'I have login success message' );

		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->expect( 'That when logged with a TEST account, that messages are displayed in the admin, options saved and LIVE message is displayed.' );

		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

		$I->click( '.forminp input[type="submit"]' );

		$I->see( 'Successfully logged in.' );
		$I->see( 'Your settings have been saved.' );
		$I->see( 'PayPlug is in TEST mode' );
		$I->see( 'Once your PayPlug account has been validated, please log out and log in again from the configuration page in order to activate LIVE mode.' );
		$I->see( 'In TEST mode, all payments will be simulations and will not generate real transactions.' );
	}
}
