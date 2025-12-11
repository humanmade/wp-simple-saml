<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class ListCommaUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ListCommaUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			16 => 1,
			17 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
