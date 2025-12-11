<?php

namespace PSR2RT;

use PHPUnit_Framework_TestSuite;
use PHP_CodeSniffer\Autoload;
use PHP_CodeSniffer\Tests\PHP_CodeSniffer_AllTests;
use PHP_CodeSniffer\Tests\TestSuite;
use PSR2R\Tools\AbstractSniff;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class AllTests
 * Run all tests in this sniffer package
 * Hooks into the PHP_CodeSniffer unit-testing system
 *
 * @author Ed Barnard
 * @license MIT
 */
class AllTests extends PHP_CodeSniffer_AllTests {
	/**
	 * Add all PHP_CodeSniffer test suites into a single test suite.
	 *
	 * @return \PHPUnit_Framework_TestSuite
	 * @throws \PHPUnit_Framework_Exception
	 */
	public static function suite() {
		$GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS'] = [];
		$GLOBALS['PHP_CODESNIFFER_TEST_DIRS'] = [];

		// Use a special PHP_CodeSniffer test suite so that we can
		// unset our autoload function after the run.
		$suite = new TestSuite('PHP PSR2R CodeSniffer');
		$suite->addTest(static::psr2rSuite());

		return $suite;
	}

	/**
	 * Based on PHP_CodeSniffer/tests/Standards/AllSniffs.php
	 *
	 * @return \PHPUnit_Framework_TestSuite
	 * @throws \PHPUnit_Framework_Exception
	 */
	public static function psr2rSuite() {
		$GLOBALS['PHP_CODESNIFFER_SNIFF_CODES'] = [];
		$GLOBALS['PHP_CODESNIFFER_FIXABLE_CODES'] = [];

		$suite = new PHPUnit_Framework_TestSuite('PHP PSR2R Standards');
		$toolFile = $GLOBALS['finder']->findFile(AbstractSniff::class);
		$standardDir = dirname(dirname(realpath($toolFile)));
		$testsDir = __DIR__ . DIRECTORY_SEPARATOR . 'PSR2R' . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR;
		$di = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
		foreach ($di as $file) {
			// Skip hidden files
			if ($file->getFilename()[0] === '.') {
				continue;
			}

			// Tests must have the extension 'php'
			$parts = explode('.', $file);
			$ext = array_pop($parts);
			if ($ext !== 'php') {
				continue;
			}

			$className = Autoload::loadFile($file->getPathname());
			$GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS'][$className] = $standardDir;
			$GLOBALS['PHP_CODESNIFFER_TEST_DIRS'][$className] = $testsDir;
			$suite->addTestSuite($className);
		}
		return $suite;
	}

}
