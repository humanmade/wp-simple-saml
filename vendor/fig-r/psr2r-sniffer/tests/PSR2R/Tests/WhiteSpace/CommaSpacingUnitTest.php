<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class CommaSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class CommaSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 2,
			19 => 1,
			20 => 1,
			21 => 2,
			22 => 3,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
