<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Checks that the method declaration has correct spacing.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MethodSpacingSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$stringIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($tokens[$stringIndex]['code'] !== T_STRING) {
			return;
		}

		$parenthesisIndex = $phpcsFile->findNext(T_WHITESPACE, $stringIndex + 1, null, true);
		if ($tokens[$parenthesisIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
			return;
		}

		if ($parenthesisIndex - $stringIndex !== 1) {
			$error = 'There should be no space between method name and opening parenthesis';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'ContentBeforeOpen');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($parenthesisIndex - 1, '');
			}
		}

		$parenthesisEndIndex = $tokens[$parenthesisIndex]['parenthesis_closer'];

		$braceStartIndex = $phpcsFile->findNext(T_WHITESPACE, $parenthesisEndIndex + 1, null, true);
		if ($tokens[$braceStartIndex]['code'] !== T_OPEN_CURLY_BRACKET) {
			return;
		}

		$braceEndIndex = $tokens[$braceStartIndex]['bracket_closer'];
		$nextContentIndex = $phpcsFile->findNext(T_WHITESPACE, $braceStartIndex + 1, null, true);
		if ($nextContentIndex === $braceEndIndex) {
			$this->assertNoAdditionalNewlinesForEmptyBody($phpcsFile, $braceStartIndex, $braceEndIndex);
			return;
		}

		if ($tokens[$nextContentIndex]['line'] - $tokens[$braceStartIndex]['line'] > 1) {
			$error = 'There should be no extra newline at beginning of a method';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'ContentAfterOpen');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($braceStartIndex + 1, '');
			}
		}

		$lastContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $braceEndIndex - 1, null, true);

		if ($tokens[$braceEndIndex]['line'] - $tokens[$lastContentIndex]['line'] > 1) {
			$error = 'There should be no extra newline at the end of a method';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'ContentBeforeClose');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($lastContentIndex + 1, '');
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_FUNCTION];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $from
	 * @param int $to
	 *
	 * @return void
	 */
	protected function assertNoAdditionalNewlinesForEmptyBody(File $phpcsFile, $from, $to) {
		$tokens = $phpcsFile->getTokens();

		$startLine = $tokens[$from]['line'];
		$endLine = $tokens[$to]['line'];
		if ($endLine === $startLine + 2) {
			$error = 'There should be no extra newline in empty methods';
			if ($phpcsFile->addFixableError($error, $from, 'ContentEmpty')) {
				$phpcsFile->fixer->replaceToken($from + 1, '');
			}
		}
	}

}
