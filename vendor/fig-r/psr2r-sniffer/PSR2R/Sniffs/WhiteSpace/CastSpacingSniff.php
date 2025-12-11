<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * No whitespace should be between cast and variable. Also account for implicit casts (!).
 *
 * @author Mark Scherer
 * @license MIT
 */
class CastSpacingSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

		if ($nextIndex - $stackPtr === 1) {
			return;
		}

		// Skip for !! casts, other sniffers take care of that
		$prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
		if ($tokens[$prevIndex]['code'] === T_BOOLEAN_NOT) {
			return;
		}

		$fix = $phpcsFile->addFixableError('No whitespace should be between cast and variable.', $stackPtr,
			'CastWhitespace');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr + 1, '');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return array_merge(Tokens::$castTokens, [T_BOOLEAN_NOT]);
	}

}
