<?php

namespace Fixtures\DocBlockParamSniff\Input;

class TestClass1Input {

	/**
	 * @param string $foo
	 * @param array $bar
	 *
	 * @return void
	 */
	public function replaceMe($foo, $bar) {
	}

	/**
	 * Detect missing param
	 *
	 * @param string $foo
	 *
	 * @return void
	 */
	public function reportMe($foo, $bar = null) {
	}

	/**
	 * Detect missing param
	 *
	 * @param \Foo\Bar\Foo $foo
	 *
	 * @return void
	 */
	public function correctMe2($foo) {
	}

	/**
	 * This is OK for this sniff
	 *
	 * @param $foo
	 *
	 * @return void
	 */
	public function ok($foo) {
	}

	/**
	 * @param int $threshold
	 * @param bool $re  Re
	 */
	public function __construct($threshold, $re) {
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return void
	 */
	public function ignoreMe(Foo $foo) {
	}

}
