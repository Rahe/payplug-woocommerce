<?php

use \Codeception\Step\Argument\PasswordArgument;

class PaymentLightboxCest {

	public $setup = false;

	public function _before( AcceptanceTester $I ) {

		if ( ! $this->setup ) {
			$I->loginAsAdmin();
			$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

			$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
			$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

			$I->click( '.forminp input[type="submit"]' );

			// Embed
			$I->click( '//*[@id="mainform"]/table[4]/tbody/tr[1]/td/fieldset/label[2]' );
			$I->click( '#mainform > p.submit > button' );

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
	 * Fill the form fields and test
	 *
	 * @param AcceptanceTester $I
	 */
	public function testConfiguredCartCheckout( AcceptanceTester $I ) {
		$I->wantToTest( 'In DEV mode and filled form, cart displays message' );

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

		// Wheck we have the lightbox
		$I->waitForElementVisible( '#iframe-payplug', 60 );
		$I->executeJS('jQuery("#iframe-payplug").attr("name", "payplug")');
		$I->switchToIFrame( 'payplug' );

		// Post in database
		$I->seePostInDatabase( [ 'post_status' => 'wc-pending' ] );

		// Right payment error
		foreach ( [ 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2 ] as $char ) {
			$I->pressKey( '#paymentCardNumber', $char );
		}

		$I->fillField( [ 'id' => 'paymentCardExpiration' ], "11/2099" );
		$I->fillField( [ 'id' => 'paymentCardCvv' ], "123" );
		$I->wait( 1 );

		$I->click( '#payButton' );

		$I->waitForText( 'Order received' );
		$I->waitForText( 'Thank you. Your order has been received.' );

		$I->seePostInDatabase( [ 'post_status' => 'wc-pending' ] );
	}

	/**
	 * Fill the form fields and test
	 *
	 * @param AcceptanceTester $I
	 */
	public
	function testConfiguredCartCheckoutCancel(
		AcceptanceTester $I
	) {
		$I->wantToTest( 'In DEV mode and filled form, cancel the payplug' );

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

		// Wheck we have the lightbox
		$I->waitForElementVisible( '#iframe-payplug', 60 );
		$I->executeJS('jQuery("#iframe-payplug").attr("name", "payplug")');
		$I->switchToIFrame( 'payplug' );

		// Post in database
		$I->seePostInDatabase( [ 'post_status' => 'wc-pending' ] );

		// Cancel
		$I->click( '#iframe-payplug-close-link' );

		$I->waitForText( 'Your order was cancelled.' );

		$I->seePostInDatabase( [ 'post_status' => 'wc-cancelled' ] );

	}
}