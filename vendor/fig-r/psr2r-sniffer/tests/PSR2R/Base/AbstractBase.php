<?php

namespace PSR2R\Base;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Tests\Standards\AbstractSniffUnitTest;

/**
 * Class AbstractBase
 * Common setup for unit tests to hook in to the PHP_CodeSniffer testing structure
 *
 * @author Ed Barnard
 * @license MIT
 */
abstract class AbstractBase extends AbstractSniffUnitTest {
	protected function setUp() {
		parent::setUp();
		$config = new Config();
		/** @noinspection PhpUndefinedFieldInspection */
		$config->cache = false;
		$ruleset = new Ruleset($config);
		$GLOBALS['PHP_CODESNIFFER_RULESET'] = $ruleset;
	}

}
