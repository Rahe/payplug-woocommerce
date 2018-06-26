<?php

use \Codeception\Step\Argument\PasswordArgument;

class PaymentCest {

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
	 * Checkout page, check there is the developper mode message
	 *
	 * @param AcceptanceTester $I
	 */
	public function testNotConfiguredCheckoutMessage( AcceptanceTester $I ) {
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

		// wait ajax done, submit the form
		$I->wait( 1 );
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

		// Right payment error
		foreach ( [ 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2, 4, 2 ] as $char ) {
			$I->pressKey( '#paymentCardNumber', $char );
		}

		// Pending Transaction
		$I->seePostInDatabase( [ 'post_status' => 'wc-pending', 'post_type' => 'shop_order' ] );
		$post_id = $I->grabLatestEntryByFromDatabase( 'wp_posts', 'ID' );

		$I->fillField( [ 'id' => 'paymentCardExpiration' ], "11/2099" );
		$I->fillField( [ 'id' => 'paymentCardCvv' ], "123" );
		$I->wait( 1 );

		$I->click( '#payButton' );

		$I->waitForText( 'Order received' );
		$I->waitForText( 'Thank you. Your order has been received.' );

		$I->seePostInDatabase( [
			'ID'          => $post_id,
			'post_status' => 'wc-processing',
			'post_type'   => 'shop_order',
		] );
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

		// wait ajax done, submit the form
		$I->wait( 1 );
		$I->waitForElement( '#place_order' );
		$I->click( '#place_order' );

		// Wait for Database modified
		$I->wait( 1 );

		// Pending Transaction
		$I->seePostInDatabase( [ 'post_status' => 'wc-pending', 'post_type' => 'shop_order', ] );
		$post_id = $I->grabLatestEntryByFromDatabase( 'wp_posts', 'ID' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

		// Cancel
		$I->click( '#linkBackMerchant' );

		$I->waitForText( 'Your order was cancelled.' );

		$I->seePostInDatabase( [
			'ID'          => $post_id,
			'post_status' => 'wc-cancelled',
			'post_type'   => 'shop_order',
		] );

	}
}