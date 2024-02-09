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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( defined( 'WC_REMOVE_ALL_DATA' ) && true === WC_REMOVE_ALL_DATA ) {
	global $wpdb;

	// Drop tables.
	$wpdb->query( 'DROP TABLE IF EXISTS {$wpdb->prefix}wc_stancer_card' );
	$wpdb->query( 'DROP TABLE IF EXISTS {$wpdb->prefix}wc_stancer_customer' );
	$wpdb->query( 'DROP TABLE IF EXISTS {$wpdb->prefix}wc_stancer_payment' );
	$wpdb->query( 'DROP TABLE IF EXISTS {$wpdb->prefix}wc_stancer_subscription' );

	// Delete options.
	delete_option( 'stancer-version' );
	delete_option( 'woocommerce_stancer_settings' );
}
