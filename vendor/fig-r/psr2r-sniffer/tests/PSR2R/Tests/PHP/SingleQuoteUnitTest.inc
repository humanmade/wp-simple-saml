<?php

namespace PSR2R\Tests\PHP;

/**
 * Class SingleQuoteUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class SingleQuoteUnitTestInc {
	private static $a = "a";
	private static $b = "O'Neil";
	private static $c = "
	select";
	private static $d = "\n";

	public function one() {
		$a = "a";
		$b = 'b';
		$c = 'O\'Neil';
		$d = "O'Neil";
		$e = "
		select 1";
		$f = "\n";
		$g = "a" . 'b' . 'O\'Neil' . "O'Neil";
	}
}
