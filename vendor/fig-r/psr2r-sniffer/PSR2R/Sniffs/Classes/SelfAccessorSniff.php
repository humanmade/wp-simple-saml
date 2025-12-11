<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Verifies that self:: is used inside the own class.
 *
 * A better and more complete version of Squiz.Classes.SelfMemberReference.
 *
 * @author Mark Scherer
 * @license MIT
 */
class SelfAccessorSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['code'] === T_SELF) {
			$this->checkSelf($phpcsFile, $stackPtr);
			return;
		}

		$startIndex = $tokens[$stackPtr]['scope_opener'];
		if (!$startIndex || empty($tokens[$stackPtr]['scope_closer'])) {
			return;
		}
		$endIndex = $tokens[$stackPtr]['scope_closer'];

		$nameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if ($tokens[$nameIndex]['code'] !== T_STRING) {
			return;
		}

		$name = $tokens[$nameIndex]['content'];

		for ($i = $startIndex + 1; $i < $endIndex; $i++) {
			if ($tokens[$i]['code'] === T_NEW) {
				$this->checkNew($phpcsFile, $i, $name);
				continue;
			}

			if ($tokens[$i]['code'] === T_DOUBLE_COLON) {
				$this->checkDoubleColon($phpcsFile, $i, $name);
				continue;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_SELF, T_CLASS, T_INTERFACE, T_TRAIT];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function checkSelf(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$content = $tokens[$stackPtr]['content'];
		if ($content === 'self') {
			return;
		}

		$fix = $phpcsFile->addFixableError('Expected `self::`, got `' . $content . '::`', $stackPtr, 'ExpectedSelf1');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr, 'self');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $i
	 * @param string $name
	 * @return void
	 */
	protected function checkNew(File $phpcsFile, $i, $name) {
		$tokens = $phpcsFile->getTokens();

		$nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, null, true);
		if ($tokens[$nextIndex]['code'] !== T_STRING) {
			return;
		}
		$openingBraceIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
		if ($tokens[$openingBraceIndex]['code'] !== T_OPEN_PARENTHESIS) {
			return;
		}

		$this->fixNameToSelf($phpcsFile, $nextIndex, $name);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @param string $name
	 * @return void
	 */
	protected function fixNameToSelf(File $phpcsFile, $index, $name) {
		$tokens = $phpcsFile->getTokens();

		$content = $tokens[$index]['content'];

		if (strtolower($content) !== strtolower($name)) {
			return;
		}

		$fix = $phpcsFile->addFixableError('Expected `self::`, got `' . $content . '::`', $index, 'ExpectedSelf2');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($index, 'self');
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $i
	 * @param string $name
	 * @return void
	 */
	protected function checkDoubleColon(File $phpcsFile, $i, $name) {
		$tokens = $phpcsFile->getTokens();

		$prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $i - 1, null, true);
		if ($tokens[$prevIndex]['code'] !== T_STRING) {
			return;
		}
		$possibleSeparatorIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $prevIndex - 1, null, true);
		if ($tokens[$possibleSeparatorIndex]['code'] === T_NS_SEPARATOR) {
			return;
		}

		$this->fixNameToSelf($phpcsFile, $prevIndex, $name);
	}

}
