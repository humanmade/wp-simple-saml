<?php
/**
 * PHP Version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://pear.php.net/package/PHP_CodeSniffer_CakePHP
 * @since         CakePHP CodeSniffer 0.1.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Makes sure there are spaces between the concatenation operator (.) and
 * the strings being concatenated.
 */
class ConcatenationSpacingSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);

		if ($tokens[$stackPtr - 1]['code'] !== T_WHITESPACE) {
			$message = 'Expected 1 space before ., but 0 found';
			$phpcsFile->addFixableError($message, $stackPtr, 'MissingBefore');
			$this->addSpace($phpcsFile, $stackPtr - 1);
		} else {
			$content = $tokens[$stackPtr - 1]['content'];
			if ($content !== ' ' && $tokens[$prevIndex]['line'] === $tokens[$stackPtr]['line']) {
				$message = 'Expected 1 space before `.`, but %d found';
				$data = [strlen($content)];
				$fix = $phpcsFile->addFixableError($message, $stackPtr, 'TooManyBefore', $data);
				if ($fix) {
					$phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
				}
			}
		}

		$nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

		if ($tokens[$stackPtr + 1]['code'] !== T_WHITESPACE) {
			$message = 'Expected 1 space after ., but 0 found';
			$phpcsFile->addFixableError($message, $stackPtr, 'MissingAfter');
			$this->addSpace($phpcsFile, $stackPtr);
		} else {
			$content = $tokens[$stackPtr + 1]['content'];
			if ($content !== ' ' && $tokens[$nextIndex]['line'] === $tokens[$stackPtr]['line']) {
				$message = 'Expected 1 space after `.`, but %d found';
				$data = [strlen($content)];
				$fix = $phpcsFile->addFixableError($message, $stackPtr, 'TooManyAfter', $data);
				if ($fix) {
					$phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_STRING_CONCAT];
	}

	/**
	 * Add a single space on the right sight.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 *
	 * @return void
	 */
	protected function addSpace(File $phpcsFile, $index) {
		if ($phpcsFile->fixer->enabled !== true) {
			return;
		}
		$phpcsFile->fixer->addContent($index, ' ');
	}

}
