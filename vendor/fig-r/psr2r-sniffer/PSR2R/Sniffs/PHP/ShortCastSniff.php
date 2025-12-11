<?php

namespace PSR2R\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Use short form of boolean and integer casts.
 *
 * @author  Mark Scherer
 * @license MIT
 */
class ShortCastSniff implements Sniff {

	/**
	 * @var array
	 */
	public static $matching = [
		'(boolean)' => '(bool)',
		'(integer)' => '(int)',
	];

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['content'] === '!') {
			$prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
			if ($tokens[$prevIndex]['content'] !== '!') {
				return;
			}

			$fix = $phpcsFile->addFixableError('`!!` cast not allowed, use `(bool)`', $stackPtr, 'DoubleNotCast');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($prevIndex, '');
				$phpcsFile->fixer->replaceToken($stackPtr, '(bool)');
			}

			return;
		}

		$content = $tokens[$stackPtr]['content'];
		$key = strtolower($content);

		if (!isset(static::$matching[$key])) {
			return;
		}

		$fix = $phpcsFile->addFixableError($content . ' found, expected ' . static::$matching[$key], $stackPtr,
			'ShortCast');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr, static::$matching[$key]);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_BOOL_CAST, T_INT_CAST, T_BOOLEAN_NOT];
	}

}
