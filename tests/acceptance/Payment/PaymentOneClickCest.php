<?php

use \Codeception\Step\Argument\PasswordArgument;

class PaymentOneClickCest {

	public $setup = false;

	public function _before( AcceptanceTester $I ) {
		/**
		 * Login to payplug the account.
		 */
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

	/**
	 * Checkout page, check this is not possible to add any card
	 *
	 * @param AcceptanceTester $I
	 */
	public function testNotConfiguredOneClickDisplayed( AcceptanceTester $I ) {
		$I->wantToTest( 'That I cannot check the oneClick if not activated ' );

		$I->expect( 'That I cannot use the onelick when not activated.' );

		$I->waitForElement( '#place_order' );
		$I->dontSeeElement( '#payment li.woocommerce-SavedPaymentMethods-token' );

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

		$I->expect( 'That I do not have the save card form.' );

		$I->dontSeeElement( '.wrap-save-card' );
	}

	public function testConfiguredOneClick( AcceptanceTester $I ) {
		$I->wantToTest( 'That I have the checkbox displayed on oneclick ' );

		// Setup admin on one click
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$I->checkOption( 'woocommerce_payplug_oneclick' );
		$I->click( '#mainform > p.submit > button' );

		// Customer tests
		$I->am( 'Customer' );
		$I->amOnPage( '/checkout/' );

		// Place an order
		$I->waitForElement( '#place_order' );
		$I->dontSeeElement( '#payment li.woocommerce-SavedPaymentMethods-token' );

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

		$I->expect( 'That I have the one click saving options displayed.' );

		// Wheck we are on Payplug page
		$I->waitForText( 'YOUR CARD' );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

		// There is the cechkbox displayed
		$I->SeeElement( '.wrap-save-card' );
	}

	public function testConfiguredOneClickWithPaymentCard( AcceptanceTester $I ) {
		$I->wantToTest( 'That the card is available for payment ' );

		// Setup admin on one click
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );
		$I->checkOption( 'woocommerce_payplug_oneclick' );
		$I->click( '#mainform > p.submit > button' );

		// Create fake resource
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 1;
		$resource->card->last4     = '3333';
		$resource->card->exp_year  = '2099';
		$resource->card->exp_month = '01';
		$resource->card->brand     = 'mastercard';
		$gateway                   = WC()->payment_gateways()->payment_gateways()['payplug'];

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );
		$token->add_meta_data( 'mode', 'test' );
		$token->add_meta_data( 'payplug_account', \wc_clean( $gateway->get_merchant_id() ), true );
		$token->save();

		// Refresh page
		$I->amOnPage( '/checkout/' );

		$I->expect( 'That I have my card displayed on the checkout page.' );

		// See the element
		$I->wait( 2 );
		$I->waitForElement( '#place_order', 10 );
		$I->see( 'Mastercard ending in 3333' );

		$I->expect( 'That I have my card displayed on the payment methods page.' );
		// See the card on account
		$I->amOnPage( '/my-account/payment-methods/' );
		$I->see( 'Mastercard ending in 3333' );
	}

	public function testOneClickCardExpired( AcceptanceTester $I ) {
		$I->wantToTest( 'That the expired card isnt available for payment ' );

		$gateway                   = WC()->payment_gateways()->payment_gateways()['payplug'];

		/**
		 * Expired card.
		 */
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 2;
		$resource->card->last4     = '2222';
		$resource->card->exp_year  = '2017';
		$resource->card->exp_month = '01';
		$resource->card->brand     = 'mastercard';

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );
		$token->add_meta_data( 'mode', 'test' );
		$token->add_meta_data( 'payplug_account', \wc_clean( $gateway->get_merchant_id() ), true );

		$token->save();

		/**
		 * Expired card for current year, previous month.
		 */
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 3;
		$resource->card->last4     = '3333';
		$resource->card->exp_year  = date( 'Y' );
		$resource->card->exp_month = date( 'm', strtotime( 'previous month' ) );
		$resource->card->brand     = 'mastercard';

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );
		$token->add_meta_data( 'mode', 'test' );
		$token->add_meta_data( 'payplug_account', \wc_clean( $gateway->get_merchant_id() ), true );

		$token->save();

		/**
		 * Expired card for current year, current month.
		 */
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 4;
		$resource->card->last4     = '4444';
		$resource->card->exp_year  = date( 'Y' );
		$resource->card->exp_month = date( 'm' );
		$resource->card->brand     = 'mastercard';

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );
		$token->add_meta_data( 'mode', 'test' );
		$token->add_meta_data( 'payplug_account', \wc_clean( $gateway->get_merchant_id() ), true );

		$token->save();

		/**
		 * Other Merchant ID.
		 */
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 5;
		$resource->card->last4     = '5555';
		$resource->card->exp_year  = date( 'Y' );
		$resource->card->exp_month = date( 'm' );
		$resource->card->brand     = 'mastercard';

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'payplug' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );
		$token->add_meta_data( 'mode', 'test' );
		$token->add_meta_data( 'payplug_account', 'other_merchant_id', true );

		$token->save();

		/**
		 * Not payplug gateway.
		 */
		$resource                  = new \StdClass();
		$resource->card            = new \StdClass();
		$resource->card->id        = 6;
		$resource->card->last4     = '6666';
		$resource->card->exp_year  = '2099';
		$resource->card->exp_month = '01';
		$resource->card->brand     = 'mastercard';

		// Create token for the current user
		$token = new \WC_Payment_Token_CC();
		$token->set_token( wc_clean( $resource->card->id ) );
		$token->set_gateway_id( 'other_gateway' );
		$token->set_last4( wc_clean( $resource->card->last4 ) );
		$token->set_expiry_year( wc_clean( $resource->card->exp_year ) );
		$token->set_expiry_month( zeroise( (int) wc_clean( $resource->card->exp_month ), 2 ) );
		$token->set_card_type( wc_clean( $resource->card->brand ) );
		$token->set_user_id( 1 );

		$token->save();

		// Refresh page
		$I->amOnPage( '/' );
		$I->amOnPage( '/checkout/' );

		$I->expect( 'That my expired card is not displayed on the checkout page.' );

		// See the element
		$I->wait( 2 );
		$I->waitForElement( '#place_order' );

		/*
		 * All of theses card shouldn't be displayed.
		 */
		$I->dontSee( 'Mastercard ending in 2222' );
		$I->dontSee( 'Mastercard ending in 3333' );
		$I->dontSee( 'Mastercard ending in 4444' );
		$I->dontSee( 'Mastercard ending in 5555' );
		$I->dontSee( 'Mastercard ending in 6666' );
	}
}