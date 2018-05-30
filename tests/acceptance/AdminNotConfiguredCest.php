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

		$I->canSee( 'The PHP Curl extention is installed and available.' );
		$I->canSee( 'Your PHP version is up-to-date.' );
		$I->canSee( 'Your OpenSSL version is up-to-date.' );
		$I->canSee( 'You must logged in to your PayPlug account.' );
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

	public function checkLoginSuccess( AcceptanceTester $I ) {
		$I->wantToTest( 'I have login success message' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
		$I->fillField( 'payplug_password',  new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

		$I->click( '.forminp input[type="submit"]' );
		$I->canSee( 'Successfully logged in.' );
		$I->canSee( 'Your settings have been saved.' );
		$I->canSee( 'PayPlug is in TEST mode' );
		$I->canSee( 'When your account is approved by PayPlug, please disconnect and reconnect in the settings page to activate LIVE mode.' );
	}

}
