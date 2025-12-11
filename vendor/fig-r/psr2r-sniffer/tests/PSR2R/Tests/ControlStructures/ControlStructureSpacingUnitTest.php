<?php

namespace PSR2R\Tests\ControlStructures;

use PSR2R\Base\AbstractBase;

/**
 * Class ControlStructureSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ControlStructureSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			23 => 2,
			25 => 2,
			26 => 2,
			28 => 1,
			31 => 1,
			33 => 1,
			45 => 2,
			46 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
