<?php

namespace PSR2R\Tests\Namespaces;

use PSR2R\Base\AbstractBase;

/**
 * Class NamespaceDeclarationUnitTest
 *
 * @author Ed Barnard
 * @license MIT
 */
class NamespaceDeclarationUnitTest extends AbstractBase {
	protected function getErrorList() {
		return [
			2 => 1,
		];
	}

	protected function getWarningList() {
		return [
		];
	}

}
