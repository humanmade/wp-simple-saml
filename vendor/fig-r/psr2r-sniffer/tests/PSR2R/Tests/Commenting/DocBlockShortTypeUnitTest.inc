<?php

namespace PSR2R\Tests\Commenting;

class DocBlockShortTypeUnitTestInc {
	/**
	 * DocBlockShortTypeUnitTestInc constructor.
	 *
	 * @param integer $a
	 * @param boolean $b
	 */
	public function __construct($a = 1, $b = true) {

	}

	/**
	 * @param array|null $a
	 * @param int $b
	 * @param array $c
	 * @return boolean
	 */
	public function one(array $a = null, $b = 1, $c = []) {

	}

	/**
	 * @param integer $a
	 * @param bool $b
	 * @param boolean $c
	 * @return integer
	 */
	public function two($a, $b, $c) {
		return $a;
	}
}
