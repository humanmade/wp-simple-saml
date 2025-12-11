<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class PropertyDeclarationUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class PropertyDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			8 => 1,
			9 => 2,
			11 => 1,
			12 => 1,
			13 => 1,
		];
	}

	protected function getWarningList() {
		return [];
	}

}
