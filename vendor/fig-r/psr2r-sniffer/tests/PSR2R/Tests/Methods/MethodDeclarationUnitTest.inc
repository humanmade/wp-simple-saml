<?php

namespace PSR2R\Tests\Methods;

/**
 * Class MethodDeclarationUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\Methods
 */
class MethodDeclarationUnitTestInc {
	function one() {}
    function _myFunction() {}
    private function myFunction() {}
    function __myFunction() {}
    public static function myFunction() {}
    static public function myFunction() {}
    final public function myFunction() {}
    public final function myFunction() {}
    abstract private function myFunction() {}
    private abstract function myFunction() {}
    final public static function myFunction() {}
    static protected final abstract function myFunction() {}
    public function _() {}
}
