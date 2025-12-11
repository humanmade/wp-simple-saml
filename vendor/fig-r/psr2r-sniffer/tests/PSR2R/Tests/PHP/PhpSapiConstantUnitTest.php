<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class PhpSapiConstantUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class PhpSapiConstantUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			17 => 1,
			18 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
