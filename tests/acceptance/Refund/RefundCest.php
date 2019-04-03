<?php

use \Codeception\Step\Argument\PasswordArgument;
use \Codeception\Util\Locator;

class RefundCest {

	public $setup = false;
	public $order_id = 0;

	public function _before( AcceptanceTester $I ) {
		/**
		 * 1. Setup the connexion to payplug
		 * 2. Create and validate an order with payplug API
		 */
		if ( ! $this->setup ) {
			$I->loginAsAdmin();

			$I->amOnAdminPage( 'post.php' );


			$I->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=payplug' );

			/**
			 * Ensure we are logged out
			 */
			try {
				if ( Locator::find( '#mainform input[name="submit_logout"]', [] ) ) {
					$I->click( '#mainform input[name="submit_logout"]' );
				}
			} catch ( Exception $e ) {
			}

			$I->fillField( 'payplug_email', getenv( 'PAYPLUG_TEST_EMAIL' ) );
			$I->fillField( 'payplug_password', new PasswordArgument( getenv( 'PAYPLUG_TEST_PASSWORD' ) ) );

			$I->click( '.forminp input[type="submit"]' );
			$this->setup = true;
		}
	}

	protected function setup_order( AcceptanceTester $I ) {

		$I->am( 'Customer' );

		$I->amOnPage( '/shop/' );
		$I->click( '.ajax_add_to_cart' );
		$I->waitForText( 'View cart' );
		$I->click( '.added_to_cart' );
		$I->click( 'Proceed to checkout' );

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
		$I->waitForText( 'YOUR CARD', 30 );
		$I->waitForText( 'YOU ARE ON A TEST ENVIRONMENT.' );

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


		// Fill the orderID
		$this->order_id = $I->grabLatestEntryByFromDatabase( 'wp_posts', 'ID' );

	}

	public function _after( AcceptanceTester $I ) {
	}

	/**
	 * @param AcceptanceTester $I
	 *
	 * @before setup_order
	 */
	public function testRefundTotalNote( AcceptanceTester $I ) {
		$I->amGoingTo( 'Total Refund and note displayed' );
		$I->loginAsAdmin();

		$order = wc_get_order( $this->order_id );
		$I->amOnUrl( admin_url( sprintf( 'post.php?post=%s&action=edit', $this->order_id ) ) );

		// Show the controls
		$I->click( 'button.refund-items' );

		// Fill with the current value
		$I->waitForElementVisible( "#refund_amount" );
		$amount = (int) number_format( $order->get_total(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		$I->fillField( 'refund_amount', $amount );

		# Wait for the refund
		$I->waitForElement( ".refund-actions" );
		$I->click( '.do-api-refund' );
		$I->acceptPopup();

		$I->wait( 3 );

		$I->waitForElementVisible( 'tr.refund ', 60 );


		/**
		 * Check line added
		 */
		$refunds = $order->get_refunds();
		$refund  = $refunds[0];

		$I->canSee( 'Refund #' . $refund->get_id() );

		/**
		 * Check status modified
		 */
		$I->seePostInDatabase( [
			'ID'          => $this->order_id,
			'post_status' => 'wc-refunded',
			'post_type'   => 'shop_order',
		] );

		$I->canSee( sprintf( 'Refunded %s', $amount ) );
	}

	/**
	 * @param AcceptanceTester $I
	 *
	 * @before setup_order
	 */
	public function testRefundPartialNote( AcceptanceTester $I ) {
		$I->wantToTest( 'Total Refund and note displayed' );
		$I->loginAsAdmin();

		$order  = wc_get_order( $this->order_id );
		$status = $order->get_status();
		$I->amOnUrl( admin_url( sprintf( 'post.php?post=%s&action=edit', $this->order_id ) ) );

		// Show the controls
		$I->click( 'button.refund-items' );

		// Fill with the current value
		$I->waitForElementVisible( "#refund_amount" );
		$amount = (int) number_format( $order->get_total() - 1, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		$I->fillField( 'refund_amount', $amount );

		# Wait for the refund
		$I->waitForElement( ".refund-actions" );
		$I->click( '.do-api-refund' );
		$I->acceptPopup();

		$I->wait( 3 );

		$I->waitForElementVisible( 'tr.refund ', 60 );

		/**
		 * Check line added
		 */
		$refunds = $order->get_refunds();
		$refund  = $refunds[0];

		/**
		 * Check status not modified
		 */
		$I->seePostInDatabase( [
			'ID'          => $this->order_id,
			'post_status' => 'wc-'.$status,
			'post_type'   => 'shop_order',
		] );

		$I->canSee( 'Refund #' . $refund->get_id() );
	}
}
