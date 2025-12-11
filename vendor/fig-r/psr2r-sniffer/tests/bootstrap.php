<?php
/**
 * The PHP_CodeSniffer has its own autoloading system, which we
 * need to hook into
 *
 * @author Ed Barnard
 * @license MIT
 */

//defined('PHP_CODESNIFFER_VERBOSITY') or define('PHP_CODESNIFFER_VERBOSITY', 2);

/** @noinspection UsingInclusionOnceReturnValueInspection */
$autoloader = require_once dirname(__DIR__) . DIRECTORY_SEPARATOR .
	'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// The code sniffer autoloader
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR .
	'vendor' . DIRECTORY_SEPARATOR .
	'squizlabs' . DIRECTORY_SEPARATOR .
	'php_codesniffer' . DIRECTORY_SEPARATOR .
	'autoload.php';

// The test wrapper
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR .
	'vendor' . DIRECTORY_SEPARATOR .
	'squizlabs' . DIRECTORY_SEPARATOR .
	'php_codesniffer' . DIRECTORY_SEPARATOR .
	'tests' . DIRECTORY_SEPARATOR .
	'AllTests.php';

if (is_object($autoloader)) {
	$GLOBALS['finder'] = $autoloader;
}
