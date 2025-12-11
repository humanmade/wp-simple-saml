<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockParamUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockParamUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			19 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
