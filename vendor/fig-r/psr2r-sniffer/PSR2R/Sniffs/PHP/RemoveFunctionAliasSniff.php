<?php

namespace PSR2R\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Eliminate alias usage of basic PHP functions.
 *
 * @author Mark Scherer
 * @license MIT
 */
class RemoveFunctionAliasSniff implements Sniff {

	/**
	 * @see http://php.net/manual/en/aliases.php
	 *
	 * @var array
	 */
	public static $matching = [
		'is_integer' => 'is_int',
		'is_long' => 'is_int',
		'is_real' => 'is_float',
		'is_double' => 'is_float',
		'is_writeable' => 'is_writable',
		'join' => 'implode',
		'key_exists' => 'array_key_exists', // Deprecated function
		'sizeof' => 'count',
		'strchr' => 'strstr',
		'ini_alter' => 'ini_set',
		'fputs' => 'fwrite',
		'die' => 'exit',
		'chop' => 'rtrim',
	];

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

		$tokenContent = $tokens[$stackPtr]['content'];
		$key = strtolower($tokenContent);
		if (!isset(static::$matching[$key])) {
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

		$error = 'Function name ' . $tokenContent . '() found, should be ' . static::$matching[$key] . '().';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'FunctionName');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($stackPtr, static::$matching[$key]);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_STRING, T_EXIT];
	}

}
