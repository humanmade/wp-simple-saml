<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class TabAndSpaceUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class TabAndSpaceUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
			14 => 1,
			15 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
