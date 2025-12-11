<?php

namespace PSR2R\Tests\Namespaces;

use PSR2R\Base\AbstractBase;

/**
 * Class UnusedUseStatementUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class UnusedUseStatementUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
		];
	}

	protected function getWarningList() {
		return [
			5 => 1,
		];
	}

}
