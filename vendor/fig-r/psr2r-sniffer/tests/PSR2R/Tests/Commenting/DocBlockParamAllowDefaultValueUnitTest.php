<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockParamAllowDefaultValueUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockParamAllowDefaultValueUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			12 => 1,
		];
	}

	protected function getWarningList() {
		return [];
	}

}
