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
 * Stancer Refunds traits.
 *
 * @since 1.2.0
 *
 * @package stancer
 * @subpackage stancer/includes/traits
 */
trait WC_Stancer_Refunds_Traits {
	/**
	 * Check if we can refund an order this check is done before showing the stancer button.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Order $order the WooCommerce order to be refunded.
	 * @return boolean
	 */
	public function can_refund_order( $order ) {
		if ( wp_verify_nonce( $_GET['_wpnonce'] ) && 'wc_order' !== $_GET['page'] && 'edit' !== $_GET['action'] ) {
			return false;
		}
		if ( $order->get_payment_method( 'view' ) !== $this->id ) {
			return false;
		}
		if ( 'wc_failed' === $order->get_status( 'view' ) ) {
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

		$status = [
			Stancer\Payment\Status::TO_CAPTURE,
			Stancer\Payment\Status::CAPTURED,
		];
		return in_array( $api_payment->getStatus(), $status, true );
	}

	/**
	 * Process the refund and return the result
	 *
	 * @since 1.2.0
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
		if ( 0 !== $refundable ) {
			$text = sprintf(
				// translators: "%1$.2f": Amount refunded. "%2$s": Currency. "%3$.2f": Total amount after all refunds.
				__(
					'The payment has been partially refunded of %1$.2f %2$s, the payment is now of: %3$.2f %2$s.',
					'stancer',
				),
				$amount,
				strtoupper( $currency ),
				( $refundable / 100 )
			);
		} else {
			$text = sprintf(
				// translators: "%1$.2f": the amount refunded. "%2$s": the currency.
				__( 'The payment has been fully refunded of %1$.2f %2$s via Stancer.', 'stancer' ),
				$amount,
				strtoupper( $currency )
			);
		}

		$wc_order->add_order_note( $text );

		if ( '' !== $reason ) {
			// translators: "%1$s": the reason for the refund process.
			$wc_order->add_order_note( sprintf( __( 'Reason for refund: %1$s', 'stancer' ), $reason ) );
		}

		return true;
	}
}
