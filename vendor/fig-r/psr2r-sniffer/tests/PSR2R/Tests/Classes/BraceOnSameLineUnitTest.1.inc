<?php
namespace PSR2R\Tests\Classes;

class ShouldNotFail {

	/**
	 * @param string $foo
	 * @param array $b
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
	public function report($foo, $bar = null) {
	}

	/**
	 * Detect missing param
	 *
	 * @param \Foo\Bar\Foo
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
	 * @param int $threshold ;
	 * @param bool $re       ; Re
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
