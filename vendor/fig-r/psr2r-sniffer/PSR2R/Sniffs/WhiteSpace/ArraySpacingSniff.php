<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * No whitespace should be at the beginning and end of an array.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ArraySpacingSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$endIndex = $tokens[$stackPtr]['bracket_closer'];
		$this->checkBeginning($phpcsFile, $stackPtr);
		$this->checkEnding($phpcsFile, $endIndex);
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_OPEN_SHORT_ARRAY];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function checkBeginning(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if ($nextIndex - $stackPtr === 1) {
			return;
		}
		if ($tokens[$nextIndex]['line'] !== $tokens[$stackPtr]['line']) {
			return;
		}

		$fix = $phpcsFile->addFixableError('No whitespace after opening bracket', $stackPtr, 'InvalidAfter');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($nextIndex - 1, '');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function checkEnding(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$previousIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
		if ($stackPtr - $previousIndex === 1) {
			return;
		}
		if ($tokens[$previousIndex]['line'] !== $tokens[$stackPtr]['line']) {
			return;
		}

		// Let another sniffer take care of invalid commas
		if ($tokens[$previousIndex]['code'] === T_COMMA) {
			return;
		}

		$fix = $phpcsFile->addFixableError('No whitespace before closing bracket', $stackPtr, 'InvalidBefore');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($previousIndex + 1, '');
		}
	}

}
