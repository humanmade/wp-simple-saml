<?php

namespace PSR2R\Tests\PHP;

/**
 * Class ShortCastUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class ShortCastUnitTestInc {
	public function one() {
		$a = (int)1;
		$b = (integer)1;
		$c = (bool)1;
		$d = (boolean)1;
		$e = !!1;
	}
}
