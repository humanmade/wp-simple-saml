<?php

namespace PSR2R\Tests\Methods;

use PSR2R\Base\AbstractBase;

/**
 * Class MethodMultilineArgumentsUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class MethodMultilineArgumentsUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			4 => 2,
			5 => 1,
			10 => 2,
			11 => 1,
			26 => 2,
			27 => 1,
			32 => 2,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
