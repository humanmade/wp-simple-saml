<?php

namespace PSR2R\Tests\PHP;

/**
 * Class PreferStaticOverSelfUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class PreferStaticOverSelfUnitTestInc {
	private static $a = 1;
	public static function one() {
		return self::$a;
	}

	public function two() {
		$b = static::$a;
		$c = static::one();
		$d = self::one();
		$e = self::$a;
		$f = new self;
		$g = new self();
		$h = new static;
		$i = new static();
	}
}
