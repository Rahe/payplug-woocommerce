<?php

use \Codeception\Step\Argument\PasswordArgument;


class AdminNotConfiguredCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	public function _after( AcceptanceTester $I ) {

	}

	// tests
	public function checkPayplugOnAdminPaymentList( AcceptanceTester $I ) {
		$I->wantToTest( 'I Have payplug on the payment list' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout' );

		$I->canSee( 'PayPlug' );
		$I->canSee( 'Let your customers pay with PayPlug' );
	}

	public function checkNotLogged( AcceptanceTester $I ) {
		$I->wantToTest( 'I have messages to configure the plugin' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->canSee( 'PHP cURL extension is enabled on your server.' );
		$I->canSee( 'Your server is running a valid PHP version.' );
		$I->canSee( 'OpenSSL is up to date.' );
		$I->canSee( 'You must connect your Payplug account.' );
		$I->canSee( 'Your shop use Euro as your currency.' );
	}

	public function checkLoginFail( AcceptanceTester $I ) {
		$I->wantToTest( 'I have login fail message' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->fillField( 'payplug_email', 'nope' );
		$I->fillField( 'payplug_password', 'nope' );

		$I->click( '.forminp input[type="submit"]' );
		$I->canSee( 'Your credentials are invalid.' );
	}

	/**
	 * @param AcceptanceTester $I
	 * @after logout
	 */
	public function checkLoginSuccess( AcceptanceTester $I ) {
		$I->wantToTest( 'I have login success message' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

		$I->click( '.forminp input[type="submit"]' );
		$I->canSee( 'Successfully logged in.' );
		$I->canSee( 'Your settings have been saved.' );
		$I->canSee( 'PayPlug is in TEST mode' );
		$I->canSee( 'When your account is approved by PayPlug, please disconnect and reconnect in the settings page to activate LIVE mode.' );
	}

	/**
	 * @param AcceptanceTester $I
	 * @after logout
	 */

	public function checkNoLiveForNotCertified( AcceptanceTester $I ) {
		$I->wantToTest( 'I cannot activate Live mode' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

		$I->click( '.forminp input[type="submit"]' );
		$I->click( 'label[for="woocommerce_payplug_mode-yes"]' );

		$I->click( '.submit button' );
		$I->canSee( 'Your account does not currently support LIVE mode, it need to be approved first. If your account has already been approved, please log out and log back in.' );

		$I->seeCheckboxIsChecked( '#woocommerce_payplug_mode-no' );
	}

	/**
	 * @param AcceptanceTester $I
	 * @after logout
	 */
	public function checkLiveForCertifiedAndNoOneClick( AcceptanceTester $I ) {
		$I->wantToTest( 'I login as certified and cannot have the one click activated' );

		# Login
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL_CERTIFIED' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD_CERTIFIED' ) ) );
		$I->click( '.forminp input[type="submit"]' );

		# Activate the one click
		$I->click( '//*[@id="mainform"]/table[3]/tbody/tr/td/fieldset/div[1]/label' );
		$I->click( '.submit button' );

		# Check this is not working
		$I->seeElement( '#woocommerce_payplug_oneclick[disabled="disabled"]' );
	}

	/**
	 * Basic logout function
	 *
	 * @param AcceptanceTester $I
	 */
	protected function logout( AcceptanceTester $I ) {
		// logout
		$I->click( '//*[@id="mainform"]/table[2]/tbody/tr/td/p[2]/input[1]' );
	}

}
