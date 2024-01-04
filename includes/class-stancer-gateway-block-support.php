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

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Block support for Stancer gateway
 *
 * @since unreleased
 *
 * @package  stancer
 * @subpackage  stancer/includes
 */
final class WC_Stancer_Gateway_Block_Support extends AbstractPaymentMethodType {

	/**
	 * Name of our payment method
	 *
	 * @var string
	 */
	protected $name = 'stancer';

	/**
	 * Our gateway class
	 * use solely to get param for now,
	 * if not more usefull later must be deleted
	 *
	 * @var WC_Payment_Gateway
	 */
	private WC_Payment_Gateway $gateway;

	/**
	 * Settings of our payment gateway .
	 * sent to the frontend on wc.wcSettings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Initialize the block support for our Gateway
	 *
	 * @return void
	 */
	public function initialize() {
		$gateways = WC()->payment_gateways->payment_gateways();
		$this->gateway = $gateways[ $this->name ];
		$this->settings = $this->gateway->settings;
	}

	/**
	 * Check if the Gateway is active for blocks,
	 * (we might add a settings in gateway to activate/disable block support)
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Get the js file and add it to the WordPress scripts list.
	 *
	 * @return array<string>
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'block_iframe',
			plugin_dir_url( STANCER_FILE ) . 'public/js/block.min.js',
			[],
			STANCER_ASSETS_VERSION,
			true
		);
		wp_enqueue_style(
			'stancer-iframe',
			plugin_dir_url( STANCER_FILE ) . 'public/css/iframe.min.css',
			[],
			STANCER_ASSETS_VERSION,
		);
		wp_enqueue_style(
			'stancer-option',
			plugin_dir_url( STANCER_FILE ) . 'public/css/option.min.css',
			[],
			STANCER_ASSETS_VERSION,
		);
		return [ 'block_iframe' ];
	}

	/**
	 * Get the Stancer gateway settings, and restructure it for easy use in our frontend.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$logo = $this->gateway->settings['payment_option_logo'] ?? 'no-logo';
		return [
			'title' => $this->get_setting( 'payment_option_text' ),
			'description' => $this->get_setting( 'payment_option_description' ),
			'label' => $this->get_setting(
				'button_label',
				__( 'Pay by card', 'stancer' ),
			),
			'logo' => [
				'url' => plugin_dir_url( STANCER_FILE ) . 'public/svg/symbols.svg#' . $logo,
				'class' => 'stancer-option__logo stancer-option__logo--' . $logo,
			],
			'page_type' => $this->get_setting( 'page_type', 'pip' ),

		];
	}
}
