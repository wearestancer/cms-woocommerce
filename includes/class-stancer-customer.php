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
 * Customer table representation
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Customer extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $primary = 'stancer_customer_id';

	/**
	 * Table name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $table = 'wc_stancer_customer';

	/**
	 * WooCommerce user ID
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $user_id;

	/**
	 * API customer ID
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $customer_id;

	/**
	 * Customer's name
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $name;

	/**
	 * Customer's email
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $email;

	/**
	 * Customer's mobile phone number
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $mobile;

	/**
	 * Card creation date in Stancer Api
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $created;

	/**
	 * Create or update an Stancer customer from Stancer API customer object
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Customer|null $api_customer Customer to update.
	 *
	 * @return bool
	 */
	public static function save_from( ?Stancer\Customer $api_customer = null ) {
		if ( ! $api_customer ) {
			return false;
		}

		$existing_customer = static::find( $api_customer->getExternalId() );
		if ( $existing_customer ) {
			$stancer_customer = new WC_Stancer_Customer( $existing_customer->stancer_customer_id );
		} else {
			$stancer_customer = new WC_Stancer_Customer();
		}

		$stancer_customer->user_id = $api_customer->getExternalId();
		$stancer_customer->customer_id = $api_customer->getId();
		$stancer_customer->name = $api_customer->getName();
		$stancer_customer->email = $api_customer->getEmail();
		$stancer_customer->mobile = $api_customer->getMobile();
		$creation = $api_customer->getCreationDate();
		$stancer_customer->created = $creation ? $creation->format( 'Y-m-d H:i:s' ) : null;

		return $stancer_customer->save();
	}

	/**
	 * Retrieves customer
	 *
	 * @since 1.0.0
	 *
	 * @param integer $customer_id Customer ID.
	 */
	public static function find( $customer_id ) {
		global $wpdb;

		$stancer_customer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_stancer_customer WHERE user_id = %d",
				absint( $customer_id )
			)
		);

		return $stancer_customer;
	}

	/**
	 * Generate api customer
	 *
	 * @since 1.0.0
	 *
	 * @param array $customer Customer data.
	 * @return Stancer\Customer
	 */
	public static function generate_api_customer( $customer ) {
		$api_customer = new Stancer\Customer();
		$api_customer
			->setName( $customer['first_name'] . ' ' . $customer['last_name'] )
			->setEmail( $customer['email'] );

		if ( ! empty( $customer['id'] ) ) {
			$api_customer->setExternalId( (string) $customer['id'] );
		}

		return $api_customer;
	}

	/**
	 * Get api customer
	 *
	 * @since 1.0.0
	 *
	 * @param array $customer Customer data.
	 */
	public static function get_api_customer( $customer ) {
		$stancer_customer = null;

		if ( ! empty( $customer['id'] ) ) {
			$stancer_customer = static::find( $customer['id'] );
		}

		if ( ! $stancer_customer || ! $stancer_customer->customer_id ) {
			$api_customer = static::generate_api_customer( $customer );
		} else {
			$api_customer = new Stancer\Customer( $stancer_customer->customer_id );
		}

		return $api_customer;
	}
}
