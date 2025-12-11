<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockVarUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockVarUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
			14 => 1,
			15 => 1,
			18 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
