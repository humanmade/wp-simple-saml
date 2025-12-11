<?php

namespace PSR2R\Tests\WhiteSpace;

/**
 * Class ConcatenationSpacingUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\WhiteSpace
 */
class ConcatenationSpacingUnitTestInc {
	public function one() {
		$a = 'a'.1.'a';
		$a .= "a" .
			'b';
		$a .= 'a'. 'b';
		$a .= 'c' . __LINE__;
	}
}
