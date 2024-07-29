<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com/
 * @license MIT
 * @copyright 2023-2024 Stancer / Iliad 78
 *
 * @package stancer
 * @subpackage stancer/includes
 */

use Stancer;

/**
 * Class who create the payments to be send to our API.
 *
 * @since 1.2.5
 */
class WC_Stancer_Payment_Builder {

	use WC_Stancer_Payment_Validator_Traits;

	/**
	 * Parameters for API payments.
	 *
	 * @var array
	 */
	public array $parameters = [];

	/**
	 * The order we create the payment for
	 *
	 * @var WC_Order
	 */
	public WC_Order $order;

	/**
	 * Our Api configuration.
	 *
	 * @var WC_Stancer_Config
	 */
	public WC_Stancer_Config $api_config;

	/**
	 * Get the order and Config.
	 *
	 * @since 1.2.5
	 *
	 * @param WC_Order $order The Order that will be payed.
	 * @param WC_Stancer_Config $api_config our configuration.
	 */
	public function __construct( WC_Order $order, WC_Stancer_Config $api_config ) {
		$this->order = $order;
		$this->api_config = $api_config;
	}

	/**
	 * Prepare payment data for send a payment to Stancer.
	 *
	 * @since 1.0.0
	 * @since 1.2.5 Moved from `WC_Stancer_Api` to `WC_Stancer_Payment_Builder`
	 *
	 * @param bool|null $force_auth Do we need to force authentication.
	 * @return void
	 */
	public function build_payment_data( $force_auth = null ): void {
		$total = $this->order->get_total();
		$amount = static::prepare_amount( $total );
		$auth = $force_auth;
		$currency_code = $this->order->get_currency();
		$params = [
			'CURRENCY' => strtoupper( $currency_code ),
			'CART_ID' => (int) $this->order->get_id(),
			'ORDER_ID' => (int) $this->order->get_id(),
			'SHOP_NAME' => 'WooCommerce',
			'TOTAL_AMOUNT' => sprintf( '%.02f', $total ),
		];
		$description = $this->get_valid_description(
			$params,
			$this->api_config->description,
			// translators: "%s": The order ID.
			sprintf( __( 'Payment for order n°%s', 'stancer' ), $this->order->get_id() )
		);
		if ( null === $auth ) {
			$auth_limit = $this->api_config->auth_limit;
			$auth = is_null( $auth_limit ) || '' === $auth_limit ? false : $total >= $auth_limit;
		}

		$this->parameters = [
			'amount' => $amount,
			'auth' => $auth,
			'capture' => false,
			'currency' => strtolower( $currency_code ),
			'description' => $description,
			'order_id' => (string) $this->order->get_id(),
			'return_url' => $this->order->get_checkout_payment_url( true ),
		];
	}

	/**
	 * Create a Stancer\Payment Ready to be send.
	 *
	 * @since 1.0.0
	 * @since 1.2.5 Moved from `WC_Stancer_Api` to `WC_Stancer_Payment_Builder`
	 *
	 * @param string|null $card_id the id of a registered card if it exist.
	 * @return Stancer\Payment|null
	 */
	public function create_api_payment( ?string $card_id = null ): ?Stancer\Payment {
		$api_payment = null;
		$stancer_payment = WC_Stancer_Payment::find( $this->order, $this->parameters, true, [ 'pending' ] );

		if ( $stancer_payment ) {
			$api_payment = new Stancer\Payment( $stancer_payment->payment_id );

			if ( $card_id ) {
				if ( $this->parameters['auth'] ) {
					$api_payment->set_auth( $api_payment->getPaymentPageUrl() );
				} else {
					$api_payment->set_status( Stancer\Payment\Status::CAPTURE );
				}

				$api_payment->set_card( new Stancer\Card( $card_id ) );
			}
			return $api_payment;
		}
		return null;
	}
}
