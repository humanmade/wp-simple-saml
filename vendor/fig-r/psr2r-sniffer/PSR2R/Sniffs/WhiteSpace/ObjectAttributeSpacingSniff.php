<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Verifies that that object operator and class double colon have no additional whitespace around.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ObjectAttributeSpacingSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Make sure there is no space before.
		$previousToken = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);

		if ($stackPtr - $previousToken !== 1 && $tokens[$previousToken]['line'] === $tokens[$stackPtr]['line']) {
			$error = 'Expected no space before object operator';
			$phpcsFile->addFixableError($error, $stackPtr - 1, 'TooMany');
			if ($phpcsFile->fixer->enabled === true) {
				$phpcsFile->fixer->replaceToken($stackPtr - 1, '');
			}
		}

		// Make sure there is no space after.
		$nextToken = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

		if ($nextToken - $stackPtr !== 1 && $tokens[$nextToken]['line'] === $tokens[$stackPtr]['line']) {
			$error = 'Expected no space after object operator';
			$phpcsFile->addFixableError($error, $stackPtr + 1, 'TooMany');
			if ($phpcsFile->fixer->enabled === true) {
				$phpcsFile->fixer->replaceToken($stackPtr + 1, '');
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_OBJECT_OPERATOR, T_DOUBLE_COLON];
	}

}
