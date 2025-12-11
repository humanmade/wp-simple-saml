<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class ConcatenationSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ConcatenationSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			14 => 2,
			17 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
