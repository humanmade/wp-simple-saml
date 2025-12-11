<?php

namespace PSR2R\Tests\Commenting;

/**
 * Class NoControlStructureEndCommentUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Commenting
 */
class NoControlStructureEndCommentUnitTestInc {
	public function one() {
		while (true) {
			$a = 1;
		} //end while
	}
}//end class

class A {

} //end class

class B {

} // end class

class D {

} //end

class E {

} //end
