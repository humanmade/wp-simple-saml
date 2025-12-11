<?php

namespace PSR2R\Tests\ControlStructures;

/**
 * Class ConditionalExpressionOrderUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\ControlStructures
 */
class ConditionalExpressionOrderUnitTestInc {
	protected function one() {
		$a = 2;
		$b = 1;
		if (true === $a) {
			$a = 1;
		}
		if (1 === $a) {
			$a = 2;
		}
		if ('1' == $a) {
			$a = 3;
		}
		if ($b === 1) {
			$b = 2;
		}
	}
}
