<?php

namespace PSR2R\Tests\WhiteSpace;

/**
 * Class OperatorSpacingUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\WhiteSpace
 */
class OperatorSpacingUnitTestInc {
	public function one() {
		$a = 1;
		$a &= 3;
		$b = $a&3;
		$c = 5-3;
		$d = 5 - 3;
		$e = - 3;
		$f = -3;
		$g = floor(-3);
		$h = $a+$b|$c&$d^$e&!$f*$a**$b/$c-$d%$a;
	}
}
