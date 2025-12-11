<?php

namespace PSR2R\Tests\PHP;

/**
 * Class PreferCastOverFunctionUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class PreferCastOverFunctionUnitTestInc {
	public function one() {
		$a = strval(1);
		$b = intval(1, 10);
		$c = intval(1);
		$d = floatval(1);
		$e = boolval(1);
	}
}
