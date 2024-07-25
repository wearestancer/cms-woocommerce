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
 * @subpackage stancer/includes/traits
 */

use Stancer;

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
		$default_classes = [
			'button',
			'alt',
			wc_wp_theme_get_element_class_name( 'button' ),
			'js-stancer-change-payment-method',
		];

		return str_replace( 'button alt', esc_attr( implode( ' ', $default_classes ) ), $base );
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

		try {
			$settings = get_option( 'woocommerce_stancer_settings' );

			// Don't know why, but WC does not find the settings if did not do it myself.
			$wc_config = new WC_Stancer_Config( $settings );

			if ( $wc_config->is_not_configured() ) {
				throw new WC_Stancer_Exception( __( 'The module is not correctly configured.', 'stancer' ), 7804 );
			}

			$renewal_builder = new WCS_Stancer_Renewal_Builder( $order, $wc_config, $charge );
			$renewal_builder->build_payment_data();
			$api_payment = $renewal_builder->create_api_payment();
			$api_payment->send();

			if ( $api_payment->amount < 50 ) {
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

			$subscriptions = wcs_get_subscriptions_for_order( $this->order, [ 'order_type' => 'any' ] );

			if ( count( $subscriptions ) !== 1 ) {
				throw new WC_Stancer_Exception( __( 'We were unable to locate the subscription.', 'stancer' ), 7802 );
			}

			if ( null === $api_payment->status ) {
				throw new WC_Stancer_Exception(
					__( 'Something went wrong, the renewal payment is incomplete.', 'stancer' ),
					7805,
				);
			}

			$allowed_status = [
				Stancer\Payment\Status::CAPTURE_SENT,
				Stancer\Payment\Status::CAPTURED,
				Stancer\Payment\Status::TO_CAPTURE,
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
