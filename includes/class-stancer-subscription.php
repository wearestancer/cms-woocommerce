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
 * Subscription table representation.
 *
 * @since 1.1.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Subscription extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $primary = 'stancer_subscription_id';

	/**
	 * Table name.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $table = 'wc_stancer_subscription';

	/**
	 * Is currently active?
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $is_active;

	/**
	 * Subscription ID.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $subscription_id;

	/**
	 * API payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $payment_id;

	/**
	 * API card ID.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $card_id;

	/**
	 * API customer ID.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $customer_id;
}
