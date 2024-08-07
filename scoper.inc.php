<?php
/**
 * Config file for php-scoper to prefix all our prod dependency (for now only stancer and psr).
 *
 * For more Information on Scopping see:
 * https://github.com/humbug/php-scoper/blob/master/docs/configuration.md
 *
 * @since 1.2.1
 * @link https://www.stancer.com/
 * @license MIT
 *
 * @package stancer
 * @subpackage stancer/includes
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Stancer\\Scoped\\Isolated',
	'finders' => [
		Finder::create()->files()->in( './vendor/' ),
		Finder::create()->append( [ './composer.json' ] ),
	],
];
