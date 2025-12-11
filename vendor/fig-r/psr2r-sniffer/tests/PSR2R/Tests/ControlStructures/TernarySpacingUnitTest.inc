<?php

namespace PSR2R\Tests\ControlStructures;

/**
 * Class TernarySpacingUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\ControlStructures
 */
class TernarySpacingUnitTestInc {
	public function one() {
		$a = true ? 1 : 0;
		$b=true?1:0;
		$c = true ?: 0;
		$d = false ? : 0;
	}
}
