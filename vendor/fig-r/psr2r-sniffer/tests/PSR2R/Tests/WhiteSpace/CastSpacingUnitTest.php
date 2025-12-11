<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class CastSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class CastSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			14 => 1,
			15 => 1,
			16 => 1,
			19 => 1,
			20 => 1,
			22 => 1,
			23 => 1,
			24 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
