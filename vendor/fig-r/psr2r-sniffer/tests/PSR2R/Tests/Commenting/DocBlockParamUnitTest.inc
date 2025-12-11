<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * Class DocBlockParamUnitTestInc
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Commenting
 */
class DocBlockParamUnitTestInc {
	/**
	 * @param $arr
	 * @return int
	 */
	public function one(array $arr = null, $scalar = 1) {
		return true;
	}
}
