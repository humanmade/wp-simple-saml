<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class InterfaceNameUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class InterfaceNameUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [];
	}

	protected function getWarningList() {
		return [13 => 1];
	}

}
