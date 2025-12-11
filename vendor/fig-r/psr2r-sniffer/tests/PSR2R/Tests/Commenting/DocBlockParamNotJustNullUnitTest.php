<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockParamNotJustNullUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockParamNotJustNullUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			7 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
