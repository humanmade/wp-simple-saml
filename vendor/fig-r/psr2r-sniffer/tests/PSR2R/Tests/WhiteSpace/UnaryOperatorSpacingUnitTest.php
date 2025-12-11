<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class UnaryOperatorSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class UnaryOperatorSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			16 => 1,
			17 => 1,
			18 => 1,
			20 => 1,
			21 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
