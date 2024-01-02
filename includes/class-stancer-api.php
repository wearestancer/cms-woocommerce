<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com/
 * @license MIT
 * @copyright 2023 Stancer / Iliad 78
 *
 * @package stancer
 * @subpackage stancer/includes
 */

/**
 * Stancer API.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Api {
	/**
	 * Stancer API configuration.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_Stancer_Config
	 */
	public $api_config;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Stancer_Config $api_config Configuration.
	 */
	public function __construct( WC_Stancer_Config $api_config ) {
		$this->api_config = $api_config;
	}

	/**
	 * Prepare payment data for send a payment to Stancer.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @param bool|null $force_auth Do we need to force authentication.
	 *
	 * @return array
	 */
	public function build_payment_data( WC_Order $order, $force_auth = null ) {
		$total = $order->get_total();
		$amount = static::prepare_amount( $total );
		$auth = $force_auth;
		$currency_code = $order->get_currency();
		$description = null;

		if ( null === $auth ) {
			$auth_limit = $this->api_config->auth_limit;
			$auth = is_null( $auth_limit ) || '' === $auth_limit ? false : $total >= $auth_limit;
		}

		if ( $this->api_config->description ) {
			$params = [
				'SHOP_NAME' => 'WooCommerce',
				'CART_ID' => (int) $order->get_id(),
				'TOTAL_AMOUNT' => sprintf( '%.02f', $total ),
				'CURRENCY' => strtoupper( $currency_code ),
			];

			$description = str_replace( array_keys( $params ), $params, $this->api_config->description );
		}

		return [
			'amount' => $amount,
			'auth' => $auth,
			'capture' => false,
			'currency' => strtolower( $currency_code ),
			'description' => $description,
			'order_id' => (string) $order->get_id(),
			'return_url' => $order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * Prepare amount.
	 *
	 * @since 1.1.0
	 *
	 * @param int|float $amount Amount of the order.
	 *
	 * @return int
	 */
	public static function prepare_amount( $amount ) {
		$price_decimals = wc_get_price_decimals();
		$price_multiplier = pow( 10, $price_decimals );

		return (int) (string) ( $amount * $price_multiplier );
	}

	/**
	 * Send payment to Stancer API.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @param string|null $card_id Card identifier.
	 * @param bool|null $force_auth Force authentication, keep `null` to let the configuration decide.
	 *
	 * @return Stancer\Payment|null
	 */
	public function send_payment( WC_Order $order, $card_id = null, $force_auth = null ): ?Stancer\Payment {
		$payment_data = $this->build_payment_data( $order, $force_auth );

		$api_payment = null;
		$stancer_payment = WC_Stancer_Payment::find( $order, $payment_data, true, [ 'pending' ] );

		if ( $stancer_payment ) {
			$api_payment = new Stancer\Payment( $stancer_payment->payment_id );

			if ( $card_id ) {
				if ( $payment_data['auth'] ) {
					$api_payment->set_auth( $api_payment->getPaymentPageUrl() );
				} else {
					$api_payment->set_status( Stancer\Payment\Status::CAPTURE );
				}

				$api_payment->set_card( new Stancer\Card( $card_id ) );
			}

			if ( $api_payment->isModified() && ! static::sent_object_to_api( $api_payment ) ) {
				return null;
			}

			$api_cutomer = $api_payment->customer;
			WC_Stancer_Customer::save_from( $api_cutomer );
		}

		return $api_payment;
	}

	/**
	 * Send payment to API.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Payment $object Object to send.
	 *
	 * @return bool
	 */
	public static function sent_object_to_api( $object ): bool {
		if ( $object->isNotModified() ) {
			return true;
		}

		try {
			$object->send();
		} catch ( Exception $exception ) {
			$log = $exception->getMessage();

			if ( ! empty( $log ) ) {
				if ( class_exists( 'WC_Logger' ) ) {
					wc_get_logger()->debug( 'Stancer --- ' . $log );
				}

				return false;
			}
		}

		return true;
	}
}
