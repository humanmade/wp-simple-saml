<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class NoIsNullUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NoIsNullUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
			16 => 1,
			17 => 1,
			20 => 1,
			23 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
