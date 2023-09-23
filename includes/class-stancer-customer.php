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
 * Customer table representation.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Customer extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $primary = 'stancer_customer_id';

	/**
	 * Table name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'wc_stancer_customer';

	/**
	 * WooCommerce user ID.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	protected $user_id;

	/**
	 * API customer ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $customer_id;

	/**
	 * Customer's name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Customer's email.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Customer's mobile phone number.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $mobile;

	/**
	 * Card creation date in Stancer API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $created;

	/**
	 * Retrieves customer.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $customer_id WooCommerce customer ID.
	 *
	 * @return object|null
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
	 * Generate api customer.
	 *
	 * @since 1.0.0
	 *
	 * @param array $customer Customer data.
	 *
	 * @return Stancer\Customer
	 */
	public static function generate_api_customer( $customer ) {
		$api_customer = new Stancer\Customer();

		$api_customer->name = $customer['first_name'] . ' ' . $customer['last_name'];
		$api_customer->email = $customer['email'];

		if ( ! empty( $customer['id'] ) ) {
			$api_customer->external_id = (string) $customer['id'];
		}

		return $api_customer;
	}

	/**
	 * Get api customer.
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

		if ( $stancer_customer && $stancer_customer->customer_id ) {
			$api_customer = new Stancer\Customer( $stancer_customer->customer_id );
		} else {
			$api_customer = static::generate_api_customer( $customer );
		}

		return $api_customer;
	}

	/**
	 * Create or update an Stancer customer from Stancer API customer object.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Customer|null $api_customer Customer to update.
	 *
	 * @return static|null
	 */
	public static function save_from( ?Stancer\Customer $api_customer = null ) {
		if ( ! $api_customer || ! $api_customer->external_id ) {
			return null;
		}

		$existing_customer = static::find( $api_customer->external_id );

		if ( $existing_customer ) {
			$stancer_customer = new WC_Stancer_Customer( $existing_customer->stancer_customer_id );
		} else {
			$stancer_customer = new WC_Stancer_Customer();
		}

		$stancer_customer->user_id = $api_customer->external_id;
		$stancer_customer->customer_id = $api_customer->id;
		$stancer_customer->name = $api_customer->name;
		$stancer_customer->email = $api_customer->email;
		$stancer_customer->mobile = $api_customer->mobile;

		$creation = $api_customer->creation_date;
		$stancer_customer->created = $creation ? $creation->format( 'Y-m-d H:i:s' ) : null;

		return $stancer_customer->save();
	}
}
