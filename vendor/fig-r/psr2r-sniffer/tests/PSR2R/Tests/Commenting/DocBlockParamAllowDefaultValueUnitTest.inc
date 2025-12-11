<?php

namespace PSR2R\Tests\Commenting;

class DocBlockParamAllowDefaultValueUnitTestInc {
	/**
	 * @param array|null $parms
	 */
	public function one(array $parms = null) {
	}
	/**
	 * @param array $parms
	 */
	public function two(array $parms = null) {
	}
}
