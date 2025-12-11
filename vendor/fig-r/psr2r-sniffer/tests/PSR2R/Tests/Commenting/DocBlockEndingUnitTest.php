<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockEndingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockEndingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
			17 => 1,
		];
	}

	protected function getWarningList() {
		return [];
	}

}
