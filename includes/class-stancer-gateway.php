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
	use WC_Stancer_Refunds_Traits;
	use WC_Stancer_Subscription_Trait;

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
		$this->supports = [
			'payments',
			'refunds',
		];

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

		$this->dynamic_title();
		$this->init_subscription();

		add_action( 'admin_notices', [ $this, 'display_error_key' ] );
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
		$reload = true;

		$api_payment = $this->api->send_payment( $order, $card_id );

		if ( $api_payment && $api_payment->return_url ) {
			$order->set_transaction_id( $api_payment->getId() );
			$redirect = $api_payment->getPaymentPageUrl(
				[
					'lang' => str_replace( '_', '-', get_locale() ),
				]
			);
			$reload = false;
		}

		return [
			'redirect' => $redirect,
			'reload' => $reload,
			'result' => $reload ? 'failed' : 'success',
		];
	}

	/**
	 * Display notices if the key are not properly setup.
	 * The way this hook work highly displeases me but the new function exist only since WordPress 6.4.
	 *
	 * @return void
	 */
	public function display_error_key() {

		if ( $this->api_config->is_configured() ) {
			return;
		}
		$page = $_GET['page'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mode = $this->api_config->mode;
		$is_setting_page = ( ! is_null( $page ) && 'wc-settings' === $page );
		// translators: "%1$s": the mode in which our API is (test mode or Live mode).
		$message = sprintf( __( 'You are on %1$s mode but your %1$s keys are not properly setup.', 'stancer' ), $mode );

		if ( $this->api_config->is_test_mode() ) {
			$class[] = 'notice-warning';
			$class[] = 'is-dismissible';
			$display = $is_setting_page;
		} else {
			$class[] = 'notice-error';
			if ( ! $is_setting_page ) {
				$message = __( 'Payments can not be done with Stancer. Please setup your API keys.', 'stancer' );
			}
			$display = true;
		}

		$class[] = 'stancer-key-notice';
		$class[] = 'notice';
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stancer' );
		$urlname = __( 'Stancer plugin is not properly configured.', 'stancer' );
		if ( $display ) {
			printf(
				'<div class="%1$s"><p><a href="%3$s">%4$s</a> %2$s</p></div>',
				esc_attr( implode( ' ', $class ) ),
				esc_html( $message ),
				esc_attr( $url ),
				esc_html( $urlname )
			);
		}

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
	 * Generate a dynamic module title.
	 *
	 * @since 1.1.0
	 *
	 * @return self
	 */
	public function dynamic_title() {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$request = $_REQUEST;
		// phpcs:enable
		$result = null;

		if ( array_key_exists( 'page', $request ) && 'wc-orders' === $request['page'] ) {
			$this->title = 'Stancer';
		}

		$table = fn( string $name ) => '`' . $wpdb->prefix . 'wc_stancer_' . $name . '`';

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		if ( array_key_exists( 'view-order', $request ) ) {
			$sql = 'SELECT `brand_name`, `last4`, `expiration`
					FROM ' . $table( 'card' ) . '
					LEFT JOIN ' . $table( 'payment' ) . '
					USING (`card_id`)
					WHERE TRUE
					AND `order_id` = %d
					AND `status` IN ("authorized", "to_capture", "captured")
					ORDER BY `stancer_payment_id` DESC
					LIMIT 1
					;';

			$result = $wpdb->get_row( $wpdb->prepare( $sql, $request['view-order'] ), ARRAY_A );
		}

		if ( array_key_exists( 'view-subscription', $request ) ) {
			$sql = 'SELECT `brand_name`, `last4`, `expiration`
					FROM ' . $table( 'card' ) . '
					LEFT JOIN ' . $table( 'subscription' ) . '
					USING (`card_id`)
					WHERE TRUE
					AND `subscription_id` = %d
					AND `is_active` = 1
					;';

			$result = $wpdb->get_row( $wpdb->prepare( $sql, $request['view-subscription'] ), ARRAY_A );
		}

		if ( array_key_exists( 'change_payment_method', $request ) ) {
			$sql = 'SELECT `brand_name`, `last4`, `expiration`
					FROM ' . $table( 'card' ) . '
					LEFT JOIN ' . $table( 'subscription' ) . '
					USING (`card_id`)
					WHERE TRUE
					AND `subscription_id` = %d
					AND `is_active` = 1
					;';

			$result = $wpdb->get_row( $wpdb->prepare( $sql, $request['change_payment_method'] ), ARRAY_A );
		}

		// phpcs:enable

		if ( is_array( $result ) ) {
			$date = new DateTime( $result['expiration'] );

			$this->title = vsprintf(
				// translators: $1 Card brand. $2 Last 4. $3 Expiration month. $4 Expiration year.
				__( '%1$s finishing with %2$s', 'stancer' ),
				[
					$result['brand_name'],
					$result['last4'],
					$date->format( 'm' ),
					$date->format( 'Y' ),
				],
			);
		}

		return $this;
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
	 * Return checkout button classes.
	 *
	 * @return string
	 */
	public function get_button_classes() {
		$default_classes = [
			'button',
			'alt',
			wc_wp_theme_get_element_class_name( 'button' ),
			'js-stancer-place-order',
		];

		return implode( ' ', $default_classes );
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
	 * @since 1.1.0 Woo Subscriptions method change description.
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
			'default' => __( 'Credit card / Debit card', 'stancer' ),
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

		if ( $this->subscriptions_enabled() ) {
			$inputs['woosubscription_title'] = [
				'title' => 'Woo Subscriptions',
				'type' => 'title',
			];

			$inputs['subscription_payment_change_description'] = [
				'desc_tip' => __( 'Description shown to the customer during payment method change.', 'stancer' ),
				'title' => __( 'Payment method change description', 'stancer' ),
				'type' => 'text',
			];
		} else {
			$inputs['subscription_payment_change_description'] = [
				'type' => 'hidden',
			];
		}

		$inputs['subscription_payment_change_description']['default'] = __(
			'An authorization request without an amount will be made in order to validate the new method.',
			'stancer',
		);

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
		wp_enqueue_script(
			'stancer-admin-ts',
			plugin_dir_url( STANCER_FILE ) . 'public/js/admin.min.js',
			[],
			STANCER_ASSETS_VERSION,
			true,
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

		$page_type = $this->settings['page_type'];
		$data = [
			'initiate' => WC_AJAX::get_endpoint( 'checkout' ),
		];

		if ( isset( $_GET['change_payment_method'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$page_type = 'pip'; // Picture in picture is forced for payment method change.
			$data['changePaymentMethod'] = [
				'nonce' => wp_create_nonce( 'change-method-' . $_GET['change_payment_method'] ),
				'url' => plugins_url( $this->id . '/subscription/change-payment-method.php' ),
			];

			echo esc_html( $this->settings['subscription_payment_change_description'] );
		} else {
			echo esc_html( $this->settings['payment_option_description'] );
		}

		$script_path = fn( string $name ) => plugin_dir_url( STANCER_FILE ) . 'public/js/' . $name . '.min.js';
		$style_path = fn( string $name ) => plugin_dir_url( STANCER_FILE ) . 'public/css/' . $name . '.min.css';

		$add_script = function ( string $script ) use ( $data, $script_path ) {
			$name = 'stancer-' . $script;

			wp_register_script( $name, $script_path( $script ), [ 'jquery' ], STANCER_ASSETS_VERSION, true );
			wp_localize_script( $name, 'stancer', $data );
			wp_enqueue_script( $name );
		};

		switch ( $page_type ) {
			case 'iframe':
				$add_script( 'popup' );
				break;

			case 'pip':
				$add_script( 'iframe' );
				wp_enqueue_style( 'stancer-iframe', $style_path( 'iframe' ), [], STANCER_ASSETS_VERSION );

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

		$attrs = [
			'class="' . esc_attr( $this->get_button_classes() ) . '"',
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

		return $this->create_api_payment( $order, $card_id );
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
		$stancer_payment = WC_Stancer_Payment::find( $order, [], false, [ 'pending' ] );
		$api_payment = new Stancer\Payment( $stancer_payment->payment_id );

		$auth = $api_payment->auth;
		$api_card = $api_payment->card;
		$status = $api_payment->status;

		if ( $auth ) {
			if ( Stancer\Auth\Status::SUCCESS !== $auth->status ) {
				// We can not mark the payment failed in the API.
				$status = Stancer\Payment\Status::FAILED;
			}
		} elseif ( ! $status && $api_card ) {
			$status = Stancer\Payment\Status::CAPTURE;
		}

		if ( ! empty( $status ) ) {
			$stancer_payment->mark_as( $status );
		}

		switch ( $status ) {
			case Stancer\Payment\Status::FAILED:
			case Stancer\Payment\Status::REFUSED:
				if ( 0 === $api_payment->amount ) {
					return;
				}

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
			case Stancer\Payment\Status::CAPTURED:
				// Save card.
				WC_Stancer_Card::save_from( $api_card, $customer );

				$stancer_payment->card_id = $api_card->id;
				$stancer_payment->save();

				// Remove cart.
				WC()->cart->empty_cart();

				// Complete order.
				$order->payment_complete( $api_payment->getId() );

				$order->add_order_note(
					sprintf(
						// translators: %s: Stancer payment identifier.
						__( 'Payment was completed via Stancer (Transaction ID: %s)', 'stancer' ),
						$api_payment->getId()
					)
				);
				$order->set_transaction_id( $api_payment->getId() );

				$this->register_subscription_data( $order, $stancer_payment );

				wp_safe_redirect( $this->get_return_url( $order ) );

				break;
			default:
				wp_safe_redirect( wc_get_checkout_url() );
		}

		exit();
	}
}
