<?php

namespace PSR2R\Tests\ControlStructures;

use PSR2R\Base\AbstractBase;

/**
 * Class ElseIfDeclarationUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ElseIfDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
		];
	}

	protected function getWarningList() {
		return [
			16 => 1,
			24 => 1,
			29 => 1,
			37 => 1,
		];
	}

}
