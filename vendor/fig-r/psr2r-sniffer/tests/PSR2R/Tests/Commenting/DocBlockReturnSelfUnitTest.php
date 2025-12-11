<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockReturnSelfUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockReturnSelfUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			22 => 5,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
