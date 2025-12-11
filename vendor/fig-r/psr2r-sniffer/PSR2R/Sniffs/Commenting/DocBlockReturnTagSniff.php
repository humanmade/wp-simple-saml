<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that a `@return` tag exists for all functions and methods and that it does not exist
 * for all constructors and destructors.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockReturnTagSniff extends AbstractScopeSniff {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct([T_CLASS], [T_FUNCTION]);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function processTokenOutsideScope(File $phpcsFile, $stackPtr) {
	}

	/**
	 * @inheritDoc
	 */
	protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope) {
		$tokens = $phpcsFile->getTokens();

		// Type of method
		$method = $phpcsFile->findNext(T_STRING, $stackPtr + 1);
		$returnRequired = !in_array($tokens[$method]['content'], ['__construct', '__destruct'], true);

		$find = [
			T_COMMENT,
			T_DOC_COMMENT,
			T_CLASS,
			T_FUNCTION,
			T_OPEN_TAG,
		];
		$find = array_merge($find, Tokens::$commentTokens);

		$commentEnd = $phpcsFile->findPrevious($find, $stackPtr - 1);

		if ($commentEnd === false) {
			return;
		}

		if ($tokens[$commentEnd]['type'] !== 'T_DOC_COMMENT_CLOSE_TAG') {
			// Function doesn't have a comment. Let someone else warn about that.
			return;
		}

		$commentStart = ($phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $commentEnd - 1) + 1);

		$commentWithReturn = null;
		for ($i = $commentEnd; $i >= $commentStart; $i--) {
			$currentComment = $tokens[$i]['content'];

			// '@return' is separate token from return value
			if (strpos($currentComment, '@return') !== false) {
				$commentWithReturn = $i;
				break;
			}
		}
		$inheritDocPtr = $phpcsFile->findNext(T_DOC_COMMENT_TAG, $commentStart, $commentEnd, false, '@inheritDoc');
		$haveInheritDoc = $inheritDocPtr !== false;

		if (!$commentWithReturn && !$returnRequired) {
			return;
		}

		if ($commentWithReturn && $returnRequired && !$haveInheritDoc) {
			return;
		}

		// A class method should have @return unless @inheritDoc
		if (!$commentWithReturn && !$haveInheritDoc) {
			$error = 'Missing @return tag in function comment';
			$phpcsFile->addError($error, $stackPtr, 'Missing');
			return;
		}
		if ($commentWithReturn && $haveInheritDoc) {
			$error = 'Should not have both @inheritDoc and @return in function comment';
			$phpcsFile->addError($error, $stackPtr, 'RedundantReturn');
			return;
		}

		// Constructor/destructor should not have @return
		if ($commentWithReturn) {
			$error = 'Unexpected @return tag in constructor/destructor comment';
			$phpcsFile->addFixableError($error, $commentWithReturn, 'Unexpected');
			if ($phpcsFile->fixer->enabled === true) {
				$phpcsFile->fixer->replaceToken($commentWithReturn, '');
			}
			return;
		}
	}

}
