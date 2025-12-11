<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocCommentUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class DocCommentUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			5 => 3,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
