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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stancer gateway
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Gateway extends WC_Payment_Gateway {

	/**
	 * Stancer configuration
	 *
	 * @since 1.0.0
	 * @var WC_Stancer_Config
	 */
	public $api_config;

	/**
	 * Stancer API
	 *
	 * @since 1.0.0
	 * @var WC_Stancer_Api
	 */
	public $api;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id                 = 'stancer';
		$this->icon               = apply_filters( 'stancer_icon', '' );
		$this->has_fields         = true;
		$this->method_title       = 'Stancer';
		$this->method_description = __( 'Simple payment solution at low prices.', 'stancer' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->api_config  = new WC_Stancer_Config( $this->settings );
		$this->api         = new WC_Stancer_Api( $this->api_config );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_notice' ] );

		// Filters.
		add_filter( 'woocommerce_order_button_html', [ $this, 'place_order_button_html' ] );
	}

	/**
	 * Create payment
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order    $order    Order.
	 * @param string|null $card_id  Card identifier.
	 */
	public function create_api_payment( $order, $card_id = null ) {
		$redirect = $order->get_checkout_payment_url( true );
		$api_payment = $this->api->send_payment( $order, $card_id );

		if ( $api_payment && $api_payment->getReturnUrl() ) {
			$redirect = $api_payment->getPaymentPageUrl(
				[
					'lang' => 'fr',
				]
			);
		}

		return [
			'redirect' => $redirect,
		];
	}

	/**
	 * Display notice when payment failed.
	 *
	 * @since 1.0.0
	 */
	public function display_notice() {
		$notice = WC()->session->get( 'stancer_error_payment' );

		if ( ! empty( $notice ) ) {
			wc_add_notice( $notice, 'error' );
			WC()->session->set( 'stancer_error_payment', null );
		}
	}

	/**
	 * Return the list of needed configurations
	 *
	 * @since 1.0.0
	 */
	public function get_configurations() {
		// translators: %s: Key prefixes (aka sprod, pprod, stest or ptest).
		$desc = __( 'Starts with "%s"', 'stancer' );

		return [
			'api_live_public_key' => [
				'description' => sprintf( $desc, 'pprod_' ),
				'pattern' => 'pprod_',
				'title' => __( 'Public live API key', 'stancer' ),
			],
			'api_live_secret_key' => [
				'description' => sprintf( $desc, 'sprod_' ),
				'pattern' => 'sprod_',
				'title' => __( 'Secret live API key', 'stancer' ),
			],
			'api_test_public_key' => [
				'description' => sprintf( $desc, 'ptest_' ),
				'pattern' => 'ptest_',
				'title' => __( 'Public test API key', 'stancer' ),
			],
			'api_test_secret_key' => [
				'description' => sprintf( $desc, 'stest_' ),
				'pattern' => 'stest_',
				'title' => __( 'Secret test API key', 'stancer' ),
			],
		];
	}

	/**
	 * Init settings page
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$inputs = [];

		$inputs['title'] = [
			'default' => __( 'Pay by card', 'stancer' ),
			'title'   => __( 'Title', 'stancer' ),
			'type'    => 'text',
		];

		$inputs['enabled'] = [
			'default' => 'yes',
			'label'   => __( 'Enable Stancer', 'stancer' ),
			'title'   => __( 'Enable/Disable', 'stancer' ),
			'type'    => 'checkbox',
		];

		$inputs['api'] = [
			'title' => __( 'Settings', 'stancer' ),
			'type'  => 'title',
		];

		foreach ( $this->get_configurations() as $key => $conf ) {
			$inputs[ $key ] = array_merge(
				$conf,
				[
					'type' => 'text',
				]
			);
		}

		$inputs['test_mode'] = [
			'default'     => 'yes',
			'description' => __( 'In test mode, no payment will really send to a bank, only test card can be used.', 'stancer' ),
			'label'       => __( 'Enable test mode', 'stancer' ),
			'title'       => __( 'Test mode', 'stancer' ),
			'type'        => 'checkbox',
		];

		$inputs['page_type'] = [
			'default'      => 'iframe',
			'label'        => __( 'Page type', 'stancer' ),
			'title'        => __( 'Page type', 'stancer' ),
			'options'      => [
				'iframe'   => __( 'Popup', 'stancer' ),
				'redirect' => __( 'Redirect to an external page', 'stancer' ),
			],
			'type'         => 'select',
		];

		$desc_auth_limit = __( 'Minimum amount to trigger an authenticated payment (3DS, Verified by Visa, Mastercard Secure Code...).', 'stancer' );
		$desc_auth_limit .= '<br/>';
		$desc_auth_limit .= __( 'Leave blank if you do not wish to authenticate, at zero all payments will be authenticated.', 'stancer' );

		$inputs['auth_limit'] = [
			'default'     => '0',
			'title'       => __( 'Authentication limit', 'stancer' ),
			'type'        => 'text',
			'description' => $desc_auth_limit,
		];

		$desc_description = __( 'Will be used as description for every payment made, and will be visible to your customer in redirect mode.', 'stancer' );
		$desc_description .= ' ';
		$desc_description .= __( 'List of available variables:', 'stancer' );
		$desc_description .= '<br/>';

		$vars = [
			'SHOP_NAME'    => __( 'Shop name configured in Woocommerce', 'stancer' ),
			'TOTAL_AMOUNT' => __( 'Total amount', 'stancer' ),
			'CURRENCY'     => __( 'Currency of the order', 'stancer' ),
			'CART_ID'      => __( 'Cart identifier', 'stancer' ),
		];

		foreach ( $vars as $key => $value ) {
			$desc_description .= '<b>' . $key . '</b> : ' . $value . '';
			$desc_description .= '<br/>';
		}

		$inputs['description'] = [
			'default'     => __( 'Your order SHOP_NAME', 'stancer' ),
			'title'       => __( 'Description', 'stancer' ),
			'type'        => 'text',
			'description' => $desc_description,
		];

		$inputs['host'] = [
			'default' => '',
			'type'    => 'hidden',
		];

		$inputs['timeout'] = [
			'default' => 1,
			'type'    => 'hidden',
		];

		$this->form_fields = apply_filters( 'stancer_form_fields', $inputs );

		return $this;
	}

	/**
	 * Define if gateway is available
	 *
	 * @since 1.0.0
	 */
	public function is_available() {
		if ( ! is_woocommerce_activated() ) {
			return false;
		}

		return $this->api_config->is_configured();
	}

	/**
	 * Display payment fields
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		$customer = WC()->customer;
		$cards = [];
		if ( $customer ) {
			$cards = WC_Stancer_Card::get_customer_cards( $customer );
		}

		include STANCER_DIRECTORY_PATH . 'includes/views/class-stancer-payment-fields.php';
	}

	/**
	 * Return place order button
	 *
	 * @since 1.0.0
	 */
	public function place_order_button_html() {
		$order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );

		$default_classes = [
			'button',
			'alt',
			wc_wp_theme_get_element_class_name( 'button' ),
			'js-stancer-place-order',
		];

		$attrs = [
			'class="' . esc_attr( implode( ' ', $default_classes ) ) . '"',
			'id="place_order"',
			'name="woocommerce_checkout_place_order"',
			'value="' . esc_attr( $order_button_text ) . '"',
			'type="submit"',
			'data-value="' . esc_attr( $order_button_text ) . '"',
		];
		$button = '<button ' . implode( ' ', $attrs ) . '>' . esc_html( $order_button_text ) . '</button>';

		return $button;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$card_id = filter_input( INPUT_POST, 'stancer-card', FILTER_SANITIZE_STRING );
		$result = $this->create_api_payment( $order, $card_id );

		return array(
			'result'   => $result['redirect'] ? 'success' : 'failed',
			'redirect' => $result['redirect'],
		);
	}

	/**
	 * Complete order.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$customer = new WC_Customer( $order->get_customer_id() );
		$stancer_payment = WC_Stancer_Payment::get_payment( $order );
		$api_payment = WC_Stancer_Payment::get_api_payment( $stancer_payment );

		$auth = $api_payment->getAuth();
		$api_card = $api_payment->getCard();
		$status = $api_payment->getStatus();

		if ( $auth ) {
			if ( $auth->getStatus() !== Stancer\Auth\Status::SUCCESS ) {
				// We can not mark the payment failed in the API.
				$status = Stancer\Payment\Status::FAILED;
			}
		} else {
			if ( ! $status && $api_card ) {
				$status = Stancer\Payment\Status::CAPTURE;
			}
		}

		if ( ! empty( $status ) ) {
			$stancer_payment->markAs( $status );
		}

		switch ( $status ) {
			case Stancer\Payment\Status::FAILED:
			case Stancer\Payment\Status::REFUSED:
				$order->update_status( 'failed' );

				WC()->session->set( 'stancer_error_payment', __( 'The payment attempt failed.', 'stancer' ) );
				wp_safe_redirect( wc_get_checkout_url() );

				exit;
			case Stancer\Payment\Status::AUTHORIZED:
			case Stancer\Payment\Status::TO_CAPTURE:
			case Stancer\Payment\Status::CAPTURE:
				// Save card.
				// @todo : remove check of property when property deleted will be added.
				$deleted = property_exists( $api_card, 'deleted' ) && $api_card->deleted ?? false;
				if ( $deleted ) {
					WC_Stancer_Card::delete_from( $api_card );
				} else {
					WC_Stancer_Card::save_from( $api_card, $customer );
				}

				// Remove cart.
				WC()->cart->empty_cart();

				// Complete order.
				$order->payment_complete();
				// translators: %s: Stancer payment identifier.
				$order->add_order_note( sprintf( __( 'Payment was completed via Stancer (Transaction ID: %s)', 'stancer' ), $api_payment->getId() ) );
				$order->set_transaction_id( $api_payment->getId() );

				wp_safe_redirect( $this->get_return_url( $order ) );

				exit;
			default:
				wp_safe_redirect( wc_get_checkout_url() );

				exit;
		}
	}
}
