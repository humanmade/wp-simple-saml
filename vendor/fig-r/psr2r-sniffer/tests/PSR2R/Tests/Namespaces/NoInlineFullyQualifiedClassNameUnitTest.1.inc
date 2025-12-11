<?php

namespace PSR2R\Tests\Namespaces;

/**
 * Class NoInlineFullyQualifiedClassNameUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Namespaces
 */
class NoInlineFullyQualifiedClassNameUnitTestInc extends \PSR2R\Base\AbstractBase {
	protected function getErrorList(\PHP_CodeSniffer\Util\Tokens $tokens = null) {
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
