<?php

namespace PSR2R\Tests\ControlStructures;

use PSR2R\Base\AbstractBase;

/**
 * Class TernarySpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class TernarySpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 4,
			17 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
