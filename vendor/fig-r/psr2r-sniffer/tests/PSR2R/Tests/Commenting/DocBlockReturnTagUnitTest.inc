<?php

namespace PSR2R\Tests\Commenting;

use PSR2R\Base\AbstractBase;

/**
 * One
 */
function one() {
}
class DocBlockReturnTagUnitTestInc {
	/**
	 * DocBlockReturnTagUnitTestInc constructor
	 * @return $this
	 */
	public function __construct() {

	}

	/**
	 * @return void
	 */
	public function __destruct() {

	}

	/**
	 * Two
	 */
	public function two() {

	}
	/**
	 * @return void
	 */
	public function three() {

	}
}

class DocBlockReturnTagInherit extends DocBlockReturnTagUnitTestInc {
	/**
	 * @inheritDoc
	 */
	public function three() {
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function two() {
	}
}
