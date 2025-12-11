<?php

namespace PSR2R\Tests\WhiteSpace;

/**
 * Class CastSpacingUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\WhiteSpace
 */
class CastSpacingUnitTestInc {
	public static function one() {
		$a = (bool) 1;
		$b = (boolean)   1;
		$c = (bool)
		1
			;
		$d = (int) 1;
		$e = (float)  2;
		$f = (float)3;
		$g = (string) 4;
		$h = ! 1;
		$i = ! ! 1;
		$j = !1;
		$k = !!1;
	}
}
