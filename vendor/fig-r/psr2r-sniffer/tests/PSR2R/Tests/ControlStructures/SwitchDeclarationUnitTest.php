<?php

namespace PSR2R\Tests\ControlStructures;

use PSR2R\Base\AbstractBase;

/**
 * Class SwitchDeclarationUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class SwitchDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			22 => 1,
			23 => 1,
			26 => 1,
			32 => 1,
			35 => 1,
			49 => 2,
			52 => 1,
			120 => 1,
			142 => 1,
			154 => 1,
			157 => 1,
			172 => 1,
			184 => 1,
			206 => 1,
			220 => 1,
			236 => 1,
			248 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
