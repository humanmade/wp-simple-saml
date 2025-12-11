<?php

namespace PSR2R\Tests\Namespaces;

use PSR2R\Base\AbstractBase;

/**
 * Class NoInlineFullyQualifiedClassNameUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NoInlineFullyQualifiedClassNameUnitTest extends AbstractBase {
	protected function getErrorList($file = '') {
		switch ($file) {
			case 'NoInlineFullyQualifiedClassNameUnitTest.1.inc':
				return [
					12 => 1,
					13 => 1,
					18 => 1,
					23 => 1,
				];
			case 'NoInlineFullyQualifiedClassNameUnitTest.2.inc':
				return [
					14 => 1,
					20 => 1,
					25 => 1,
				];
			case 'NoInlineFullyQualifiedClassNameUnitTest.3.inc':
				return [
					14 => 1,
					15 => 1,
					16 => 2,
				];
			case 'NoInlineFullyQualifiedClassNameUnitTest.4.inc':
				return [
					14 => 1,
				];
		}
		return [];
	}

	protected function getWarningList() {
		return [
		];
	}

}
