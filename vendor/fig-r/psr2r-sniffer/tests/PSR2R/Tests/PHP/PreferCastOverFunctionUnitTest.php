<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class PreferCastOverFunctionUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class PreferCastOverFunctionUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			14 => 1,
			16 => 1,
			17 => 1,
			18 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
