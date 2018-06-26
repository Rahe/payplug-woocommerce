<?php

use \Codeception\Step\Argument\PasswordArgument;

class RefundCest {

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
	}

	public function _after( AcceptanceTester $I ) {
	}

}