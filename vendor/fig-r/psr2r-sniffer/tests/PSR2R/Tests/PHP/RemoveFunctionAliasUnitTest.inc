<?php

namespace PSR2R\Tests\PHP;

/**
 * Class RemoveFunctionAliasUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class RemoveFunctionAliasUnitTestInc {
	public function one() {
		$a = is_integer(1);
		$b = is_long(1);
		$c = is_real(1);
		$d = is_double(1);
		$e = is_writeable(1);
		$f = join('', []);
		$g = key_exists('a', []);
		$h = sizeof([1], COUNT_RECURSIVE);
		$i = sizeof([1]);
		$j = strchr('a', 'a', true);
		$k = strchr('a', 'a');
		$l = ini_alter('memory_limit', -1);
		$m = fputs(null, 'a', 1);
		$o = chop('a', 'a');
		$n = die('now');
	}
}
