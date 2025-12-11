<?php

namespace Fixtures\PhpSapiConstantSniff\Input;

class TestClass1Input {

	/**
     * @return void
     */
	public function replaceFunction() {
		$foo = PHP_SAPI;
		$foo = substr(PHP_SAPI, 0, 3);
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
