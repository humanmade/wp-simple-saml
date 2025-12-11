<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

class ClassCreateInstanceSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nextParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr, null, false, null, true);

		// If there is a parenthesis owner then this is not a constructor call
		if ($nextParenthesisIndex && !isset($tokens[$nextParenthesisIndex]['parenthesis_owner'])) {
			return;
		}

		$error = 'Calling class constructors must always include parentheses';
		$constructorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);

		// We can only invoke the fixer if we know this is a static constructor function call.
		if ($tokens[$constructorIndex]['code'] !== T_STRING && $tokens[$constructorIndex]['code'] !== T_NS_SEPARATOR) {
			$phpcsFile->addError($error, $stackPtr, 'ParenthesisMissing');
			return;
		}

		// Scan to the end of possible string\namespace parts.
		$nextConstructorPart = $constructorIndex;
		while (true) {
			$nextConstructorPart = $phpcsFile->findNext(
				Tokens::$emptyTokens,
				$nextConstructorPart + 1,
				null,
				true,
				null,
				true
			);
			if ($nextConstructorPart === false
				|| ($tokens[$nextConstructorPart]['code'] !== T_STRING &&
					$tokens[$nextConstructorPart]['code'] !== T_NS_SEPARATOR)
			) {
				break;
			}

			$constructorIndex = $nextConstructorPart;
		}

		$fix = $phpcsFile->addFixableError($error, $constructorIndex, 'ParenthesisMissing');
		if ($fix) {
			$phpcsFile->fixer->addContent($constructorIndex, '()');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_NEW];
	}

}
