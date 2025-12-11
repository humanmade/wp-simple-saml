<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class TabIndentUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class TabIndentUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			5 => 1,
			13 => 1,
			14 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
