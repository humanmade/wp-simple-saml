<?php

namespace PSR2R\Tests\Namespaces;

/**
 * Class NoInlineFullyQualifiedClassNameUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Namespaces
 */
class NoInlineFullyQualifiedClassNameUnitFourInc {
	public function one() {
		$result = \PHP_CodeSniffer\Util\Common::isCamelCaps('');
	}
}
