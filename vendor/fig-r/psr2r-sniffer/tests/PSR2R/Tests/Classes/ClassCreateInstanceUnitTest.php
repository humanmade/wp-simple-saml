<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class ClassCreateInstanceUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class ClassCreateInstanceUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [9 => 1];
	}

	protected function getWarningList() {
		return [];
	}

}
