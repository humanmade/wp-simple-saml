<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockVarWithoutNameUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocBlockVarWithoutNameUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			6 => 1,
			8 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
