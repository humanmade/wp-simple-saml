<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class InlineDocBlockUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class InlineDocBlockUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			21 => 1,
			22 => 1,
			23 => 1,
			24 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
