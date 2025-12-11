<?php

namespace PSR2R\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Verifies that Yoda conditions (reversed expression order) are not used for comparison.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ConditionalExpressionOrderSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpCsFile, $stackPointer) {
		$tokens = $phpCsFile->getTokens();

		$prevIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, $stackPointer - 1, null, true);
		if (!$this->isGivenKind([T_CLOSE_SHORT_ARRAY, T_TRUE, T_FALSE, T_NULL, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING],
			$tokens[$prevIndex])
		) {
			return;
		}

		$leftIndexEnd = $prevIndex;

		if ($this->isGivenKind(T_CLOSE_SHORT_ARRAY, $tokens[$prevIndex])) {
			$prevIndex = $tokens[$prevIndex]['bracket_opener'];
		}

		$leftIndexStart = $prevIndex;

		$prevIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, $prevIndex - 1, null, true);
		if (!$prevIndex) {
			return;
		}
		if ($this->isGivenKind(Tokens::$arithmeticTokens, $tokens[$prevIndex])) {
			return;
		}
		if ($this->isGivenKind([T_STRING_CONCAT], $tokens[$prevIndex])) {
			return;
		}

		$error = 'Usage of Yoda conditions is not allowed. Switch the expression order.';
		$prevContent = $tokens[$prevIndex]['content'];

		if ($prevContent !== '('
			&& $prevContent !== ','
			&& !$this->isGivenKind(Tokens::$assignmentTokens, $tokens[$prevIndex])
			&& !$this->isGivenKind(Tokens::$booleanOperators, $tokens[$prevIndex])
		) {
			// Not fixable
			$phpCsFile->addError($error, $stackPointer, 'YodaConditions');
			return;
		}

		$rightIndexStart = $phpCsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);

		if ($this->isGivenKind(T_OPEN_PARENTHESIS, $tokens[$prevIndex])) {
			$rightIndexEnd =
				$phpCsFile->findPrevious(Tokens::$emptyTokens, $tokens[$prevIndex]['parenthesis_closer'] - 1, null,
					true);
		} else {
			$previousParenthesisOpenerIndex = $phpCsFile->findPrevious(T_OPEN_PARENTHESIS, $prevIndex - 1);
			$limit = null;
			if ($previousParenthesisOpenerIndex &&
				$tokens[$previousParenthesisOpenerIndex]['parenthesis_closer'] > $rightIndexStart
			) {
				$limit = $tokens[$previousParenthesisOpenerIndex]['parenthesis_closer'];
			}

			$rightIndexEnd = $this->detectRightEnd($phpCsFile, $rightIndexStart, $limit);
		}

		$fix = $phpCsFile->addFixableError($error, $stackPointer, 'RightEnd');
		if ($fix) {
			$this->applyFix($phpCsFile, $stackPointer, $leftIndexStart, $leftIndexEnd, $rightIndexStart,
				$rightIndexEnd);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return Tokens::$comparisonTokens;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $index
	 * @param int $limit
	 *
	 * @return int|null
	 */
	protected function detectRightEnd(File $phpCsFile, $index, $limit = 0) {
		$tokens = $phpCsFile->getTokens();

		$rightEndIndex = $index;
		$nextIndex = $index;
		if ($this->isGivenKind(T_OPEN_PARENTHESIS, $tokens[$index])) {
			return $tokens[$index]['parenthesis_closer'];
		}

		while (true) {
			$nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
			if (!$nextIndex) {
				return $rightEndIndex;
			}

			if ($nextIndex >= $limit) {
				return $rightEndIndex;
			}

			if ($this->isGivenKind([T_SEMICOLON, T_COMMA], $tokens[$nextIndex])) {
				return $rightEndIndex;
			}

			if ($this->isGivenKind(T_OPEN_PARENTHESIS, $tokens[$nextIndex])) {
				$nextIndex = $tokens[$nextIndex]['parenthesis_closer'];
				$rightEndIndex = $nextIndex;
				continue;
			}

			if ($this->isGivenKind(T_CLOSE_PARENTHESIS, $tokens[$nextIndex])
			) {
				return $rightEndIndex;
			}

			$rightEndIndex = $nextIndex;
		}

		return $rightEndIndex;
	}

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $index
	 * @param int $leftIndexStart
	 * @param int $leftIndexEnd
	 * @param int $rightIndexStart
	 * @param int $rightIndexEnd
	 *
	 * @return void
	 */
	protected function applyFix(File $phpCsFile,
		$index,
		$leftIndexStart,
		$leftIndexEnd,
		$rightIndexStart,
		$rightIndexEnd) {
		$tokens = $phpCsFile->getTokens();

		$token = $tokens[$index];
		// Check if we need to inverse comparison operator
		$comparisonValue = $this->getComparisonValue($token);

		$phpCsFile->fixer->beginChangeset();

		$leftValue = '';
		for ($i = $leftIndexStart; $i <= $leftIndexEnd; ++$i) {
			$leftValue .= $tokens[$i]['content'];
			$phpCsFile->fixer->replaceToken($i, '');
		}
		$rightValue = '';
		for ($i = $rightIndexStart; $i <= $rightIndexEnd; ++$i) {
			$rightValue .= $tokens[$i]['content'];
			$phpCsFile->fixer->replaceToken($i, '');
		}

		$phpCsFile->fixer->replaceToken($index, $comparisonValue);
		$phpCsFile->fixer->replaceToken($leftIndexEnd, $rightValue);
		$phpCsFile->fixer->replaceToken($rightIndexStart, $leftValue);

		$phpCsFile->fixer->endChangeset();
	}

	/**
	 * @param array $token
	 *
	 * @return string
	 */
	protected function getComparisonValue(array $token) {
		$comparisonIndexValue = $token['content'];
		$operatorsToMap = [T_GREATER_THAN, T_LESS_THAN, T_IS_GREATER_OR_EQUAL, T_IS_SMALLER_OR_EQUAL];
		if (in_array($token['code'], $operatorsToMap, true)) {
			$mapping = [
				T_GREATER_THAN => '<',
				T_LESS_THAN => '>',
				T_IS_GREATER_OR_EQUAL => '<=',
				T_IS_SMALLER_OR_EQUAL => '>=',
			];
			$comparisonIndexValue = $mapping[$token['code']];
		}

		return $comparisonIndexValue;
	}

}
