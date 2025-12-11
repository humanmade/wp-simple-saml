<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class SelfAccessorUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class SelfAccessorUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
			26 => 1,
		];
	}

	protected function getWarningList() {
		return [];
	}

}
