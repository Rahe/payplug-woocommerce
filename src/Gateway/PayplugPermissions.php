<?php

namespace Payplug\PayplugWoocommerce\Gateway;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Payplug\Authentication;
use Payplug\Exception\ConfigurationNotSetException;

class PayplugPermissions {

	const LIVE_MODE = 'use_live_mode';
	const SAVE_CARD = 'can_save_cards';

	/**
	 * @var PayplugGateway
	 */
	private $gateway;

	/**
	 * @var array
	 */
	private $permissions;

	/**
	 * PayplugPermissions constructor.
	 *
	 * @param PayplugGateway $gateway
	 */
	public function __construct( PayplugGateway $gateway ) {
		$this->gateway = $gateway;
		$this->load_permissions();
	}

	/**
	 * Get all permissions.
	 *
	 * @return array
	 */
	public function get_permissions() {
		return $this->permissions;
	}

	/**
	 * Check if user has specific permission.
	 *
	 * @param string $user_can
	 *
	 * @return bool
	 */
	public function has_permissions( $user_can ) {
		if ( empty( $user_can ) ) {
			return false;
		}

		return isset( $this->permissions[ $user_can ] ) && true === $this->permissions[ $user_can ];
	}

	/**
	 * Load permissions for the current mode.
	 */
	protected function load_permissions() {
		try {
			$response          = Authentication::getPermissions();
			$this->permissions = ! empty( $response ) ? $response : [];
		} catch ( ConfigurationNotSetException $e ) {
			$this->permissions = [];
		}
	}
}