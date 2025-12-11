<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures no whitespaces and one whitespace is placed around each comma.
 *
 * @author Mark Scherer
 * @license MIT
 */
class CommaSpacingSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$next = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		$this->checkNext($phpcsFile, $stackPtr, $next);

		$previous = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);

		if (($previous !== $stackPtr - 1) && $tokens[$previous]['code'] !== T_WHITESPACE) {
			if ($tokens[$previous]['code'] === T_COMMA) {
				return;
			}

			$error = 'Space before comma, expected none, though';
			$fix = $phpcsFile->addFixableError($error, $previous, 'SpaceBeforeComma');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($previous + 1, '');
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_COMMA];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @param int $next
	 * @return void
	 */
	public function checkNext(File $phpcsFile, $stackPtr, $next) {
		$tokens = $phpcsFile->getTokens();

		// Closing inline array should not have a comma before
		if ($tokens[$next]['code'] === T_CLOSE_SHORT_ARRAY && $tokens[$next]['line'] === $tokens[$stackPtr]['line']) {
			$error = 'Invalid comma before closing inline array end `]`.';
			$fix = $phpcsFile->addFixableError($error, $next, 'InvalidComma');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($stackPtr, '');
			}
			return;
		}

		if (($next !== $stackPtr + 2) && $tokens[$next]['code'] !== T_WHITESPACE) {
			// Last character in a line is ok.
			if ($tokens[$next]['line'] !== $tokens[$stackPtr]['line']) {
				return;
			}

			// Closing inline array is also ignored
			if ($tokens[$next]['code'] === T_CLOSE_SHORT_ARRAY) {
				return;
			}

			$error = 'Missing space after comma';
			$fix = $phpcsFile->addFixableError($error, $next, 'MissingCommaSpace');
			if ($fix) {
				$phpcsFile->fixer->addContent($stackPtr, ' ');
			}
		}
	}

}
