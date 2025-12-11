<?php

namespace PSR2R\Tests\PHP;

/**
 * Class PhpSapiConstantUnitTest
 *
 * @author  Ed Barnard
 * @license MIT
 * @package PSR2R\Tests\PHP
 */
class PhpSapiConstantUnitTestInc {
	/**
	 * @return void
	 */
	public function replaceFunction() {
		$foo = php_sapi_name();
		$foo = substr(php_sapi_name(), 0, 3);
	}

	/**
	 * Do not replace
	 *
	 * @return void
	 */
	public function php_sapi_name() {
		$foo = $this->php_sapi_name();
		$foo = php_sapi_name($foo);
	}
}
