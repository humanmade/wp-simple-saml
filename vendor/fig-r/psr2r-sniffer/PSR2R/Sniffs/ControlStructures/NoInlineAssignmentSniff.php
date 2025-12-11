<?php

namespace PSR2R\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Inline/conditional assignment is not allowed. Extract into an own line above.
 */
class NoInlineAssignmentSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		if ($tokens[$stackPtr]['code'] === T_OBJECT_OPERATOR || $tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
			$this->checkMethodCalls($phpcsFile, $stackPtr);
			return;
		}

		$this->checkConditions($phpcsFile, $stackPtr);
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		// We skip T_FOR, T_WHILE for now as they can have valid inline assignment
		return [T_FOREACH, T_IF, T_SWITCH, T_OBJECT_OPERATOR, T_DOUBLE_COLON];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function checkMethodCalls(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$openingBraceIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1, $stackPtr + 4);
		if (!$openingBraceIndex) {
			return;
		}
		if (empty($tokens[$openingBraceIndex]['parenthesis_closer'])) {
			return;
		}

		$closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

		$hasInlineAssignment = $this->contains($phpcsFile, T_EQUAL, $openingBraceIndex + 1, $closingBraceIndex - 1);
		if (!$hasInlineAssignment) {
			return;
		}

		$phpcsFile->addError('Inline assignment not allowed', $stackPtr, 'InlineNotAllowed');
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function checkConditions($phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$openingBraceIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if (!$openingBraceIndex) {
			return;
		}
		if (empty($tokens[$openingBraceIndex]['parenthesis_closer'])) {
			return;
		}

		$closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

		$hasInlineAssignment = $this->contains($phpcsFile, T_EQUAL, $openingBraceIndex + 1, $closingBraceIndex - 1);
		if (!$hasInlineAssignment) {
			return;
		}

		$phpcsFile->addError('Conditional inline assignment not allowed', $stackPtr, 'ConditionalInlineNotAllowed');
	}

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * //TODO: activate
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $startIndex
	 * @param int $endIndex
	 * @param int $indexEqualSign
	 *
	 * @return bool
	 */
	protected function isFixableInlineAssignment(File $phpcsFile, $startIndex, $endIndex, &$indexEqualSign) {
		$tokens = $phpcsFile->getTokens();

		$hasInlineAssignment = false;
		for ($i = $startIndex; $i < $endIndex; $i++) {
			$currentToken = $tokens[$i];

			// We need to skip for complex assignments
			if ($this->isGivenKind(Tokens::$booleanOperators, $tokens[$currentToken])) {
				$hasInlineAssignment = false;
				break;
			}

			// Negations we also cannot handle just yet
			if ($tokens[$currentToken]['code'] === T_BOOLEAN_NOT) {
				$hasInlineAssignment = false;
				break;
			}

			// Comparison inside is also more complex
			if ($this->isGivenKind(Tokens::$comparisonTokens, $tokens[$currentToken])) {
				$hasInlineAssignment = false;
				break;
			}

			$indexEqualSign = $i;
			$hasInlineAssignment = true;
		}

		return $hasInlineAssignment;
	}

}
