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
 * @subpackage stancer/includes
 */

define( 'DOING_AJAX', true );

header( 'Content-Type: application/json' );

require_once __DIR__ . '/../../../../wp-load.php';

send_origin_headers();
send_nosniff_header();
nocache_headers();

/**
 * Helper to format the card informations.
 *
 * @param Stancer\Payment $payment Payment used.
 */
function create_card_info( Stancer\Payment $payment ): string {
	// translators: $1 Card brand. $2 Last 4. $3 Expiration month. $4 Expiration year.
	$trad = __( '%1$s finishing with %2$s', 'stancer' );
	$card = $payment->card;

	return vsprintf( $trad, [ $card->getBrandName(), $card->last4, $card->exp_month, $card->exp_year ] );
}

/**
 * Helper to send the response.
 *
 * @param array $data Response data.
 */
function send_response( array $data ) {
	echo wp_json_encode( $data );

	exit();
}

$response = [
	'reason' => 'unknown request',
	'result' => 'failed',
];

if ( empty( $_REQUEST['action'] ) || empty( $_REQUEST['subscription'] ) || empty( $_REQUEST['nonce'] ) ) {
	$response['reason'] = 'incomplete request';

	send_response( $response );
}

$subscription_id = (int) $_REQUEST['subscription'];
$nonce_action = 'change-method-' . $subscription_id;

if ( ! wp_verify_nonce( $_REQUEST['nonce'], $nonce_action ) ) {
	$response['reason'] = 'invalid request';

	send_response( $response );
}

$gateway = new WC_Stancer_Gateway();

if ( $gateway->api->api_config->is_not_configured() ) {
	$response['reason'] = 'invalid configuration';

	send_response( $response );
}

$subscription = new WC_Subscription(
	$subscription_id
);
$customer = [
	'first_name' => $subscription->get_billing_first_name(),
	'last_name' => $subscription->get_billing_last_name(),
	'email' => $subscription->get_billing_email(),
	'id' => $subscription->get_customer_id(),
];

$data = [
	'amount' => 0,
	'auth' => true,
	'capture' => false,
	'customer' => WC_Stancer_Customer::get_api_customer( $customer ),
	'currency' => strtolower( $subscription->get_currency() ),
	'order_id' => (string) $subscription->get_id(),
	'return_url' => $subscription->get_checkout_payment_url( true ),
];

$response['order_id'] = $subscription->get_id();
unset( $response['reason'] );

try {
	switch ( $_REQUEST['action'] ) {
		case 'information':
				$response['card'] = $gateway->title;
				$response['result'] = 'success';

			break;
		case 'initiate':
				$payment = WC_Stancer_Payment::find( $subscription, $data, true, [ 'pending' ] );
				$api_payment = new Stancer\Payment( $payment->payment_id );

				$lang = str_replace( '_', '-', get_locale() );

				$response['redirect'] = $api_payment->getPaymentPageUrl( [ 'lang' => $lang ] );
				$response['result'] = 'success';

			break;
		case 'validate':
				$payment = WC_Stancer_Payment::find( $subscription, $data, false, [ 'pending' ] );
				$api_payment = new Stancer\Payment( $payment->payment_id );

				$payment->card_id = $api_payment->card->id;
				$payment->status = $api_payment->status;
				$payment->save();

				$customer = new WC_Customer( $subscription->get_customer_id() );
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
					$response['card'] = create_card_info( $api_payment );
					$response['messages'] = __( 'Payment method changed successfully.', 'stancer' );
					$response['result'] = 'success';
					$subscriptions = WC_Stancer_Subscription::search(
						[
							'is_active' => true,
							'subscription_id' => $subscription->get_id(),
						]
					);

					foreach ( $subscriptions as $sub ) {
						$sub->is_active = false;
						$sub->save();
					}

					$sub = new WC_Stancer_Subscription();

					$sub->is_active = true;
					$sub->subscription_id = $subscription->get_id();
					$sub->payment_id = $api_payment->id;
					$sub->card_id = $api_payment->card->id;
					$sub->customer_id = $api_payment->customer->id;

					$sub->save();
				}

			break;
		default:
			throw new Stancer\Exceptions\Exception( __( 'incorrect action method' ) );
	}
} catch ( Stancer\Exceptions\Exception $exception ) {
	$response['reason'] = $exception->getMessage();
}

send_response( $response );
