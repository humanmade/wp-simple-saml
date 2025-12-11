<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockAlignmentUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockAlignmentUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			7 => 1,
			15 => 1,
			19 => 1,
			33 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
