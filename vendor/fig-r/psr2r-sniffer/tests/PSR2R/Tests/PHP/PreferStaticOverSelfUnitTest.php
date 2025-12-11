<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class PreferStaticOverSelfUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class PreferStaticOverSelfUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
			21 => 1,
			22 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
