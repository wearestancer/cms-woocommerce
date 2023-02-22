<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com
 * @license MIT
 * @copyright 2023 Stancer / Iliad 78
 *
 * @package stancer
 * @subpackage stancer/includes
 */

/**
 * Payment table representation
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Payment extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $primary = 'stancer_payment_id';

	/**
	 * Table name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $table = 'wc_stancer_payment';

	/**
	 * API payment ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $payment_id;

	/**
	 * API card ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $card_id;

	/**
	 * API customer ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $customer_id;

	/**
	 * Currency used
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $currency;

	/**
	 * Amount
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $amount;

	/**
	 * WooCommerce order ID
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $order_id = null;

	/**
	 * Status
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $status = 'pending';

	/**
	 * Card creation date in Stancer Api
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $created;

	/**
	 * Retrieve a payment.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order                Order to find.
	 * @param array    $payment_data         Payment data used to create a new payment.
	 * @param bool     $generate_api_payment Do we need to generate a new payment if not already present.
	 * @param string   $status               Status to find.
	 *
	 * @return WC_Stancer_Payment
	 */
	public static function find(
		WC_Order $order,
		array $payment_data = [],
		bool $generate_api_payment = false,
		string $status = 'pending'
	) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_stancer_payment WHERE order_id = %d AND status = %s",
				absint( $order->get_id() ),
				esc_sql( $status )
			)
		);

		if ( ! $row && $generate_api_payment ) {
			$api_payment = static::generate_api_payment( $order, $payment_data );
			$stancer_payment = static::save_from( $api_payment );
		} else {
			$stancer_payment = new static();
			$stancer_payment->hydrate( (array) $row );
		}

		return $stancer_payment;
	}

	/**
	 * Generate API payment
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order        Source order.
	 * @param array    $payment_data Payment data.
	 */
	public static function generate_api_payment( WC_Order $order, array $payment_data ) {
		$customer = [
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'email' => $order->get_billing_email(),
			'id' => $order->get_customer_id(),
		];

		$api_customer = WC_Stancer_Customer::get_api_customer( $customer );

		$api_payment = new Stancer\Payment();
		$api_payment
			->setOrderId( ( (string) $order->get_id() ) )
			->setAmount( $payment_data['amount'] )
			->setCurrency( $payment_data['currency'] )
			->setCustomer( $api_customer )
		;

		if ( $payment_data['return_url'] ) {
			$api_payment->setReturnUrl( $payment_data['return_url'] );
		}

		if ( $payment_data['auth'] ) {
			$api_payment->setAuth( true );
		}

		if ( $payment_data['description'] ) {
			$api_payment->setDescription( $payment_data['description'] );
		}

		WC_Stancer_Api::sent_object_to_api( $api_payment );

		return $api_payment;
	}

	/**
	 * Get API payment
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Stancer_Payment $stancer_payment WooCommerce based payment.
	 * @return Stancer\Payment
	 */
	public static function get_api_payment( $stancer_payment ) {
		return new Stancer\Payment( $stancer_payment->payment_id );
	}

	/**
	 * Retrieves payment
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order                Order to find.
	 * @param array    $payment_data         Payment data used to create a new payment.
	 * @param bool     $generate_api_payment Do we need to generate a new payment if not already present.
	 * @return WC_Stancer_Payment
	 */
	public static function get_payment( $order, array $payment_data = [], bool $generate_api_payment = false ) {
		$stancer_payment = static::find( $order, $payment_data, $generate_api_payment );

		return $stancer_payment;
	}

	/**
	 * Update payment status
	 *
	 * @since 1.0.0
	 *
	 * @param string $status New status.
	 */
	public function markAs( string $status ) {
		$this->status = $status;
		$this->save();
	}

	/**
	 * Create or update an Stancer payment from Stancer API payment object
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Payment $api_payment Object to save.
	 * @return WC_Stancer_Payment
	 */
	public static function save_from( Stancer\Payment $api_payment ) {
		$stancer_payment = new WC_Stancer_Payment();

		$card = $api_payment->getCard();
		$creation = $api_payment->getCreationDate();
		$customer = $api_payment->getCustomer();

		$stancer_payment->payment_id = $api_payment->getId();
		$stancer_payment->currency = $api_payment->getCurrency();
		$stancer_payment->amount = $api_payment->getAmount();
		$stancer_payment->status = $api_payment->getStatus() ?? 'pending';
		$stancer_payment->card_id = $card ? $card->getId() : null;
		$stancer_payment->created = $creation ? $creation->format( 'Y-m-d H:i:s' ) : null;
		$stancer_payment->customer_id = $customer ? $customer->getId() : null;
		$stancer_payment->order_id = $api_payment->getOrderId();

		$stancer_payment->save();

		return $stancer_payment;
	}
}
