<?php

use \Codeception\Step\Argument\PasswordArgument;

class PaymentOneClickCest {

	public $setup = false;

	public function _before( AcceptanceTester $I ) {

		if ( ! $this->setup ) {
			$I->loginAsAdmin();
			$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

			$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
			$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

			$I->click( '.forminp input[type="submit"]' );
			$this->setup = true;
		}


		$I->am( 'Customer' );

		$I->amOnPage( '/shop/' );
		$I->click( '.ajax_add_to_cart' );
		$I->waitForText( 'View cart' );
		$I->click( '.added_to_cart' );
		$I->click( 'Proceed to checkout' );
	}

	public function _after( AcceptanceTester $I ) {
	}

	/**
	 * Checkout page, check this is not possible to add any card
	 *
	 * @param AcceptanceTester $I
	 */
	public function testNotConfiguredOneClickDisplayed( AcceptanceTester $I ) {
		$I->wantToTest('That I cannot check the oneClick if not activated ');

		$I->waitForElement( '#place_order' );
		$I->dontSeeElement('#payment li.woocommerce-SavedPaymentMethods-token');

		// Fill form
		$I->fillField( 'billing_first_name', "First Name" );
		$I->fillField( 'billing_last_name', "Last Name" );
		$I->fillField( 'billing_address_1', "118 avenenue Jean Jaurès" );
		$I->fillField( 'billing_city', "Paris" );
		$I->fillField( 'billing_postcode', "75019" );
		$I->fillField( 'billing_email', "test@payplug.localhost" );
		$I->fillField( 'billing_phone', "0123456789" );

		// wait ajax done, submit the form
		$I->wait( 1 );
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

		$I->dontSeeElement('.wrap-save-card');
	}

	public function testConfiguredOneClick( AcceptanceTester $I ){
		$I->wantToTest('That I have the checkbox displayed on oneclick ');

		// Setup admin on one click
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$I->checkOption( 'woocommerce_payplug_oneclick' );
		$I->click( '.forminp input[type="submit"]' );

		// Customer tests
		$I->am( 'Customer' );
		$I->amOnPage( '/checkout/' );

		// Place an order
		$I->waitForElement( '#place_order' );
		$I->dontSeeElement('#payment li.woocommerce-SavedPaymentMethods-token');

		// Fill form
		$I->fillField( 'billing_first_name', "First Name" );
		$I->fillField( 'billing_last_name', "Last Name" );
		$I->fillField( 'billing_address_1', "118 avenenue Jean Jaurès" );
		$I->fillField( 'billing_city', "Paris" );
		$I->fillField( 'billing_postcode', "75019" );
		$I->fillField( 'billing_email', "test@payplug.localhost" );
		$I->fillField( 'billing_phone', "0123456789" );

		// wait ajax done, submit the form
		$I->wait( 1 );
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

		// There is the cechkbox displayed
		$I->SeeElement('.wrap-save-card');
	}
}