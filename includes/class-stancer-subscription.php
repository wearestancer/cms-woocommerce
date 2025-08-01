<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com/
 * @license MIT
 * @copyright 2023-2025 Stancer / Iliad 78
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
 *
 * @property string $card_id
 * @property string $customer_id
 * @property bool $is_active
 * @property string $payment_id
 * @property int $subscription_id
 */
class WC_Stancer_Subscription extends WC_Stancer_Abstract_Table {
	/**
	 * Name of primary key.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected string $primary = 'stancer_subscription_id';

	/**
	 * Table name.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected string $table = 'wc_stancer_subscription';

	/**
	 * Is currently active?
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	protected $is_active;

	/**
	 * Subscription ID.
	 *
	 * @since 1.1.0
	 *
	 * @var int
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
