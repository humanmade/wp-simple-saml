<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class ArraySpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ArraySpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 2,
			26 => 2,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
