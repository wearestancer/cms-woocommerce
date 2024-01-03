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

/**
 * Payment table representation.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Payment extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $primary = 'stancer_payment_id';

	/**
	 * Table name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'wc_stancer_payment';

	/**
	 * API payment ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $payment_id;

	/**
	 * API card ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $card_id;

	/**
	 * API customer ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $customer_id;

	/**
	 * Currency used.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $currency;

	/**
	 * Amount.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	protected $amount;

	/**
	 * WooCommerce order ID.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	protected $order_id = null;

	/**
	 * Status.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $status = 'pending';

	/**
	 * Card creation date in Stancer API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $created;

	/**
	 * Retrieve a payment.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order to find.
	 * @param array $payment_data Payment data used to create a new payment.
	 * @param bool $generate_api_payment Do we need to generate a new payment if not already present.
	 * @param string[] $status Statuses to find.
	 *
	 * @return ?WC_Stancer_Payment
	 */
	private static function find_by_order_id(
		WC_Order $order,
		array $payment_data = [],
		bool $generate_api_payment = false,
		array $status = []
	) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		$values = [
			absint( $order->get_id() ),
		];
		$sql = "SELECT * FROM {$wpdb->prefix}wc_stancer_payment WHERE order_id = %d";

		if ( ! empty( $status ) ) {
			$placeholder = implode( ', ', array_fill( 0, count( $status ), '%s' ) );
			$sql .= ' AND status IN (' . $placeholder . ')';

			array_push( $values, ...array_map( 'esc_sql', $status ) );
		}

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $values ) );

		// phpcs:enable

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
	 * Generate API payment.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Source order.
	 * @param array $payment_data Payment data.
	 *
	 * @return Stancer\Payment
	 */
	private static function generate_api_payment( WC_Order $order, array $payment_data ) {
		$customer = [
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'email' => $order->get_billing_email(),
			'id' => $order->get_customer_id(),
		];

		$api_customer = WC_Stancer_Customer::get_api_customer( $customer );

		$api_payment = new Stancer\Payment();
		$api_payment->amount = $payment_data['amount'];
		$api_payment->capture = false;
		$api_payment->currency = $payment_data['currency'];
		$api_payment->customer = $api_customer;
		$api_payment->order_id = (string) $order->get_id();
		$api_payment->methods_allowed = [ 'card' ];

		if ( array_key_exists( 'auth', $payment_data ) && $payment_data['auth'] ) {
			$api_payment->auth = true;
		}

		if ( array_key_exists( 'description', $payment_data ) && $payment_data['description'] ) {
			$api_payment->description = $payment_data['description'];
		}

		if ( array_key_exists( 'return_url', $payment_data ) && $payment_data['return_url'] ) {
			$api_payment->return_url = $payment_data['return_url'];
		}

		$api_payment->send();

		return $api_payment;
	}

	/**
	 * Update payment status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status New status.
	 */
	public function mark_as( string $status ) {
		$this->status = $status;
		$this->save();
	}

	/**
	 * Create or update an Stancer payment from Stancer API payment object.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Payment $api_payment Object to save.
	 *
	 * @return WC_Stancer_Payment
	 */
	public static function save_from( Stancer\Payment $api_payment ) {
		$stancer_payment = new WC_Stancer_Payment();

		$card = $api_payment->card;
		$creation = $api_payment->creation_date;
		$customer = $api_payment->customer;

		$stancer_payment->payment_id = $api_payment->id;
		$stancer_payment->currency = $api_payment->currency;
		$stancer_payment->amount = $api_payment->amount;
		$stancer_payment->status = $api_payment->status ?? 'pending';
		$stancer_payment->card_id = $card ? $card->id : null;
		$stancer_payment->created = $creation ? $creation->format( 'Y-m-d H:i:s' ) : null;
		$stancer_payment->customer_id = $customer ? $customer->id : null;
		$stancer_payment->order_id = $api_payment->order_id;

		$stancer_payment->save();

		return $stancer_payment;
	}
}
