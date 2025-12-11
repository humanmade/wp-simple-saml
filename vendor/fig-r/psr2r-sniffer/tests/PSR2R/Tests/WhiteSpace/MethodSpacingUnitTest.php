<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class MethodSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class MethodSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 2,
			16 => 3,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
