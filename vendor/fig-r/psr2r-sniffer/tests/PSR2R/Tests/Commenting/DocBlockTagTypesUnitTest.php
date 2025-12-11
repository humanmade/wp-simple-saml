<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockTagTypesUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockTagTypesUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
			17 => 1,
			18 => 1,
			19 => 1,
			20 => 1,
			21 => 1,
			22 => 1,
			23 => 1,
			26 => 1,
			28 => 1,
			76 => 1,
			78 => 1,
			79 => 1,
			80 => 1,
			81 => 1,
			82 => 1,
			83 => 1,
			84 => 1,
			87 => 1,
			89 => 1,
		];
	}

	protected function getWarningList() {
		return [
			16 => 1,
			77 => 1,
		];
	}

}
