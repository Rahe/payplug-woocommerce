<?php
opcache_reset();

class PaymentCest {
	public function _before( AcceptanceTester $I ) {
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
	 * Checkout page, check there is the developper mode message
	 *
	 * @param AcceptanceTester $I
	 */
	public function testNotConfiguredCheckoutMessagge( AcceptanceTester $I ) {
		$I->wantToTest( 'In DEV mode, checkout displays message' );

		// Message for testers
		$I->see( 'Pay via PayPlug. You are in TEST MODE. In test mode you can use the card 4242424242424242 with any valid expiration date and CVC.' );
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

		// Check the options
		$I->checkOption( 'payment_method' );

		// wait ajax done, submit the form
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );

		// Right payment error
		$I->fillField( [ 'id' => 'paymentCardNumber' ], "4242 4242 4242 4242" );
		$I->fillField( [ 'id' => 'paymentCardExpiration' ], "11/2099" );
		$I->fillField( [ 'id' => 'paymentCardCvv' ], "123" );
		$I->wait(1);

		$I->click( '#payButton' );

		$I->waitForText( 'Order received' );
		$I->waitForText( 'Thank you. Your order has been received.' );
	}

	/**
	 * Fill the form fields and test
	 *
	 * @param AcceptanceTester $I
	 */
	public function testConfiguredCartCheckoutCancel( AcceptanceTester $I ) {
		$I->wantToTest( 'In DEV mode and filled form, cancel the payplug' );

		// Fill form
		$I->fillField( 'billing_first_name', "First Name" );
		$I->fillField( 'billing_last_name', "Last Name" );
		$I->fillField( 'billing_address_1', "118 avenenue Jean Jaurès" );
		$I->fillField( 'billing_city', "Paris" );
		$I->fillField( 'billing_postcode', "75019" );
		$I->fillField( 'billing_email', "test@payplug.localhost" );
		$I->fillField( 'billing_phone', "0123456789" );

		// Check the options
		$I->checkOption( 'payment_method' );

		// wait ajax done, submit the form
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );

		// Cancel
		$I->click( '#linkBackMerchant' );

		$I->waitForText( 'Your order was cancelled.' );
	}
}