<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class DuplicateSemicolonUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DuplicateSemicolonUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			3 => 1,
			5 => 1,
			18 => 1,
			23 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
