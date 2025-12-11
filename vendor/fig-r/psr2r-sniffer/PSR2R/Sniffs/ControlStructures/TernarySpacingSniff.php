<?php

namespace PSR2R\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Asserts single space between ternary operator parts (? and :) and surroundings.
 * Also asserts no whitespace between short ternary operator (?:), which was introduced in PHP 5.3.
 *
 * @see https://github.com/dereuromark/codesniffer-standards/blob/master/MyCakePHP/Sniffs/WhiteSpace/TernarySpacingSniff.php
 * @author Mark Scherer
 * @license MIT
 */
class TernarySpacingSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$this->assertSpaceBefore($phpcsFile, $stackPtr);
		$this->checkAfter($phpcsFile, $stackPtr);
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_INLINE_THEN];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function assertSpaceBefore(File $phpcsFile, $stackPtr) {
		$previous = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
		if ($stackPtr - $previous > 1) {
			$this->assertSingleSpaceBeforeIfNotMultiline($phpcsFile, $stackPtr, $previous);
			return;
		}

		$tokens = $phpcsFile->getTokens();
		$content = $tokens[$stackPtr]['content'];
		$error = 'There must be a single space before ternary operator part `' . $content . '`';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceBeforeInlineThen');
		if ($fix) {
			$phpcsFile->fixer->addContentBefore($stackPtr, ' ');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @param int $previous
	 * @return void
	 */
	protected function assertSingleSpaceBeforeIfNotMultiline(File $phpcsFile, $stackPtr, $previous) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['line'] !== $tokens[$previous]['line']) {
			return;
		}
		if ($tokens[$stackPtr - 1]['content'] === ' ') {
			return;
		}

		$error = 'There must be a single space instead of `' . $tokens[$stackPtr - 1]['content'] . '`';
		$fix = $phpcsFile->addFixableError($error, $stackPtr - 1, 'InvalidSpaceBefore');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $thenIndex
	 * @return void
	 */
	protected function checkAfter(File $phpcsFile, $thenIndex) {
		$elseIndex = $phpcsFile->findNext(T_INLINE_ELSE, $thenIndex + 1);
		if (!$elseIndex) {
			return;
		}

		// Skip for short ternary
		$prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $elseIndex - 1, null, true);
		if ($prevIndex === $thenIndex) {
			$this->assertNoSpaceBetween($phpcsFile, $thenIndex, $elseIndex);
		} else {
			$this->assertSpaceAfter($phpcsFile, $thenIndex);
			$this->assertSpaceBefore($phpcsFile, $elseIndex);
		}

		$this->assertSpaceAfter($phpcsFile, $elseIndex);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $thenIndex
	 * @param int $elseIndex
	 * @return void
	 */
	protected function assertNoSpaceBetween(File $phpcsFile, $thenIndex, $elseIndex) {
		if ($elseIndex - $thenIndex === 1) {
			return;
		}

		$error = 'There must be no space between ? and : for short ternary';
		$fix = $phpcsFile->addFixableError($error, $thenIndex, 'SpaceInlineElse');
		if (!$fix) {
			return;
		}

		for ($i = $thenIndex + 1; $i < $elseIndex; ++$i) {
			$phpcsFile->fixer->replaceToken($i, '');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function assertSpaceAfter(File $phpcsFile, $stackPtr) {
		$nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($nextIndex - $stackPtr > 1) {
			$this->assertSingleSpaceAfterIfNotMultiline($phpcsFile, $stackPtr, $nextIndex);
			return;
		}

		$tokens = $phpcsFile->getTokens();
		$content = $tokens[$stackPtr]['content'];
		$error = 'There must be a single space after ternary operator part `' . $content . '`';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterInlineThen');
		if ($fix) {
			$phpcsFile->fixer->addContent($stackPtr, ' ');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @param int $next
	 * @return void
	 */
	protected function assertSingleSpaceAfterIfNotMultiline(File $phpcsFile, $stackPtr, $next) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['line'] !== $tokens[$next]['line']) {
			return;
		}
		if ($tokens[$stackPtr + 1]['content'] === ' ') {
			return;
		}

		$error = 'There must be a single space instead of `' . $tokens[$stackPtr + 1]['content'] . '`';
		$fix = $phpcsFile->addFixableError($error, $stackPtr + 1, 'InvalidSpaceAfter');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
		}
	}

}
