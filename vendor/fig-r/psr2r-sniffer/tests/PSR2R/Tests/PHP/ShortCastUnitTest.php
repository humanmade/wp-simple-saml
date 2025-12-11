<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class ShortCastUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ShortCastUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
			17 => 1,
			18 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
