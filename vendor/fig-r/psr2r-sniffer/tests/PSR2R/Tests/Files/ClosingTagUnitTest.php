<?php

namespace PSR2R\Tests\Files;

use PSR2R\Base\AbstractBase;

/**
 * Class ClosingTagUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Files
 */
class ClosingTagUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			3 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}
}
