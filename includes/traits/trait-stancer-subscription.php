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
 * @subpackage stancer/includes/traits
 */

use Stancer\Payment\Status;

/**
 * Stancer subscription.
 *
 * @since 1.1.0
 *
 * @package stancer
 * @subpackage stancer/includes/traits
 */
trait WC_Stancer_Subscription_Trait {
	/**
	 * Format change payment button.
	 *
	 * @since 1.1.0
	 * @param string $base The base HTML button.
	 *
	 * @return string
	 */
	public function format_change_payment_button( string $base ) {
		return str_replace( 'button alt', esc_attr( $this->get_button_classes() ), $base );
	}

	/**
	 * Initialise subscriptions.
	 *
	 * @since 1.1.0
	 */
	public function init_subscription() {
		if ( $this->subscriptions_disabled() ) {
			return;
		}

		$this->supports = array_merge(
			$this->supports,
			[
				'multiple_subscriptions',
				'subscriptions',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_payment_method_change',
				'subscription_payment_method_change_customer',
				'subscription_date_changes',
			],
		);

		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			[
				$this,
				'scheduled_subscription_payment',
			],
			10,
			2
		);
		add_action( 'woocommerce_subscription_status_cancelled', [ $this, 'cancel_subscription' ] );
		add_action( 'woocommerce_subscription_status_expired', [ $this, 'cancel_subscription' ] );

