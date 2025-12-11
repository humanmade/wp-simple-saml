<?php
namespace PSR2R\Tools\Traits;

use PHP_CodeSniffer\Files\File;

/**
 * Common functionality around namespaces.
 */
trait NamespaceTrait {

	/**
	 * Checks if this use statement is part of the namespace block.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 *
	 * @return bool
	 */
	protected function shouldIgnoreUse(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Ignore USE keywords inside closures.
		$next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
		if ($tokens[$next]['code'] === T_OPEN_PARENTHESIS) {
			return true;
		}

		// Ignore USE keywords for traits.
		if ($phpcsFile->hasCondition($stackPtr, [T_CLASS, T_TRAIT]) === true) {
			return true;
		}

		return false;
	}

}
