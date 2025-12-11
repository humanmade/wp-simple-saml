<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockPipeSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockPipeSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
			19 => 1,
			25 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