		add_filter( 'woocommerce_change_payment_button_html', [ $this, 'format_change_payment_button' ] );
	}

	/**
	 * Cancel a subscription.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Subscription $subscription An object representing the subscription that just had its status changed.
	 */
	public function cancel_subscription( WC_Subscription $subscription ) {
		global $wpdb;

		$wpdb->update(
			"{$wpdb->prefix}wc_stancer_subscription",
			[
				'is_active' => 0,
			],
			[ 'stancer_subscription_id' => $subscription->get_id() ],
		);
	}

	/**
	 * Register subscription data for renewal.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Order $order The subscription order.
	 * @param WC_Stancer_Payment $stancer_payment The payment used for initiate the subscription.
	 */
	public function register_subscription_data( WC_Order $order, WC_Stancer_Payment $stancer_payment ) {
		global $wpdb;

		if ( $this->subscriptions_disabled() ) {
			return;
		}

		if ( ! wcs_order_contains_subscription( $order ) ) {
			return;
		}

		$subscriptions = wcs_get_subscriptions_for_order( $order );

		foreach ( $subscriptions as $subscription ) {
			$params = [
				'card_id' => $stancer_payment->card_id,
				'customer_id' => $stancer_payment->customer_id,
				'payment_id' => $stancer_payment->payment_id,
				'subscription_id' => $subscription->get_id(),
			];

			$wpdb->insert( "{$wpdb->prefix}wc_stancer_subscription", $params );
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing

	/**
	 * Process a scheduled subscription payment.
	 *
	 * @since 1.1.0
	 *
	 * @param float $charge The amount to charge.
	 * @param WC_Order $order A WC_Order object created to record the renewal payment.
	 *
	 * @return bool|Payment|WP_Error|null
	 */
	public function scheduled_subscription_payment( $charge, WC_Order $order ) {
		global $wpdb;

		try {
			$settings = get_option( 'woocommerce_stancer_settings' );

			// Don't know why, but WC does not find the settings if did not do it myself.
			$wc_config = new WC_Stancer_Config( $settings );

			if ( $wc_config->is_not_configured() ) {
				throw new WC_Stancer_Exception( __( 'The module is not correctly configured.', 'stancer' ), 7804 );
			}

			$amount = WC_Stancer_Api::prepare_amount( $charge );
			$currency = strtolower( $order->get_currency() );

			if ( $amount < 50 ) {
				$message = sprintf(
					// translators: 1: Currency.
					__(
						'In order to utilize this payment method, the minimum required order total is 0.50 %s.',
						'stancer',
					),
					strtoupper( $order->get_currency() ),
				);

				throw new WC_Stancer_Exception( $message, 7801 );
			}

			$subscriptions = wcs_get_subscriptions_for_order( $order, [ 'order_type' => 'any' ] );

			if ( count( $subscriptions ) !== 1 ) {
				throw new WC_Stancer_Exception( __( 'We were unable to locate the subscription.', 'stancer' ), 7802 );
			}

			$api_payment = new Stancer\Payment(
				[
					'amount' => $amount,
					'currency' => $currency,
				],
			);

			foreach ( $subscriptions as $subscription ) {
				$result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT `card_id`, `customer_id` FROM `' . $wpdb->prefix . 'wc_stancer_subscription` WHERE `is_active` = 1 AND `subscription_id` = %d;',
						$subscription->get_id(),
					),
				);

				if ( ! $result->card_id ) {
					throw new WC_Stancer_Exception( __( 'No card found for this subscription.', 'stancer' ), 7803 );
				}

				$api_payment->card = new Stancer\Card( $result->card_id );

				if ( $result->customer_id ) {
					$api_payment->customer = new Stancer\Customer( $result->customer_id );
				}

				$api_payment->description = sprintf(
					// translators: 1. Subscription ID. 2. Current order ID.
					__( 'Renewal payment for subscription n°%1$d, order n°%2$d', 'stancer' ),
					$subscription->get_id(),
					$order->get_id(),
				);
				$api_payment->order_id = (string) $subscription->get_id();
			}

			$api_payment->send();

			if ( null === $api_payment->status ) {
				throw new WC_Stancer_Exception(
					__( 'Something went wrong, the renewal payment is incomplete.', 'stancer' ),
					7805,
				);
			}

			$allowed_status = [
				Status::CAPTURE_SENT,
				Status::CAPTURED,
				Status::TO_CAPTURE,
			];

			if ( in_array( $api_payment->status, $allowed_status, true ) ) {
				// translators: 1: Payment id or transaction id.
				$message = __(
					'Your payment has been successfully processed through Stancer. (Transaction ID: %s)',
					'stancer',
				);
				$order->add_order_note( sprintf( $message, $api_payment->id ) );

				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_complete();
				}

				do_action( 'processed_subscription_payments_for_order', $order );
			} else {
				// translators: 1: Payment status.
				$order->add_order_note( __( 'The payment is not in a valid status (%s).', 'stancer' ) );
				$message = __(
					'We regret to inform you that the payment has been declined. Please consider using an alternative card.',
					'stancer',
				);

				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_failed();
				}

				do_action( 'processed_subscription_payment_failure_for_order', $order );

				throw new WC_Stancer_Exception( $message, 7810 );
			}
		} catch ( Stancer\Exceptions\Exception $error ) {
			// translators: 1: Error code. 2: Error message. 3. Exception name.
			$message = __(
				'The transaction for renewing your subscription has failed. (%3$s: [%1$s] %2$s)',
				'stancer',
			);
			$order->update_status(
				'failed',
				sprintf( $message, $error->getCode(), $error->getMessage(), get_class( $error ) ),
			);

			return false;
		} catch ( WC_Stancer_Exception $error ) {
			// translators: 1: Error code. 2: Error message.
			$message = __( 'The transaction for renewing your subscription has failed. (%1$s: %2$s)', 'stancer' );
			$order->update_status(
				'failed',
				sprintf( $message, $error->getCode(), $error->getMessage() ),
			);

			return false;
		} finally {
			if ( $api_payment ) {
				WC_Stancer_Payment::save_from( $api_payment );
			}
		}

		$order->add_order_note(
			__( 'Your subscription renewal transaction has been successfully submitted.', 'stancer' ),
		);
	}

	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.Missing

	/**
	 * Indicate if subscriptions are not enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function subscriptions_disabled() {
		return ! $this->subscriptions_enabled();
	}

	/**
	 * Indicate if subscriptions are enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function subscriptions_enabled() {
		return class_exists( 'WC_Subscriptions' );
	}
}
