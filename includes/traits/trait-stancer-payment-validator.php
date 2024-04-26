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

trait WC_Stancer_Payment_Validator_Traits {

	/**
	 * Return a valid payment description.
	 *
	 * @since unreleased
	 *
	 * @param array $params Variable parameter for dynamic description.
	 * @param string|null $custom_description A custom description to use instead of the api_config one.
	 * @param string $default_description A default description if the custom description doesn't fit our criteria.
	 * @return string
	 */
	public static function get_valid_description( array $params, ?string $custom_description, string $default_description ) {

		if ( ! isset( $custom_description ) ) {
			return $default_description;
		}

		$description = str_replace( array_keys( $params ), $params, $custom_description );
		if ( strlen( $description ) > WC_Stancer_Gateway::MAX_SIZE_DESCRIPTION || strlen( $description ) < WC_Stancer_Gateway::MIN_SIZE_DESCRIPTION ) {
			return $default_description;
		}
		return $description;
	}

	/**
	 * Prepare amount.
	 *
	 * @since 1.1.0
	 * @since unreleased Moved From `WC_Stancer_Api` to `WC_Stancer_Payment_Validator_Traits`
	 *
	 * @param int|float $amount Amount of the order.
	 *
	 * @return int
	 */
	public static function prepare_amount( $amount ) {
		// /!\ We cannot relie on woo formatting methods! like  wc_get_price_decimals()

		return (int) (string) ( $amount * 100 );
	}
}
