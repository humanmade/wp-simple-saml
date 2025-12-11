<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Always have an extra new line at the beginning and end of a classy body.
 *
 * @author Mark Scherer
 * @license MIT
 */
class EmptyEnclosingLineSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$errorData = [strtolower($tokens[$stackPtr]['content'])];

		if (isset($tokens[$stackPtr]['scope_opener']) === false) {
			$error = 'Possible parse error: %s missing opening or closing brace';
			$phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $errorData);
			return;
		}

		$curlyBraceStartIndex = $tokens[$stackPtr]['scope_opener'];
		$curlyBraceEndIndex = $tokens[$stackPtr]['scope_closer'];

		$lastContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $curlyBraceEndIndex - 1, $stackPtr, true);

		if ($lastContentIndex === $curlyBraceStartIndex) {
			// Single new line for empty classes
			if ($tokens[$curlyBraceEndIndex]['line'] === $tokens[$curlyBraceStartIndex]['line'] + 1) {
				return;
			}

			$error = 'Closing brace of an empty %s must have a single new line between curly brackets';

			$fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine', $errorData);
			if ($fix === true) {
				if ($curlyBraceEndIndex - $curlyBraceStartIndex === 1) {
					$phpcsFile->fixer->addNewline($curlyBraceStartIndex);
				} else {
					$phpcsFile->fixer->replaceToken($curlyBraceStartIndex + 1, '');
					$phpcsFile->fixer->addNewline($curlyBraceStartIndex + 1);
				}
			}
			return;
		}

		$contentLine = $tokens[$lastContentIndex]['line'];
		$braceLine = $tokens[$curlyBraceEndIndex]['line'];

		if ($braceLine !== $contentLine + 2) {
			$phpcsFile->recordMetric($stackPtr, 'Class closing brace placement', 'lines');
			$error = 'Closing brace of a %s must have a new line between itself and the last content.';

			$fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine2', $errorData);
			if ($fix === true) {
				if ($braceLine < $contentLine + 2) {
					$phpcsFile->fixer->addNewlineBefore($curlyBraceEndIndex);
				} else {
					$phpcsFile->fixer->replaceToken($curlyBraceEndIndex - 1, '');
				}
			}
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
		];
	}

}
