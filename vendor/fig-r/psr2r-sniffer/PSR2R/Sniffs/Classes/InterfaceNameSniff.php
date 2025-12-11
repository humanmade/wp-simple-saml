<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Checks that Interfaces are always used with "Interface".
 */
class InterfaceNameSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nameIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		$name = $tokens[$nameIndex]['content'];
		if (substr($name, -9) === 'Interface') {
			return;
		}

		$warn = 'Interface names should always have the suffix "Interface"';
		$phpcsFile->addWarning($warn, $nameIndex, 'MissingSuffix');
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_INTERFACE];
	}

}
