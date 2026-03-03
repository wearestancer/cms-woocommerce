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

use Stancer;

/**
 * WP-Cron reconciliation job for pending Stancer payments.
 *
 * Stancer does not support webhooks. This class registers a scheduled task
 * that runs every 15 minutes to poll the Stancer API for payments still
 * recorded as "pending" locally and updates WooCommerce order statuses
 * accordingly.
 *
 * Schedule / unschedule are called on plugin activation / deactivation.
 *
 * @since Unreleased
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Cron {

	/**
	 * WP-Cron hook name.
	 *
	 * @since Unreleased
	 *
	 * @var string
	 */
	const HOOK = 'stancer_reconcile_pending_payments';

	/**
	 * Custom cron schedule identifier.
	 *
	 * @since Unreleased
	 *
	 * @var string
	 */
	const SCHEDULE = 'stancer_fifteen_minutes';

	/**
	 * Minimum age of a pending payment before it is reconciled (seconds).
	 *
	 * Payments younger than this threshold are skipped to allow the Stancer
	 * payment page to complete its redirect flow before we poll the API.
	 *
	 * @since Unreleased
	 *
	 * @var int
	 */
	const THRESHOLD = 900; // 15 minutes.

	/**
	 * Register the custom 15-minute cron schedule.
	 *
	 * Hooked to the WordPress `cron_schedules` filter.
	 *
	 * @since Unreleased
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing cron schedules.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function add_schedule( array $schedules ): array {
		$schedules[ static::SCHEDULE ] = [
			'interval' => static::THRESHOLD,
			'display'  => __( 'Every 15 minutes (Stancer reconciliation)', 'stancer' ),
		];

		return $schedules;
	}

	/**
	 * Schedule the reconciliation event.
	 *
	 * Called on plugin activation. Has no effect if already scheduled.
	 *
	 * @since Unreleased
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( static::HOOK ) ) {
			wp_schedule_event( time(), static::SCHEDULE, static::HOOK );
		}
	}

	/**
	 * Unschedule the reconciliation event.
	 *
	 * Called on plugin deactivation.
	 *
	 * @since Unreleased
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( static::HOOK );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, static::HOOK );
		}
	}

	/**
	 * Reconcile pending payments by polling the Stancer API.
	 *
	 * Finds all local payments with status "pending" or "authorized" that were
	 * created between fifteen minutes and one week ago, then checks their
	 * real status against the API. Updates the local record and the WooCommerce
	 * order accordingly.
	 *
	 * @since Unreleased
	 *
	 * @return void
	 */
	public function reconcile(): void {
		global $wpdb;

		$week_timestamp = 604800;

		$fifteen_minutes_before = gmdate( 'Y-m-d H:i:s', time() - static::THRESHOLD );
		$one_week_before = gmdate( 'Y-m-d H:i:s', time() - $week_timestamp );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_stancer_payment
				 WHERE `status` IN ('pending','authorized')
				 AND `datetime_created` <= %s
				 AND `datetime_created` >= %s",
				[
					$fifteen_minutes_before,
					$one_week_before,
				]
			)
		);
		// phpcs:enable

		if ( empty( $rows ) ) {
			return;
		}

		$logger = wc_get_logger();

		$config = ( new WC_Stancer_Gateway() )->api_config;
		if ( $config->is_configured() ) {
			foreach ( $rows as $row ) {
				$this->process_row( $row, $logger );
			}
		} else {
			$logger->info(
				sprintf( 'Stancer cron: Stancer configuration is not properly set up, please check your setting page.' ),
				[ 'source' => 'stancer-cron' ]
			);
		}
	}

	/**
	 * Process a single pending payment row.
	 *
	 * Retrieves the payment status from the Stancer API and updates the local
	 * record and WooCommerce order if the status has changed.
	 *
	 * @since Unreleased
	 *
	 * @param object              $row    Database row from wc_stancer_payment.
	 * @param WC_Logger_Interface $logger WooCommerce logger.
	 *
	 * @return void
	 */
	private function process_row( object $row, WC_Logger_Interface $logger ): void {
		$payment_id = $row->payment_id;
		$context    = [ 'source' => 'stancer-cron' ];

		try {
			$api_payment = new Stancer\Payment( $payment_id );
			$api_status  = $api_payment->status;

			// No status yet — wait for next run.
			if ( ! $api_status ) {
				return;
			}

			// Still authorized on the API side — wait for next run.
			if ( Stancer\Payment\Status::AUTHORIZED === $api_status ) {
				return;
			}

			// Update local record with the real API status.
			$stancer_payment = new WC_Stancer_Payment();
			$stancer_payment->hydrate( (array) $row );
			$stancer_payment->mark_as( $api_status->value );

			// Retrieve the associated WooCommerce order.
			$order = wc_get_order( (int) $row->order_id );

			if ( ! $order ) {
				$logger->warning(
					sprintf(
						'Stancer cron: order %d not found for payment %s.',
						(int) $row->order_id,
						$payment_id
					),
					$context
				);

				return;
			}

			switch ( $api_status ) {
				case Stancer\Payment\Status::TO_CAPTURE:
				case Stancer\Payment\Status::CAPTURE_SENT:
				case Stancer\Payment\Status::CAPTURED:
					if ( $order->needs_payment() ) {
						$order->payment_complete( $payment_id );
						$order->add_order_note(
							sprintf(
								// translators: "%s": Stancer payment identifier.
								__( 'Payment confirmed via Stancer reconciliation (Transaction ID: %s)', 'stancer' ),
								$payment_id
							)
						);
						$logger->info(
							sprintf(
								'Stancer cron: order %d marked complete (payment %s, status %s).',
								$order->get_id(),
								$payment_id,
								$api_status->value
							),
							$context
						);
					}
					break;

				case Stancer\Payment\Status::REFUSED:
				case Stancer\Payment\Status::CANCELED:
				case Stancer\Payment\Status::EXPIRED:
					if ( ! $order->has_status( [ 'failed', 'cancelled' ] ) ) {
						$order->update_status(
							'failed',
							sprintf(
								// translators: "%1$s": Stancer payment status. "%2$s": Stancer payment identifier.
								__( 'Payment %1$s via Stancer (Transaction ID: %2$s)', 'stancer' ),
								$api_status->value,
								$payment_id
							)
						);
						$logger->info(
							sprintf(
								'Stancer cron: order %d marked failed (payment %s, status %s).',
								$order->get_id(),
								$payment_id,
								$api_status->value
							),
							$context
						);
					}
					break;

				default:
					$logger->debug(
						sprintf(
							'Stancer cron: payment %s has status "%s" — no action taken.',
							$payment_id,
							$api_status->value
						),
						$context
					);
					break;
			}
		} catch ( Exception $e ) {
			$logger->error(
				sprintf(
					'Stancer cron error for payment %s: %s',
					$payment_id,
					$e->getMessage()
				),
				$context
			);
		}
	}
}
