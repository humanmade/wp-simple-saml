<?php

namespace PSR2R\Tests\Namespaces;

use PHP_CodeSniffer\Util\Tokens;

/**
 * Class NoInlineFullyQualifiedClassNameUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Namespaces
 */
class NoInlineFullyQualifiedClassNameUnitTestInc extends \PSR2R\Base\AbstractBase {
	protected function getErrorList(Tokens $tokens = null) {
		return [
		];
	}

	protected function getOtherList(\Other\Names\Tokens $tokens = null) {
		return [
		];
	}

	protected function getWarningList(\PHP_CodeSniffer\Files\File $file = null) {
		return [
		];
	}
}
