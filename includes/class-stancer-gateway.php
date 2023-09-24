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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stancer gateway.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Gateway extends WC_Payment_Gateway {

	/**
	 * Stancer configuration.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_Stancer_Config
	 */
	public $api_config;

	/**
	 * Stancer API.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_Stancer_Api
	 */
	public $api;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id = 'stancer';
		$this->icon = apply_filters( 'stancer_icon', '' );
		$this->has_fields = true;
		$this->method_title = 'Stancer';
		$this->method_description = __( 'Simple payment solution at low prices.', 'stancer' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'payment_option_text' );
		$this->description = $this->get_option( 'payment_option_description' );
		$this->api_config = new WC_Stancer_Config( $this->settings );
		$this->api = new WC_Stancer_Api( $this->api_config );

		// Add message on checkout.
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_notice' ] );

		// Action on payment return.
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );

		// Add our gateway on checkout button.
		add_filter( 'woocommerce_order_button_html', [ $this, 'place_order_button_html' ] );

		// Update settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Create payment.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @param string|null $card_id Card identifier.
	 *
	 * @return array
	 */
	public function create_api_payment( $order, $card_id = null ) {
		$redirect = $order->get_checkout_payment_url( true );
		$api_payment = $this->api->send_payment( $order, $card_id );

		if ( $api_payment && $api_payment->return_url ) {
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
	 * Generate the payment option logo selector.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @return string
	 */
	public function generate_payment_option_logo_html( $key, $data ) {
		$current_value = $this->get_option( $key );
		$option_name = $this->get_field_key( $key );
		$defaults = [
			'desc_tip' => false,
			'description' => '',
			'title' => '',
		];
		$data = wp_parse_args( $data, $defaults );

		$images = [
			'no-logo' => __( 'No logo.', 'stancer' ),
			'stancer' => __( 'Stancer logo.', 'stancer' ),
			'visa-mc-prefixed' => __( 'Main schemes logos prefixed with Stancer logo.', 'stancer' ),
			'visa-mc' => __( 'Main schemes logos.', 'stancer' ),
			'visa-mc-suffixed' => __( 'Main schemes logos suffixed with Stancer logo.', 'stancer' ),
			'visa-mc-stancer' => __( 'Main schemes logos with full Stancer logo.', 'stancer' ),
			'all-schemes-prefixed' => __( 'Every supported schemes logos prefixed with Stancer logo.', 'stancer' ),
			'all-schemes' => __( 'Every supported schemes logos.', 'stancer' ),
			'all-schemes-suffixed' => __( 'Every supported schemes logos suffixed with Stancer logo.', 'stancer' ),
			'all-schemes-stancer' => __( 'Every supported schemes logos with full Stancer logo.', 'stancer' ),
		];

		$template = [
			'<tr class="titledesc stancer-admin">',
			'<th scope="row" class="titledesc stancer-admin__header">',
			'<span class="stancer-admin__label">',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_kses_post( $data['title'] ),
			$this->get_tooltip_html( $data ),
			'</span>',
			'</th>',
			'<td class="forminp stancer-admin__form-control">',
		];

		foreach ( $images as $image => $text ) {
			$input_id = esc_html( $option_name . '-' . $image );

			$template[] = '<input
				class="stancer-admin__radio"
				name="' . esc_html( $option_name ) . '"
				type="radio"
				id="' . $input_id . '"
				value="' . esc_html( $image ) . '"
				' . ( $current_value === $image ? 'checked' : '' ) . '
			/>';

			$class = [
				'stancer-admin__label',
				'stancer-admin__label--' . $image,
			];

			$template[] = '<label class="' . implode( ' ', $class ) . '" for="' . $input_id . '">';

			if ( 'no-logo' !== $image ) {
				$image_path = plugin_dir_url( STANCER_FILE ) . 'public/svg/symbols.svg#' . $image;

				$class = [
					'stancer-admin__preview',
					'stancer-admin__preview--' . $image,
				];

				$template[] = '<img class="' . implode( ' ', $class ) . '" src="' . esc_html( $image_path ) . '" />';
			}

			$template[] = $text;
			$template[] = '</label>';
		}

		$template[] = '</td>';
		$template[] = '</tr>';

		return implode( '', $template );
	}

	/**
	 * Return the list of needed configurations.
	 *
	 * @since 1.0.0
	 *
	 * @return array
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
	 * Init settings page.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 New page type `pip`.
	 * @since 1.1.0 New payment description.
	 * @since 1.1.0 Allow to choose scheme logos.
	 *
	 * @return self
	 */
	public function init_form_fields() {
		$inputs = [];

		$inputs['enabled'] = [
			'default' => 'yes',
			'label' => __( 'Enable Stancer', 'stancer' ),
			'title' => __( 'Enable/Disable', 'stancer' ),
			'type' => 'checkbox',
		];

		$inputs['payment_option_text'] = [
			'default' => __( 'Pay by card', 'stancer' ),
			'desc_tip' => __( 'Payment method title shown to the customer during checkout.', 'stancer' ),
			'title' => __( 'Title', 'stancer' ),
			'type' => 'text',
		];

		$inputs['payment_option_description'] = [
			'desc_tip' => __( 'Payment method description shown to the customer during checkout.', 'stancer' ),
			'title' => __( 'Payment option description', 'stancer' ),
			'type' => 'text',
		];

		$inputs['payment_option_logo'] = [
			'default' => 'all-schemes-stancer',
			'desc_tip' => __( 'Card logos displayed to the customer during checkout.', 'stancer' ),
			'title' => __( 'Payment option logos', 'stancer' ),
			'type' => 'payment_option_logo',
		];

		$inputs['authentication_title'] = [
			'title' => __( 'Authentication', 'stancer' ),
			'type' => 'title',
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
			'default' => 'yes',
			'description' => __(
				'In test mode, no payment will really send to a bank, only test card can be used.',
				'stancer',
			),
			'label' => __( 'Enable test mode', 'stancer' ),
			'title' => __( 'Test mode', 'stancer' ),
			'type' => 'checkbox',
		];

		$inputs['behavior_title'] = [
			'title' => __( 'Behavior', 'stancer' ),
			'type' => 'title',
		];

		$inputs['page_type'] = [
			'default' => 'iframe',
			'label' => __( 'Page type', 'stancer' ),
			'title' => __( 'Page type', 'stancer' ),
			'options' => [
				'iframe' => __( 'Popup', 'stancer' ),
				'pip' => __( 'Inside the page', 'stancer' ),
				'redirect' => __( 'Redirect to an external page', 'stancer' ),
			],
			'type' => 'select',
		];

		$desc_auth_limit = __(
			'Minimum amount to trigger an authenticated payment (3DS, Verified by Visa, Mastercard Secure Code...).',
			'stancer',
		);
		$desc_auth_limit .= '<br/>';
		$desc_auth_limit .= __(
			'Leave blank if you do not wish to authenticate, at zero all payments will be authenticated.',
			'stancer',
		);

		$inputs['auth_limit'] = [
			'default' => '0',
			'title' => __( 'Authentication limit', 'stancer' ),
			'type' => 'text',
			'description' => $desc_auth_limit,
		];

		$desc_description = __(
			'Will be used as description for every payment made, and will be visible to your customer in redirect mode.',
			'stancer',
		);
		$desc_description .= ' ';
		$desc_description .= __( 'List of available variables:', 'stancer' );
		$desc_description .= '<br/>';

		$vars = [
			'SHOP_NAME' => __( 'Shop name configured in WooCommerce', 'stancer' ),
			'TOTAL_AMOUNT' => __( 'Total amount', 'stancer' ),
			'CURRENCY' => __( 'Currency of the order', 'stancer' ),
			'CART_ID' => __( 'Cart identifier', 'stancer' ),
		];

		foreach ( $vars as $key => $value ) {
			$desc_description .= '<b>' . $key . '</b> : ' . $value . '';
			$desc_description .= '<br/>';
		}

		$inputs['payment_description'] = [
			'default' => __( 'Your order SHOP_NAME', 'stancer' ),
			'title' => __( 'Description', 'stancer' ),
			'type' => 'text',
			'description' => $desc_description,
		];

		$inputs['host'] = [
			'default' => '',
			'type' => 'hidden',
		];

		$inputs['timeout'] = [
			'default' => 0,
			'type' => 'hidden',
		];

		wp_enqueue_style(
			'stancer-admin',
			plugin_dir_url( STANCER_FILE ) . 'public/css/admin.min.css',
			[],
			STANCER_ASSETS_VERSION,
		);

		$this->form_fields = apply_filters( 'stancer_form_fields', $inputs );

		return $this;
	}

	/**
	 * Define if gateway is available.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! is_woocommerce_activated() ) {
			return false;
		}

		return $this->api_config->is_configured();
	}

	/**
	 * Display payment fields.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Add logos and description.
	 */
	public function payment_fields() {
		$logo = $this->settings['payment_option_logo'] ?? 'no-logo';

		if ( 'no-logo' !== $logo ) {
			wp_enqueue_style(
				'stancer-option',
				plugin_dir_url( STANCER_FILE ) . 'public/css/option.min.css',
				[],
				STANCER_ASSETS_VERSION,
			);

			$image_path = plugin_dir_url( STANCER_FILE ) . 'public/svg/symbols.svg#' . $logo;

			$class = [
				'stancer-option__logo',
				'stancer-option__logo--' . $logo,
			];

			echo wp_kses_post( '<img class="' . implode( ' ', $class ) . '" src="' . esc_html( $image_path ) . '" />' );
		}

		echo esc_html( $this->settings['payment_option_description'] );

		switch ( $this->settings['page_type'] ) {
			case 'iframe':
				wp_enqueue_script(
					'stancer-popup',
					plugin_dir_url( STANCER_FILE ) . 'public/js/popup.min.js',
					[],
					STANCER_ASSETS_VERSION,
					true,
				);
				break;

			case 'pip':
				wp_enqueue_script(
					'stancer-iframe',
					plugin_dir_url( STANCER_FILE ) . 'public/js/iframe.min.js',
					[],
					STANCER_ASSETS_VERSION,
					true,
				);
				wp_enqueue_style(
					'stancer-iframe',
					plugin_dir_url( STANCER_FILE ) . 'public/css/iframe.min.css',
					[],
					STANCER_ASSETS_VERSION,
				);
				break;

			default:
				echo esc_html__( 'You will be redirected to our partner\'s portal to make the payment.', 'stancer' );
		}
	}

	/**
	 * Return place order button.
	 *
	 * @since 1.0.0
	 *
	 * @return string
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

		return [
			'result' => $result['redirect'] ? 'success' : 'failed',
			'redirect' => $result['redirect'],
		];
	}

	/**
	 * Complete order.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$settings = get_option( 'woocommerce_stancer_settings' );

		// Don't know why, but WC does not find the settings if did not do it myself.
		$wc_config = new WC_Stancer_Config( $settings );

		if ( $wc_config->is_not_configured() ) {
			$order->update_status( 'failed' );

			WC()->session->set( 'stancer_error_payment', __( 'The module is not correctly configured.', 'stancer' ) );
			wp_safe_redirect( wc_get_checkout_url() );

			exit();
		}

		$customer = new WC_Customer( $order->get_customer_id() );
		$stancer_payment = WC_Stancer_Payment::get_payment( $order );
		$api_payment = new Stancer\Payment( $stancer_payment->payment_id );

		$auth = $api_payment->auth;
		$api_card = $api_payment->card;
		$status = $api_payment->status;

		if ( $auth ) {
			if ( Stancer\Auth\Status::SUCCESS !== $auth->status ) {
				// We can not mark the payment failed in the API.
				$status = Stancer\Payment\Status::FAILED;
			}
		} else {
			if ( ! $status && $api_card ) {
				$status = Stancer\Payment\Status::CAPTURE;
			}
		}

		if ( ! empty( $status ) ) {
			$stancer_payment->mark_as( $status );
		}

		switch ( $status ) {
			case Stancer\Payment\Status::FAILED:
			case Stancer\Payment\Status::REFUSED:
				$order->update_status( 'failed' );

				WC()->session->set( 'stancer_error_payment', __( 'The payment attempt failed.', 'stancer' ) );
				wp_safe_redirect( wc_get_checkout_url() );

				break;
			case Stancer\Payment\Status::AUTHORIZED:
				$api_payment->status = Stancer\Payment\Status::CAPTURE;
				$api_payment->send();

				// No break, we just need to ask for the capture and leave the "capture" part creating the order.

			case Stancer\Payment\Status::TO_CAPTURE:
			case Stancer\Payment\Status::CAPTURE:
				// Save card.
				WC_Stancer_Card::save_from( $api_card, $customer );

				$stancer_payment->card_id = $api_card->id;
				$stancer_payment->save();

				// Remove cart.
				WC()->cart->empty_cart();

				// Complete order.
				$order->payment_complete();

				$order->add_order_note(
					sprintf(
						// translators: %s: Stancer payment identifier.
						__( 'Payment was completed via Stancer (Transaction ID: %s)', 'stancer' ),
						$api_payment->getId()
					)
				);
				$order->set_transaction_id( $api_payment->getId() );

				wp_safe_redirect( $this->get_return_url( $order ) );

				break;
			default:
				wp_safe_redirect( wc_get_checkout_url() );
		}
	}
}
