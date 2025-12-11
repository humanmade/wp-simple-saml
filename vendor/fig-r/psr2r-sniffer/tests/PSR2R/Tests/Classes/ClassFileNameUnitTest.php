<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class ClassFileNameUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ClassFileNameUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [5 => 1];
	}

	protected function getWarningList() {
		return [];
	}

}
