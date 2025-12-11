<?php

namespace PSR2R\Tests\Namespaces;

use PSR2R\Base\AbstractBase;

/**
 * Class UseInAlphabeticalOrderUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class UseInAlphabeticalOrderUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			6 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
