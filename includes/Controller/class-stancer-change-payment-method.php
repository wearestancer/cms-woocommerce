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
 * ChangePayment Controller used to change payment with WP API.
 * For now the controller is not restfull and the request are more SOAP.
 *
 * @since 1.3.0
 */
class WCS_Stancer_Change_Payment_Method extends WP_REST_Controller {

	public const API_VERSION = 1;
	/**
	 * Our Payment Gateway
	 *
	 * @since 1.3.0
	 *
	 * @var WC_Stancer_Gateway
	 */
	public WC_Stancer_Gateway $gateway;

	/**
	 * Our Subscription.
	 *
	 * @since 1.3.0
	 *
	 * @var WC_Subscription
	 */
	public WC_Subscription $subscription;

	/**
	 * Instanciation method.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		$this->gateway = new WC_Stancer_Gateway();
	}

	/**
	 * Register Routes to be called by our frontend.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function register_routes() {
		$namespace = 'stancer/v' . static::API_VERSION;
		$base = 'change_payment_method';
		register_rest_route(
			$namespace,
			'/' . $base . '/information',
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [ $this , 'create_change_payment_information' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			],
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/initiate',
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [ $this , 'create_change_payment_initiate' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			],
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/validate',
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [ $this , 'create_change_payment_validate' ],
				'permission_callback' => [ $this, 'validate_permission' ],
			],
		);
	}

	/**
	 * Handle an Information request.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function create_change_payment_information(): array {

		$response['card'] = $this->gateway->title;
		$response['result'] = 'success';
		return $response;
	}

	/**
	 * Handle an Initiation request.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function create_change_payment_initiate(): array {
		$data = $this->get_payment_data( $this->subscription );
		$payment = WC_Stancer_Payment::find( $this->subscription, $data, true, [ 'pending' ] );
		$api_payment = new Stancer\Payment( $payment->payment_id );
		$lang = str_replace( '_', '-', get_locale() );

		$response['redirect'] = $api_payment->getPaymentPageUrl( [ 'lang' => $lang ] );
		$response['result'] = 'success';
		return $response;
	}

	/**
	 * Handle a Validation request.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function create_change_payment_validate(): array {
		$data = $this->get_payment_data( $this->subscription );
		$payment = WC_Stancer_Payment::find( $this->subscription, $data, false, [ 'pending' ] );
		$api_payment = new Stancer\Payment( $payment->payment_id );

		$payment->card_id = $api_payment->card->id;
		$payment->status = $api_payment->status;
		$payment->save();

		$customer = new WC_Customer( $this->subscription->get_customer_id() );
		WC_Stancer_Card::save_from( $api_payment->card, $customer );

		$valid_status = [
			Stancer\Payment\Status::AUTHORIZED,
			Stancer\Payment\Status::CAPTURE_SENT,
			Stancer\Payment\Status::CAPTURED,
			Stancer\Payment\Status::TO_CAPTURE,
		];

		if ( ! in_array( $api_payment->status, $valid_status, true ) ) {
			$response['messages'] = __( 'This method has not been validated. Please try a new one.', 'stancer' );
		} else {
			$response['card'] = $this->create_card_info( $api_payment );
			$response['messages'] = __( 'Payment method changed successfully.', 'stancer' );
			$response['result'] = 'success';
			$subscriptions = WC_Stancer_Subscription::search(
				[
					'is_active' => true,
					'subscription_id' => $this->subscription->get_id(),
				]
			);

			foreach ( $subscriptions as $sub ) {
				$sub->is_active = false;
				$sub->save();
			}

			$sub = new WC_Stancer_Subscription();

			$sub->is_active = true;
			$sub->subscription_id = $this->subscription->get_id();
			$sub->payment_id = $api_payment->id;
			$sub->card_id = $api_payment->card->id;
			$sub->customer_id = $api_payment->customer->id;

			$sub->save();
		}
		return $response;
	}



	/**
	 * Get the payment data from the current subscription.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function get_payment_data(): array {
		$customer = [
			'first_name' => $this->subscription->get_billing_first_name(),
			'last_name' => $this->subscription->get_billing_last_name(),
			'email' => $this->subscription->get_billing_email(),
			'id' => $this->subscription->get_customer_id(),
		];
		return [
			'amount' => 0,
			'auth' => true,
			'capture' => false,
			'customer' => WC_Stancer_Customer::get_api_customer( $customer ),
			'currency' => strtolower( $this->subscription->get_currency() ),
			'order_id' => (string) $this->subscription->get_id(),
			'return_url' => $this->subscription->get_checkout_payment_url( true ),
		];
	}

	/**
	 * Helper to format the card informations.
	 *
	 * @since 1.3.0
	 *
	 * @param Stancer\Payment $payment Payment used.
	 *
	 * @return string
	 */
	public function create_card_info( Stancer\Payment $payment ): string {
		// translators: "%1$s": Card brand. "%2$d" Last 4.
		$trad = __( '%1$s finishing with %2$s', 'stancer' );
		$card = $payment->card;

		return vsprintf( $trad, [ $card->getBrandName(), $card->last4 ] );
	}

	/**
	 * Validate the permission( notably the nonce token) to make sure to process the change payment method.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_REST_Request $request the request to be treated by our controller.
	 * @return boolean|WP_error
	 */
	public function validate_permission( WP_REST_Request $request ): bool {
		if ( $this->gateway->api->api_config->is_not_configured() ) {
			return false;
		}

		if ( empty( $request['subscription'] ) || empty( $request['nonce'] ) ) {
			return false;
		}
		$nonce_action = 'change-method-' . $request['subscription'];

		if ( ! wp_verify_nonce( $request['nonce'], $nonce_action ) ) {
			return false;
		}
		if ( 0 === get_current_user_id() ) {
			return false;
		}
		$this->subscription = new WC_Subscription( (int) $request['subscription'] );

		return true;
	}
}
