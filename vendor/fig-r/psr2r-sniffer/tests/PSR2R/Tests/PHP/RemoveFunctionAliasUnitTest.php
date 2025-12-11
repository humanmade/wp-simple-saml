<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class RemoveFunctionAliasUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class RemoveFunctionAliasUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			14 => 1,
			15 => 1,
			16 => 1,
			17 => 1,
			18 => 1,
			19 => 1,
			20 => 1,
			21 => 1,
			22 => 1,
			23 => 1,
			24 => 1,
			25 => 1,
			26 => 1,
			27 => 1,
			28 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
