<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class EmptyLinesUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class EmptyLinesUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			6 => 1,
			16 => 1,
			21 => 1,
			24 => 1,
			25 => 1,
			26 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
