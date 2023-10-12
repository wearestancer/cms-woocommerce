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
		if ( defined( 'STANCER_WC_VERSION' ) ) {
			$this->version = STANCER_WC_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'stancer';

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
	 * Initialize administration.
	 *
	 * @since 1.1.0
	 */
	public function init_admin() {
		$this->install_database();

		$options = get_option( 'woocommerce_stancer_settings' );
		$replace = [
			'description' => 'payment_description',
			'title' => 'payment_option_text',
		];
		$updated = false;

		foreach ( $replace as $key => $value ) {
			if ( array_key_exists( $key, $options ) ) {
				$options[ $value ] = $options[ $key ];
				$updated = true;

				unset( $options[ $key ] );
			}
		}

		if ( $updated ) {
			update_option( 'woocommerce_stancer_settings', $options );
		}
	}

	/**
	 * Create database at plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function install_database() {
		global $wpdb;

		$version = get_option( 'stancer', '1.0.0' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		if ( version_compare( '1.0.0', $version, '>=' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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
	}

	/**
	 * Load all actions for Stancer plugin.
	 *
	 * @since 1.0.0
	 */
	private function load_actions() {
		add_action( 'plugins_loaded', [ $this, 'load_gateway' ], 0 );
		add_action( 'admin_init', [ $this, 'init_admin' ] );
		add_action( 'wc_ajax_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'load_public_hooks' ] );
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
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stancer-gateway.php';
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
	 * Fake run method.
	 *
	 * @since 1.0.0
	 */
	public function run() { }
}
