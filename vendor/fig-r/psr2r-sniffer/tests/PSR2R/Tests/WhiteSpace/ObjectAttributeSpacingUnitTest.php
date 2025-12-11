<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class ObjectAttributeSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ObjectAttributeSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			21 => 2,
			23 => 2,
			26 => 2,
			27 => 2,
			28 => 2,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
