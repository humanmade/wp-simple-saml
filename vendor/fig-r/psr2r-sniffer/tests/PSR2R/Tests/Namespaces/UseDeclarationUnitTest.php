<?php

namespace PSR2R\Tests\Namespaces;

use PSR2R\Base\AbstractBase;

/**
 * Class UseDeclarationUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 */
class UseDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			6 => 1,
			7 => 1,
			9 => 2,
			13 => 2,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
