<?php

namespace PSR2R\Tests\PHP;

use PSR2R\Base\AbstractBase;

/**
 * Class SingleQuoteUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class SingleQuoteUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			13 => 1,
			20 => 1,
			27 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
