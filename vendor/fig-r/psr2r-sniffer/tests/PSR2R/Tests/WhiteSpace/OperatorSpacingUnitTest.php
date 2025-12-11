<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class OperatorSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class OperatorSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			16 => 2,
			17 => 2,
			22 => 20,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
