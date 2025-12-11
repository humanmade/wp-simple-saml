<?php

namespace PSR2R\Tests\PHP;

/**
 * Class ListCommaUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class ListCommaUnitTestInc {
	public function one() {
		list($a,, $b) = [];
		list($a, ,$b) = [];
		list($a, $b,,) = [];
		list( $a,$b, , ) = [];
	}
}
