<?php

namespace PSR2R\Tests\ControlStructures;

/**
 * Class NoInlineAssignmentUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\ControlStructures
 */
class NoInlineAssignmentUnitTestInc {
	public function one() {
		$a = 1;
		if($a = 1)$a=2;
		while($a = 0)$a=0;
		if($a ==2)
			$a=2;
		while($a)
			$a=0;
		while(true):
			$a=0;
		endwhile;
		foreach ([] as $b) echo PHP_EOL;
	}
}
