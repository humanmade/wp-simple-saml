<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures unnecessary comments, especially //end ... ones are removed.
 */
class NoControlStructureEndCommentSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_COMMENT];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$possibleCurlyBracket = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, 0, true);
		if ($possibleCurlyBracket === false || $tokens[$possibleCurlyBracket]['type'] !== 'T_CLOSE_CURLY_BRACKET') {
			return;
		}

		$content = $tokens[$stackPtr]['content'];
		if (strpos($content, '//end ') !== 0) {
			return;
		}

		$error = 'The unnecessary end comment must be removed';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'Unnecessary');
		if ($fix === true) {
			/** @noinspection NotOptimalRegularExpressionsInspection */
			$phpcsFile->fixer->replaceToken($stackPtr, preg_replace('/[^\s]/', '', $content));
		}
	}

}
