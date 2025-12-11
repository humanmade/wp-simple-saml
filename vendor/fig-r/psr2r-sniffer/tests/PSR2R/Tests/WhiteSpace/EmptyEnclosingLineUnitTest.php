<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class EmptyEnclosingLineUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class EmptyEnclosingLineUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			7 => 1,
			8 => 1,
			9 => 1,
			24 => 1,
		];
	}

	protected function getWarningList() {
		return [
			25 => 1,
		];
	}

}
