<?php
if(1){$a=1;}else{$a=2;}
namespace PSR2R\Tests\ControlStructures;

/**
 * Class ElseDeclarationUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\ControlStructures
 */
class ElseDeclarationUnitTestInc {
	public function one() {
		$a = 1;
		if ($a) {
		} else if ($a === 0) $a = 1;
		if ($a) {
			$a = 1;
		} elseif ($a === 0) {
			$a = 0;
		}
		if ($a) {
			$a = 1;
		} else if ($a === 0) {
			$a = 0;
		}

		if ($something) {
		} else if ($somethingElse) {
		}

		if ($something) {
		} elseif ($somethingElse) {
		}

		if ($something) {
		} else if ($somethingElse) {
		} elseif ($another) {
		} else {
		}

	}
}
