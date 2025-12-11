<?php

namespace PSR2R\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Always use `static::` and "late static binding" over `self::` usage.
 *
 * @author  Mark Scherer
 * @license MIT
 */
class PreferStaticOverSelfSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$index = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		if ($tokens[$index]['code'] !== T_SELF) {
			return;
		}
		if ($tokens[$index]['level'] < 2) {
			return;
		}

		$fix = $phpcsFile->addFixableError('Please use static:: instead of self::', $stackPtr, 'StaticVsSelf');
		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->replaceToken($index, 'static');
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_DOUBLE_COLON];
	}

}
