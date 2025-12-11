<?php

namespace PSR2R\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Use PHP_SAPI constant instead of php_sapi_name() function.
 *
 * @author Mark Scherer
 * @license MIT
 */
class PhpSapiConstantSniff implements Sniff {

	const PHP_SAPI = 'PHP_SAPI';

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

		$tokenContent = $tokens[$stackPtr]['content'];
		if (strtolower($tokenContent) !== 'php_sapi_name') {
			return;
		}

		$previous = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
		if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, false)) {
			return;
		}

		$openingBrace = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
			return;
		}

		$closingBrace = $phpcsFile->findNext(T_WHITESPACE, $openingBrace + 1, null, true);
		if (!$closingBrace || $tokens[$closingBrace]['type'] !== 'T_CLOSE_PARENTHESIS') {
			return;
		}

		$error = $tokenContent . '() found, should be const ' . static::PHP_SAPI . '.';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SapiConstant');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr, static::PHP_SAPI);
			for ($i = $openingBrace; $i <= $closingBrace; ++$i) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_STRING];
	}

}
