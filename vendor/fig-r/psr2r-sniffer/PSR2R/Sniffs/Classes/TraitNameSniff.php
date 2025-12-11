<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Checks that Traits are always used with "Trait".
 */
class TraitNameSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nameIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		$name = $tokens[$nameIndex]['content'];
		if (substr($name, -5) === 'Trait') {
			return;
		}

		$warn = 'Trait names should always have the suffix "Trait"';
		$phpcsFile->addWarning($warn, $nameIndex, 'MissingSuffix');
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_TRAIT];
	}

}
