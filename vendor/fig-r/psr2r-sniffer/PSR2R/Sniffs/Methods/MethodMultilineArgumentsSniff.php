<?php

namespace PSR2R\Sniffs\Methods;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Checks that the method declaration of arguments has a single one per line for multiline.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MethodMultilineArgumentsSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$stringIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($tokens[$stringIndex]['code'] !== T_STRING) {
			return;
		}

		$parenthesisStartIndex = $phpcsFile->findNext(T_WHITESPACE, $stringIndex + 1, null, true);
		if ($tokens[$parenthesisStartIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
			return;
		}
		if (empty($tokens[$parenthesisStartIndex]['parenthesis_closer'])) {
			return;
		}

		$parenthesisEndIndex = $tokens[$parenthesisStartIndex]['parenthesis_closer'];

		if ($tokens[$parenthesisEndIndex]['line'] === $tokens[$stackPtr]['line']) {
			return;
		}

		for ($i = $parenthesisStartIndex + 1; $i < $parenthesisEndIndex - 1; $i++) {
			if ($tokens[$i]['code'] !== T_VARIABLE) {
				continue;
			}

			$possibleCommaIndex = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
			if (!$possibleCommaIndex || $tokens[$possibleCommaIndex]['type'] !== 'T_COMMA') {
				continue;
			}

			if ($tokens[$possibleCommaIndex]['line'] !== $tokens[$i]['line']) {
				continue;
			}

			$error = 'Multiline method arguments must be a single one per line';
			$fix = $phpcsFile->addFixableError($error, $i, 'ContentAfterOpen');

			if ($fix === true) {
				$indentation = $this->getIndentationCharacter($this->getIndentationWhitespace($phpcsFile, $i));
				$indentation = str_repeat($indentation, $this->getIndentationColumn($phpcsFile, $i));

				$phpcsFile->fixer->beginChangeset();

				if ($i - $possibleCommaIndex > 1) {
					$phpcsFile->fixer->replaceToken($i - 1, '');
				}
				$phpcsFile->fixer->addNewline($i - 1);
				$phpcsFile->fixer->addContentBefore($i, $indentation);

				$phpcsFile->fixer->endChangeset();
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_FUNCTION];
	}

}
