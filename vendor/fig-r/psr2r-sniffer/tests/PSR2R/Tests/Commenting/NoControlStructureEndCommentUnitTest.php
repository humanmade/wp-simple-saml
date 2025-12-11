<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class NoControlStructureEndCommentUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NoControlStructureEndCommentUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			16 => 1,
			18 => 1,
			22 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
