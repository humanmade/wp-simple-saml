<?php

namespace PSR2R\Tests\ControlStructures;

use PSR2R\Base\AbstractBase;

/**
 * Class NoInlineAssignmentUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NoInlineAssignmentUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			15 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
