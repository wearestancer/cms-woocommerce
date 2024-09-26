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

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * Stancer plugin.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer {
	/**
	 * The ID of Stancer plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string $plugin_name The ID of Stancer plugin.
	 */
	private $plugin_name;

	/**
	 * The version of Stancer plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string $version The current version of Stancer plugin.
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'stancer';
		$this->version = STANCER_WC_VERSION;

		$this->load_actions();
		$this->load_filters();
	}

	/**
	 * Add Stancer to the list of available gateways.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateways List of gateways.
	 */
	public function add_gateway( $gateways ) {
		$gateways[] = 'WC_Stancer_Gateway';

		return $gateways;
	}

	/**
	 * Create database at plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function install_database() {
		global $wpdb;

		$version = get_option( 'stancer-version', '0.0.0' );

		if ( version_compare( STANCER_WC_VERSION, $version, '==' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		if ( version_compare( '1.0.0', $version, '>' ) ) {
			$sql = 'CREATE TABLE ' . $wpdb->prefix . 'wc_stancer_card (
				stancer_card_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique ID in this table",
				user_id int(10) UNSIGNED NOT NULL COMMENT "User ID (see users table)",
				card_id binary(29) NOT NULL COMMENT "Card ID (for the API, unique in this table)",
				last4 char(4) NOT NULL COMMENT "Last 4 digits of the number",
				expiration date NOT NULL COMMENT "Expiration date",
				brand varchar(10) NULL DEFAULT NULL COMMENT "Card brand",
				brand_name varchar(255) NULL DEFAULT NULL COMMENT "Card brand name",
				name varchar(10) NULL DEFAULT NULL COMMENT "Card holder\'s name",
				created datetime COMMENT "Creation date into the API",
				last_used datetime COMMENT "Last time this card was used",
				tokenized tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT "Is this card tokenized?",
				datetime_created datetime NULL DEFAULT NULL COMMENT "Creation date and time",
				datetime_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last modification date and time",
				PRIMARY KEY (stancer_card_id),
				UNIQUE INDEX card_id (card_id),
				INDEX user_id (user_id)
			);';

			dbDelta( $sql );

			$sql = 'CREATE TABLE ' . $wpdb->prefix . 'wc_stancer_customer (
				stancer_customer_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique ID in this table",
				user_id int(10) UNSIGNED NOT NULL COMMENT "User ID (see users table)",
				customer_id binary(29) NOT NULL COMMENT "Customer ID (for the API, unique in this table)",
				name varchar(64) NULL DEFAULT NULL COMMENT "Customer\'s name",
				email varchar(64) NULL DEFAULT NULL COMMENT "Customer\'s email",
				mobile varchar(16) NULL DEFAULT NULL COMMENT "Customer\'s mobile phone number",
				created datetime COMMENT "Creation date into the API",
				datetime_created datetime NULL DEFAULT NULL COMMENT "Creation date and time",
				datetime_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last modification date and time",
				PRIMARY KEY (stancer_customer_id),
				UNIQUE INDEX customer_id (customer_id),
				INDEX user_id (user_id)
			);';

			dbDelta( $sql );

			$sql = 'CREATE TABLE ' . $wpdb->prefix . 'wc_stancer_payment (
				stancer_payment_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique ID in this table",
				payment_id binary(29) NOT NULL COMMENT "ID of a payment, unique in this table",
				card_id binary(29) COMMENT "ID of the card used",
				customer_id binary(29) NOT NULL COMMENT "ID of the customer who paid (API)",
				currency char(3) NOT NULL COMMENT "Currency used",
				amount int(10) UNSIGNED NOT NULL COMMENT "Amount paid (in cents)",
				order_id int(10) UNSIGNED NOT NULL COMMENT "Internal order ID (see posts table)",
				status varchar(10) NOT NULL DEFAULT "pending" COMMENT "Payment\'s status (trust only API status)",
				datetime_created datetime NULL DEFAULT NULL COMMENT "Creation date and time",
				created datetime COMMENT "Creation date into the API",
				datetime_modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last modification date and time",
				PRIMARY KEY (stancer_payment_id),
				UNIQUE INDEX payment_id (payment_id),
				INDEX order_id (order_id)
			);';

			dbDelta( $sql );
		}

		if ( version_compare( '1.1.0', $version, '>' ) ) {
			$sql = 'CREATE TABLE ' . $wpdb->prefix . 'wc_stancer_subscription (
				`stancer_subscription_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique ID in this table",
				`is_active` tinyint(1) UNSIGNED DEFAULT 1 COMMENT "Is still",
				`subscription_id` int(10) UNSIGNED NOT NULL COMMENT "Subscription order ID (see posts table)",
				`payment_id` binary(29) NOT NULL COMMENT "Source payment identifier",
				`card_id` binary(29) NOT NULL COMMENT "ID of the card used",
				`customer_id` binary(29) DEFAULT NULL COMMENT "ID of the customer used",
				`datetime_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Creation date and time",
				`datetime_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last modification date and time",
				PRIMARY KEY (`stancer_subscription_id`),
				INDEX `subscription_id-is_active` (`subscription_id`, `is_active`)
			);';

			dbDelta( $sql );
		}

		update_option( 'stancer-version', STANCER_WC_VERSION );
	}

	/**
	 * Load all actions for Stancer plugin.
	 *
	 * @since 1.0.0
	 */
	private function load_actions() {
		add_action( 'plugins_loaded', [ $this, 'load_plugin' ] );
		add_action( 'wc_ajax_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'load_public_hooks' ] );
		add_action( 'woocommerce_blocks_loaded', [ $this, 'gateway_block_support' ] );
		add_action(
			'rest_api_init',
			function () {
				$payment_change_controller = new WCS_Stancer_Change_Payment_Method();
				$payment_change_controller->register_routes();
			}
		);
		add_action( 'admin_notices', [ $this, 'display_depreciation' ] );
	}

	/**
	 * Display deprecated settings parameters
	 *
	 * @since 1.2.5
	 *
	 * @return void
	 */
	public function display_depreciation() {

		$options = get_option( 'woocommerce_stancer_settings' );
		if ( is_array( $options ) && array_key_exists( 'page_type', $options ) && 'iframe' === $options['page_type'] ) {

			$class = [ 'notice', 'notice-warning' ];
			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				esc_attr( implode( ' ', $class ) ),
				sprintf(
					// translators: "%s": Link to plugin settings.
					esc_html__(
						'Stancer payment by popup is deprecated since 31st July 2024, it will be deleted in the next major version (1.3.0). We advise you to change the option in your %s.',
						'stancer'
					),
					'<a href="' . esc_attr( stancer_setting_url() ) . '">' . esc_html__( 'plugin settings', 'stancer' ) . '</a>',
				),
			);
		}
	}

	/**
	 * Load all filters for Stancer plugin.
	 *
	 * @since 1.0.0
	 */
	private function load_filters() {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateway' ] );
	}

	/**
	 * Load Stancer gateway.
	 *
	 * @since 1.0.0
	 */
	public function load_gateway() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-stancer-gateway.php';
		}
	}

	/**
	 * Load public hooks (CSS/JS) for Stancer plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_public_hooks() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( STANCER_FILE ) . 'public/js/popup-closing.min.js',
			[],
			$this->version,
			true
		);
	}

	/**
	 * Load the plugin.
	 *
	 * @since 1.1.0
	 */
	public function load_plugin() {
		$this->upgrade_plugin();
		$this->load_gateway();
	}
	/**
	 * Register the Gateway Block support to the approriate action hooks
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function gateway_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Stancer_Gateway_Block_Support() );
				}
			);
		}
	}
	/**
	 * Get the plugin absolute path
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function plugin_abspath() {
		return plugin_dir_path( dirname( ( __FILE__ ) ) );
	}

	/**
	 * Fake run method.
	 *
	 * @since 1.0.0
	 */
	public function run() { }

	/**
	 * Upgrade the plugin.
	 *
	 * @since 1.1.0
	 */
	public function upgrade_plugin() {
		$version = get_option( 'stancer-version', '0.0.0' );

		if ( version_compare( STANCER_WC_VERSION, $version, '==' ) ) {
			return;
		}

		$this->install_database();

		$options = get_option( 'woocommerce_stancer_settings' );
		$replace = [
			'description' => 'payment_description',
			'title' => 'payment_option_text',
		];
		$updated = false;

		if ( is_array( $options ) ) {
			$replace = [
				'description' => 'payment_description',
				'title' => 'payment_option_text',
			];

			foreach ( $replace as $key => $value ) {
				if ( array_key_exists( $key, $options ) ) {
					$options[ $value ] = $options[ $key ];
					$updated = true;

					unset( $options[ $key ] );
				}
			}

			$new_defaults = [
				'subscription_payment_change_description' => __(
					'An authorization request without an amount will be made in order to validate the new method.',
					'stancer',
				),
			];

			foreach ( $new_defaults as $key => $value ) {
				if ( ! array_key_exists( $key, $options ) ) {
					$options[ $key ] = $value;
					$updated = true;
				}
			}
		}

		if ( $updated ) {
			update_option( 'woocommerce_stancer_settings', $options );
		}
	}
}
