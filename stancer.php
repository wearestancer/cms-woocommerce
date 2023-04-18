<?php
/**
 * Stancer
 *
 * @package stancer
 * @license MIT
 * @copyright 2023 Stancer / Iliad 78
 *
 * @wordpress-plugin
 * Plugin Name: Stancer
 * Plugin URI:  https://gitlab.com/wearestancer/cms/woocommerce
 * Description: Simple payment solution at low prices.
 * Version:     1.0.0
 * Author:      Stancer
 * Author URI:  https://www.stancer.com
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Domain Path: /languages
 * Text Domain: stancer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currently plugin version.
 */
define( 'STANCER_VERSION', '1.0.0' );
define( 'STANCER_FILE', __FILE__ );
define( 'STANCER_DIRECTORY_PATH', plugin_dir_path( STANCER_FILE ) );

require_once STANCER_DIRECTORY_PATH . 'includes/class-stancer.php';
require_once STANCER_DIRECTORY_PATH . '/vendor/autoload.php';

add_action( 'plugins_loaded', 'load_translations' );

/**
 * Wrapper to load our translations.
 */
function load_translations() {
	load_plugin_textdomain( 'stancer', false, plugin_basename( dirname( STANCER_FILE ) ) . '/languages' );
}

if ( ! function_exists( 'is_woocommerce_activated' ) ) {
	/**
	 * Check if WooCommerce is activated.
	 *
	 * Simple stub, just in case.
	 */
	function is_woocommerce_activated() {
		return class_exists( 'woocommerce' );
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_stancer() {
	$plugin = new WC_Stancer();
	$plugin->run();
}

run_stancer();
