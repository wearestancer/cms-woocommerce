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

 // phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Generic exception for the module.
 *
 * @since 1.1.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Exception extends Exception {
}

/**
 * Bad request exception, for stancer controllers
 */
class WC_Stancer_Request_Exception extends WC_Stancer_Exception{}
