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
		// /!\ We cannot relie on woo formatting methods! like  wc_get_price_decimals()

		return (int) (string) ( $amount * 100 );
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

			$api_customer = $api_payment->customer;
			WC_Stancer_Customer::save_from( $api_customer );
		}

		return $api_payment;
	}

	/**
	 * Send Refund to our api.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Order $order Wc order.
	 * @param float|null $refund_amount amount to be refund in cents.
	 * @return Stancer\Payment
	 * @throws WC_Stancer_Exception Check that the refund amount is above 50 cents.
	 * @throws WC_Stancer_Exception Check for minimum sum before accepting refund or showing an error Message to the User.
	 * @throws WC_Stancer_Exception Catch all api exception and translate it for users.
	 */
	public function send_refund( WC_Order $order, ?int $refund_amount ): Stancer\Payment {
		if ( ! $refund_amount || 0 === $refund_amount ) {
			throw new WC_Stancer_Exception( esc_html( __( 'You cannot refund a null amount', 'stancer' ) ) );
		}

		if ( $refund_amount < 50 ) {
			throw new WC_Stancer_Exception( esc_html( __( 'A refund must be above 50 cents', 'stancer' ) ) );
		}

		$transaction_id = $order->get_transaction_id() ?? null;

		if ( ! $transaction_id ) {
			$stancer_payment = WC_Stancer_Payment::find( $order );
			$transaction_id = $stancer_payment->payment_id;
		}

		$api_payment = new Stancer\Payment( $transaction_id );

		try {
			$api_payment->refund( (int) ( $refund_amount ) );
		} catch ( Stancer\Exceptions\InvalidAmountException $e ) {
			throw new WC_Stancer_Exception(
				esc_html(
					sprintf(
						// translators: "%1f$.02f": refunded payment sums. "%2$.02f": the amount still refundable. "%3$s":  the currency of the transaction.
						__( 'You cannot refund %1$.02f %3$s the order total with already acounted refund is %2$.02f %3$s', 'stancer' ),
						$refund_amount / 100,
						$api_payment->getRefundableAmount() / 100,
						$order->get_currency( 'view' ),
					)
				)
			);
		}
		return $api_payment;
	}


	/**
	 * Send payment to API.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Payment $obj Object to send.
	 *
	 * @return bool
	 */
	public static function sent_object_to_api( $obj ): bool {
		if ( $obj->isNotModified() ) {
			return true;
		}

		try {
			$obj->send();
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
