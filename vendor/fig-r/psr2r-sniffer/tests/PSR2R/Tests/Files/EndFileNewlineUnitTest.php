<?php

namespace PSR2R\Tests\Files;

use PSR2R\Base\AbstractBase;

/**
 * Class EndFileNewlineUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Files
 */
class EndFileNewlineUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}
}
