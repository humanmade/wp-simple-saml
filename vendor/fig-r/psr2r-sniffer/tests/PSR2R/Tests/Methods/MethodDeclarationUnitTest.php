<?php

namespace PSR2R\Tests\Methods;

use PSR2R\Base\AbstractBase;

/**
 * Class MethodDeclarationUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class MethodDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			18 => 1,
			20 => 1,
			22 => 1,
			24 => 3,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
