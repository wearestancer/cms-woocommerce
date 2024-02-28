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
	 * Auth limit.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $auth_limit;

	/**
	 * Description.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $description;

	/**
	 * API Host.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $host;

	/**
	 * Mode Live or Test.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $mode;

	/**
	 * Page type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $page_type;

	/**
	 * Public production API key.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $pprod;

	/**
	 * Public test API key.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $ptest;

	/**
	 * Secret production API key.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $sprod;

	/**
	 * Secret test API key.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $stest;

	/**
	 * Enable refund
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	public $refund;
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Base settings.
	 */
	public function __construct( $settings ) {
		$this->auth_limit = $settings['auth_limit'];
		$this->description = $settings['payment_description'] ?? '';
		$this->host = $settings['host'];
		$this->mode = Stancer\Config::TEST_MODE;
		$this->page_type = $settings['page_type'] ?? 'pip';
		$this->pprod = $settings['api_live_public_key'] ?? '';
		$this->ptest = $settings['api_test_public_key'] ?? '';
		$this->sprod = $settings['api_live_secret_key'] ?? '';
		$this->stest = $settings['api_test_secret_key'] ?? '';
		$this->refund = 'yes' === $settings['enable_refund'] ? true : false;

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
		$keys = [
			$this->pprod,
			$this->sprod,
			$this->ptest,
			$this->stest,
		];

		$api_config = Stancer\Config::init( array_filter( $keys ) );

		if ( $api_config ) {
			$api_config->setMode( $this->mode ?? Stancer\Config::TEST_MODE );

			if ( $this->host ) {
				$api_config->setHost( $this->host );
			}

			// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInText
			$api_config->addAppData( 'libstancer-woocommerce', STANCER_WC_VERSION );
			$api_config->addAppData( 'woocommerce', WC_VERSION );
			$api_config->addAppData( 'wordpress', get_bloginfo( 'version' ) );
			// phpcs:enable
		}

		return $api_config;
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

	/**
	 * Checks if it is NOT configured.
	 *
	 * @since 1.1.0
	 */
	public function is_not_configured() {
		return ! $this->is_configured();
	}
}
