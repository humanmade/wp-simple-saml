<?php

namespace PSR2R\Tests\WhiteSpace;

use PSR2R\Base\AbstractBase;

/**
 * Class LanguageConstructSpacingUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class LanguageConstructSpacingUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			16 => 1,
			17 => 1,
			20 => 1,
			21 => 1,
			25 => 1,
			26 => 1,
			29 => 1,
			30 => 1,
			34 => 1,
			35 => 1,
			40 => 1,
			41 => 1,
			46 => 1,
			47 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
