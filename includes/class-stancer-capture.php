<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com/
 * @license MIT
 * @copyright 2023-2026 Stancer / Iliad 78
 *
 * @package stancer
 * @subpackage stancer/includes
 */

use Stancer;

/**
 * Service class for Order Capture
 *
 * @since 1.4.2
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Capture {

	/**
	 * Order linked to the authorized payment.
	 *
	 * @since 1.4.2
	 * @var WC_Order
	 */
	private WC_Order $order;

	/**
	 * Stored locally payment data.
	 *
	 * @since 1.4.2
	 * @var WC_Stancer_Payment
	 */
	private WC_Stancer_Payment $payment;

	/**
	 * Api payment.
	 *
	 * @since 1.4.2
	 * @var Stancer\Payment
	 */
	private Stancer\Payment $api_payment;

	/**
	 * Constructor
	 *
	 * @since 1.4.2
	 *
	 * @param WC_Order|integer $order The order displayed.
	 *
	 * @throws Stancer\Exceptions\InvalidArgumentException If we don't have a stancer payment linked to the Order.
	 * @throws Stancer\Exceptions\NotAuthorizedException If we don't have a config properly set up.
	 */
	public function __construct( WC_Order|int $order ) {
		if ( is_int( $order ) ) {
			$this->order = wc_get_order( $order );
		} else {
			$this->order = $order;
		}
		// Find the last payment created for this order.
		$payment = WC_Stancer_Payment::find( $this->order, [], false, [], 'created', true );
		if ( ! $payment ) {
			throw new Stancer\Exceptions\InvalidArgumentException(
				esc_html__( 'This order is not associated with a Stancer payment.', 'stancer' )
			);
		}
		$this->payment = $payment;
		$gateway = new WC_Stancer_Gateway();
		if ( ! $gateway->api_config->is_configured() ) {
			throw new Stancer\Exceptions\NotAuthorizedException(
				esc_html__(
					'The module is not correctly configured.',
					'stancer',
				)
			);
		}
		$this->api_payment = new Stancer\Payment( $payment->payment_id );
	}

	/**
	 * Display the payment's status, and maybe the capture button.
	 *
	 * @since 1.4.2
	 *
	 * @return void
	 */
	public function maybe_display_capture() {
		$this->payment->mark_as( $this->api_payment->get_status()->value );
		wp_enqueue_style(
			'stancer-order-style',
			plugin_dir_url( STANCER_FILE ) . 'public/css/order.min.css',
			[],
			STANCER_ASSETS_VERSION,
		);
		printf(
			'<mark class="tips order-status %1$s stancer-status"><span>%2$s</span></mark>',
			esc_attr( 'stancer-' . $this->payment->status ),
			esc_html( $this->payment->status )
		);

		if ( Stancer\Payment\Status::AUTHORIZED !== $this->api_payment->get_status() ) {
			if ( $this->order->get_status() === 'on-hold' ) {
				$this->complete_payment_if_captured();
			}
			return;
		}

		$nonce = wp_create_nonce( 'stancer_capture' );
		$get_data = build_query(
			[
				'nonce' => $nonce,
				'action' => 'stancer_capture',
				'order_id' => $this->order->get_id(),
			]
		);
		printf(
			'
			<div class="capture-stancer-block">
			<a href="%1$s" class="button capture-stancer">%2$s</a>
			</div>
		',
			esc_url( admin_url( 'admin-post.php' ) . '?' . $get_data ),
			esc_html__( 'Capture the payment', 'stancer' )
		);
	}

	/**
	 * Handle a capture payment request.
	 *
	 * @since 1.4.2
	 *
	 * @return void
	 */
	public function capture_authorize_payment() {

		$this->api_payment->capture();
		$this->complete_payment_if_captured();
		$this->payment->mark_as( $this->api_payment->get_status()->value );
	}

	/**
	 * If payment is complete say so in WooCommerce BackOffice
	 *
	 * @since 1.4.2
	 *
	 * @return bool
	 */
	public function complete_payment_if_captured() {
		if ( in_array(
			$this->api_payment->status,
			[
				Stancer\Payment\Status::CAPTURE_SENT,
				Stancer\Payment\Status::CAPTURED,
				Stancer\Payment\Status::TO_CAPTURE,
			],
			true
		)
		) {
			return WC_Stancer_Payment_Builder::complete_payment( $this->order, $this->api_payment );
		}
		return false;
	}
}
