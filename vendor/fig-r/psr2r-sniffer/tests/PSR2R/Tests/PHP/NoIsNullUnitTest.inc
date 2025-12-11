<?php

namespace PSR2R\Tests\PHP;

/**
 * Class NoIsNullUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class NoIsNullUnitTestInc {
	public function one() {
		$a = null;
		$b = is_null($a);
		$c = is_null($b) ? 'Null' : '';
		if (is_null($b)) {
			$c = 'Null';
		}
		if (!!is_null($b)) {
			$c = 'Null';
		}
		if ($c = is_null($b = $g)) {
			$d = 'Yuck';
		}
	}
}
