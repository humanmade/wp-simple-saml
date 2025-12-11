<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockReturnVoidUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockReturnVoidUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			8 => 1,
			15 => 1,
			38 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
