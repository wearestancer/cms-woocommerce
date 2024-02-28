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

use Stancer\Payment\Status;

/**
 * Stancer Refunds traits.
 *
 * @since 1.1.0
 *
 * @package stancer
 * @subpackage stancer/includes/traits
 */
trait WC_Stancer_Refunds_Traits {
	/**
	 * Check if we can refund an order this check is done before showing the stancer button.
	 *
	 * @param WC_Order $order the WooCommerce order to be refunded.
	 * @return boolean
	 */
	public function can_refund_order( $order ) {
		if ( ! $this->api_config->refund || $order->get_payment_method( 'view' ) !== $this->id ) {
			return false;
		}
		if ( ! $order->payment_complete() ) {
			return false;
		}
		if ( $order->get_total() <= $order->get_total_refunded() ) {
			return false;
		}
		$transaction_id = $order->get_transaction_id() ?? null;
		if ( ! $transaction_id ) {
			$api_payment = WC_Stancer_Payment::find( $order );
			$transaction_id = $api_payment->payment_id;
		}
		$api_payment = new Stancer\Payment( $transaction_id );

		// Here we should use a match when we switch to PHP 8.
		switch ( $api_payment->getStatus() ) {
			case Stancer\Payment\Status::TO_CAPTURE
			|| Stancer\Payment\Status::CAPTURED
			|| \Stancer\Payment\Status::CAPTURED:
				return true;
			default:
				return false;
		}
	}

	/**
	 * Process the refund and return the result
	 *
	 * @param int $order_id Order id.
	 * @param float|null $amount the amount of refund asked.
	 * @param string $reason the reason of the refund.
	 *
	 * @throws Exception Throw an exception when they are no proper public and private keys setup.
	 *
	 * @return boolean
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		$wc_order = wc_get_order( $order_id );
		if ( $this->api_config->is_not_configured() ) {
			WC()->session->set( 'stancer_error_payment', __( 'The module is not correctly configured.', 'stancer' ) );
			throw new Exception( esc_html( __( 'The module is not correctly configured', 'stancer' ) ) );
		}

		$stancer_payment = $this->api->send_refund( $wc_order, $amount ? (int) ( $amount * 100 ) : null );
		$refundable = $stancer_payment->getRefundableAmount();
		$currency = $stancer_payment->currency;
		// translators: %1$.2f the amount refunded,  %2s the currency of the payment.
		$text = sprintf( __( 'The refund of %1$.2f %2$s has been completed via Stancer, ', 'stancer' ), $amount, strtoupper( $currency ) );
		if ( 0 !== $refundable ) {
			// translators: %1$.2f the amount total after all the refund, %2$s the currency.
			$text .= sprintf( __( 'the Stancer payment is now equal to %1$.2f %2$s.', 'stancer' ), ( $refundable / 100 ), strtoupper( $currency ) );
		} else {
			$text .= __( 'the order has been fully refunded.', 'stancer' );
		}
		if ( '' !== $reason ) {
			$text .= __( 'the refund was made because:' ) . $reason;
		}

		$wc_order->add_order_note( $text );

		return true;
	}
}
