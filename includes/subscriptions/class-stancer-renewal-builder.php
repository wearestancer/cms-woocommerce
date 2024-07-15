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
 * @since unreleased
 */
class WCS_Stancer_Renewal_Builder {


	use WC_Stancer_Payment_Validator_Traits;

	/**
	 * The price to be charged in cents.
	 *
	 * @var int
	 */
	public int $amount;

	/**
	 * Our Api configuration.
	 *
	 * @var WC_Stancer_Config
	 */
	public WC_Stancer_Config $api_config;

	/**
	 * The order we create the payment for
	 *
	 * @var WC_Order
	 */
	public WC_Order $order;

	/**
	 * Parameters for API payments.
	 *
	 * @var array
	 */
	public array $parameters = [];

	/**
	 * Our subscriptions
	 *
	 * @var WC_Subscription[]
	 */
	public WC_Stancer_Config $subscriptions;



	/**
	 * Get the order, config and charge value.
	 *
	 * @since unreleased
	 *
	 * @param WC_Order $order The Order that will be payed.
	 * @param WC_Stancer_Config $api_config our configuration.
	 * @param float $charge the amount to be charged (not in cents).
	 */
	public function __construct( WC_Order $order, WC_Stancer_Config $api_config, float $charge ) {
		$this->order = $order;
		$this->api_config = $api_config;
		$this->subscriptions = wcs_get_subscriptions_for_order( $this->order, [ 'order_type' => 'any' ] );
		$this->amount = static::prepare_amount( $charge );
	}

	/**
	 * Build the renewal data from the subscription .
	 *
	 * @since 1.0.0
	 * @since unreleased Moved from `WC_Stancer_Subscription_Trait` to `WC_Stancer_Renewal_Builder`
	 *
	 * @return void
	 * @throws WC_Stancer_Exception No Card linked to the subscription.
	 */
	public function build_payment_data(): void {
		global $wpdb;

		foreach ( $this->subscriptions as $subscription ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `card_id`, `customer_id` FROM `' . $wpdb->prefix . 'wc_stancer_subscription` WHERE `is_active` = 1 AND `subscription_id` = %d;',
					$subscription->get_id(),
				),
			);

			if ( ! $result->card_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new WC_Stancer_Exception( __( 'No card found for this subscription.', 'stancer' ), 7803 );
			}

			$card = new Stancer\Card( $result->card_id );

			if ( $result->customer_id ) {
				$this->parameters['customers'] = new Stancer\Customer( $result->customer_id );
			}

			$description = static::get_valid_description(
				[],
				sprintf(
				// translators: 1. Subscription ID. 2. Current order ID.
					__( 'Renewal payment for subscription n°%1$d, order n°%2$d', 'stancer' ),
					$subscription->get_id(),
					$this->order->get_id(),
				),
				__( 'Renewal payment for your subscription', 'Stancer' ),
			);
			$order_id = (string) $subscription->get_id();
		}
		$this->parameters = [
			'amount' => $this->amount,
			'card' => $card,
			'currency' => $this->order->get_currency(),
			'description' => $description,
			'order_id' => $order_id,
		];
	}

	/**
	 * Create a Stancer\Payment Ready to be send.
	 *
	 * @since 1.0.0
	 * @since unreleased Moved from `WC_Stancer_Subscription_Trait` to `WC_Stancer_Renewal_Builder`
	 *
	 * @return Stancer\Payment|null
	 */
	public function create_api_payment(): ?Stancer\Payment {
		$api_payment = new Stancer\Payment( $this->parameters );
		return $api_payment;
	}
}
