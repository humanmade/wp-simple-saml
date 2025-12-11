<?php

namespace PSR2R\Tests\Namespaces;

/**
 * Class NoInlineFullyQualifiedClassNameUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Namespaces
 */
class NoInlineFullyQualifiedClassNameUnitThreeInc {
	public function one() {
		$config = new \PHP_CodeSniffer\Config();
		$extra = new \Other\Config();
		$object = new \PHP_CodeSniffer\Files\File('', new \PHP_CodeSniffer\Ruleset($config), $config);
	}
}
