<?php

namespace Fixtures\NoInlineFullyQualifiedClassNameSniff\Input;

use Php\Foo\Bar;
use DateTime;
use X\Y\Z;
use Faa\SomeTrait;

class TestClass1Input extends \Foo\Bar implements \Bar\Baz, \Bar\Xxx {

	use SomeTrait;

	/**
	 * @return void
	 */
	public function fixMe() {
		$a = function () use ($x) {
			return $x;
		};

		$x = new \Event\Event('Controller.initialize');
		if ($x) {
			throw new \Exception();
		} else {
			throw new \Exception();
		}
	}

}
