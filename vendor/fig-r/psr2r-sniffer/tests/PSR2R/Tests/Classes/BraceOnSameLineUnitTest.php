<?php

namespace PSR2R\Tests\Classes;

use PSR2R\Base\AbstractBase;

/**
 * Class BraceOnSameLineUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class BraceOnSameLineUnitTest extends AbstractBase {
	protected function getErrorList($file = '') {
		switch ($file) {
			case 'BraceOnSameLineUnitTest.1.inc':
				return [];
			case 'BraceOnSameLineUnitTest.2.inc':
				return [
					6 => 1,
					14 => 1,
					22 => 1,
					31 => 1,
					34 => 1,
				];
		}
		return [];
	}

	protected function getWarningList($file = '') {
		return [];
	}

}
