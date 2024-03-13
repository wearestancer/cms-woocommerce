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

/**
 * Card table representation.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Card extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $primary = 'stancer_card_id';

	/**
	 * Table name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'wc_stancer_card';

	/**
	 * WooCommerce user ID.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	protected $user_id;

	/**
	 * API card ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $card_id;

	/**
	 * Card's last 4 numbers.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $last4;

	/**
	 * Expiration date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $expiration;

	/**
	 * Card's brand.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $brand;

	/**
	 * Card's brand name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $brand_name;

	/**
	 * Card's holder name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Card creation date in Stancer API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $created;

	/**
	 * Last date of use.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $last_used;

	/**
	 * Is card tokenized ?
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected $tokenized;

	/**
	 * Delete card.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Card $api_card Card to delete.
	 *
	 * @return bool
	 */
	public static function delete_from( Stancer\Card $api_card ) {
		global $wpdb;

		return (bool) $wpdb->delete( "{$wpdb->prefix}wc_stancer_card", [ 'card_id' => absint( $api_card->id ) ] );
	}

	/**
	 * Retrieves card.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Card $api_card Card to find.
	 * @return static
	 */
	public static function find_by_api_card( Stancer\Card $api_card ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_stancer_card WHERE card_id = %s",
				esc_sql( $api_card->id ),
			)
		);

		if ( ! $row ) {
			return null;
		}

		$stancer_card = new static();
		$stancer_card->hydrate( (array) $row );

		return $stancer_card;
	}

	/**
	 * Retrieves all valid card of customer.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Customer $customer Customer.
	 * @return array<static>
	 */
	public static function get_customer_cards( WC_Customer $customer ) {
		global $wpdb;

		$stancer_cards = [];

		if ( empty( $customer->get_id() ) ) {
			return $stancer_cards;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_stancer_card WHERE user_id = %d AND expiration > CURDATE() ORDER BY last_used DESC",
				absint( $customer->get_id() )
			)
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				$stancer_card = new static();
				$stancer_card->hydrate( (array) $result );
				$stancer_cards[] = $stancer_card;
			}
		}

		return $stancer_cards;
	}

	/**
	 * Save card.
	 *
	 * @since 1.0.0
	 *
	 * @param Stancer\Card $api_card Card.
	 * @param WC_Customer $customer Customer.
	 * @return static
	 */
	public static function save_from( Stancer\Card $api_card, WC_Customer $customer ) {
		if ( empty( $customer->get_id() ) ) {
			return;
		}

		$stancer_card = static::find_by_api_card( $api_card );

		if ( ! $stancer_card ) {
			$creation = $api_card->creation_date;

			$stancer_card = new static();
			$stancer_card->user_id = $customer->get_id();
			$stancer_card->card_id = $api_card->id;
			$stancer_card->last4 = $api_card->last4;
			$stancer_card->brand = $api_card->brand;
			$stancer_card->brand_name = $api_card->getBrandName();
			$stancer_card->name = $api_card->name;
			$stancer_card->created = $creation ? $creation->format( 'Y-m-d H:i:s' ) : null;
			$stancer_card->expiration = $api_card->getExpirationDate()->format( 'Y-m-d' );
			$stancer_card->tokenized = $api_card->is_tokenized;
		}

		$stancer_card->last_used = gmdate( 'Y-m-d H:i:s' );
		$stancer_card->save();

		return $stancer_card;
	}
}
