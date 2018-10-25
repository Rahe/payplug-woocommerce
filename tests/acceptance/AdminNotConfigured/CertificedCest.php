<?php

use \Codeception\Step\Argument\PasswordArgument;


class CertificedCest {

	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	/**
	 * @param AcceptanceTester $I
	 *
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
		$I->see( 'Your account does not support LIVE mode at the moment, it must be validated first. If your account has already been validated, please log out and log in again.' );

		$I->seeCheckboxIsChecked( '#woocommerce_payplug_mode-no' );
	}

	/**
	 * @param AcceptanceTester $I
	 *
	 * @after logout
	 */
	public function checkLiveForCertifiedAndOneClick( AcceptanceTester $I ) {
		$I->wantToTest( 'I login as certified and can have the one click activated' );
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

		# Login
		$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL_CERTIFIED' ) );
		$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD_CERTIFIED' ) ) );
		$I->click( '.forminp input[type="submit"]' );

		# Activate the one click
		$I->click( '#woocommerce_payplug_oneclick' );
		$I->click( '.submit button' );

		# Check this is not working
		$I->seeElement( '#woocommerce_payplug_oneclick[checked=checked]' );
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
