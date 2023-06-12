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

/**
 * Stancer API configuration.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Config {
	/**
	 * Mode Live or Test.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $mode;

	/**
	 * API Host.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $host;

	/**
	 * API Timeout.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $timeout;

	/**
	 * Auth limit.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $auth_limit;

	/**
	 * Page type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $page_type;

	/**
	 * Public API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $public_key;

	/**
	 * Secret API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $secret_key;

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $description;

	/**
	 * API is configured.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $is_configured;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Base settings.
	 */
	public function __construct( $settings ) {
		$this->auth_limit = $settings['auth_limit'];
		$this->description = $settings['description'];
		$this->host = $settings['host'];
		$this->is_configured = $this->is_configured();
		$this->mode = Stancer\Config::TEST_MODE;
		$this->page_type = $settings['page_type'];
		$this->public_key = $this->get_public_key( $settings );
		$this->secret_key = $this->get_secret_key( $settings );
		$this->timeout = $settings['timeout'];

		if ( ! empty( $settings['test_mode'] ) && 'no' === $settings['test_mode'] ) {
			$this->mode = Stancer\Config::LIVE_MODE;
		}
	}

	/**
	 * Get API configuration.
	 *
	 * @since 1.0.0
	 */
	private function get_config() {
		$api_config = null;

		if ( ! $this->public_key || ! $this->secret_key ) {
			return $api_config;
		}

		$api_config = Stancer\Config::init(
			[
				$this->public_key,
				$this->secret_key,
			]
		);

		if ( $api_config ) {
			$api_config->setMode( $this->mode );

			if ( $this->host ) {
				$api_config->setHost( $this->host );
			}

			if ( $this->timeout ) {
				$api_config->setTimeout( $this->timeout );
			}

			// phpcs:disable WordPress.WP.CapitalPDangit.Misspelled
			$api_config->addAppData( 'libstancer-woocommerce', STANCER_VERSION );
			$api_config->addAppData( 'woocommerce', WC_VERSION );
			$api_config->addAppData( 'wordpress', get_bloginfo( 'version' ) );
			// phpcs:enable
		}

		return $api_config;
	}

	/**
	 * Get the public API key.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Settings.
	 */
	private function get_public_key( $settings ) {
		if ( $this->is_test_mode() ) {
			return $settings['api_test_public_key'];
		}

		return $settings['api_live_public_key'];
	}

	/**
	 * Get the secrect API key.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Settings.
	 */
	private function get_secret_key( $settings ) {
		if ( $this->is_test_mode() ) {
			return $settings['api_test_secret_key'];
		}

		return $settings['api_live_secret_key'];
	}

	/**
	 * Checks if on test mode.
	 *
	 * @since 1.0.0
	 */
	public function is_test_mode() {
		return Stancer\Config::TEST_MODE === $this->mode;
	}

	/**
	 * Checks if it is configured.
	 *
	 * @since 1.0.0
	 */
	public function is_configured() {
		$api_config = $this->get_config();

		try {
			if (
				! empty( $api_config )
				&& ! empty( $api_config->getPublicKey() )
				&& ! empty( $api_config->getSecretKey() )
			) {
				return true;
			}
		} catch ( Stancer\Exceptions\MissingApiKeyException $exception ) {
			return false;
		}

		return false;
	}
}
