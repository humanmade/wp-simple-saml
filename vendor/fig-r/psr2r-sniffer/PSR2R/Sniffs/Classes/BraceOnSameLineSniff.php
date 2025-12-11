<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Makes sure opening braces are on the same line for class, interface and trait.
 *
 * @author Mark Scherer
 * @license MIT
 */
class BraceOnSameLineSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$errorData = [strtolower($tokens[$stackPtr]['content'])];

		if (isset($tokens[$stackPtr]['scope_opener']) === false) {
			return;
		}

		$curlyBrace = $tokens[$stackPtr]['scope_opener'];
		$lastContent = $phpcsFile->findPrevious(T_WHITESPACE, $curlyBrace - 1, $stackPtr, true);
		$classLine = $tokens[$lastContent]['line'];
		$braceLine = $tokens[$curlyBrace]['line'];
		if ($braceLine !== $classLine) {
			$phpcsFile->recordMetric($stackPtr, 'Class opening brace placement', 'same line');
			$error = 'Opening brace of a %s must be on the same line as the definition';

			$fix = $phpcsFile->addFixableError($error, $curlyBrace, 'OpenBraceNewLine', $errorData);
			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken($lastContent, $tokens[$lastContent]['content'] . ' ');

				for ($i = $lastContent + 1; $i < $curlyBrace; $i++) {
					$phpcsFile->fixer->replaceToken($i, '');
				}
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
			T_FUNCTION,
		];
	}

}
