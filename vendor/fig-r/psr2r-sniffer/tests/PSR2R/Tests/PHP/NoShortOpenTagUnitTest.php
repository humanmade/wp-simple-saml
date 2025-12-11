<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class NoShortOpenTagUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NoShortOpenTagUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			2 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
