<?php

namespace PSR2R\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * is_null() should be replaced by === null check.
 *
 * @author Mark Scherer
 * @license MIT
 */
class NoIsNullSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

		$tokens = $phpcsFile->getTokens();

		$tokenContent = $tokens[$stackPtr]['content'];
		if (strtolower($tokenContent) !== 'is_null') {
			return;
		}

		$previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, false)) {
			return;
		}

		$openingBraceIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if (!$openingBraceIndex || $tokens[$openingBraceIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
			return;
		}

		$closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

		$error = $tokenContent . '() found, should be strict === null check.';

		$possibleCastIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		$negated = false;
		if ($possibleCastIndex && $tokens[$possibleCastIndex]['code'] === T_BOOLEAN_NOT) {
			$negated = true;
		}
		// We dont want to fix double !!
		if ($negated) {
			$anotherPossibleCastIndex =
				$phpcsFile->findPrevious(Tokens::$emptyTokens, $possibleCastIndex - 1, null, true);
			if ($tokens[$anotherPossibleCastIndex]['code'] === T_BOOLEAN_NOT) {
				$phpcsFile->addError($error, $stackPtr, 'CastDoubleNot');
				return;
			}
		}

		// We don't want to fix stuff with bad inline assignment
		if ($this->contains($phpcsFile, 'T_EQUAL', $openingBraceIndex + 1, $closingBraceIndex - 1)) {
			$phpcsFile->addError($error, $stackPtr, 'InlineAssignment');
			return;
		}

		$beginningIndex = $negated ? $possibleCastIndex : $stackPtr;
		$endIndex = $closingBraceIndex;

		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'Inline');
		if ($fix) {
			$needsBrackets = $this->needsBrackets($phpcsFile, $openingBraceIndex, $closingBraceIndex);
			$leadingComparison = $this->hasLeadingComparison($phpcsFile, $beginningIndex);
			$trailingComparison = $this->hasTrailingComparison($phpcsFile, $closingBraceIndex);

			if ($leadingComparison) {
				$possibleBeginningIndex = $this->findUnnecessaryLeadingComparisonStart($phpcsFile, $beginningIndex);
				if ($possibleBeginningIndex !== null) {
					$beginningIndex = $possibleBeginningIndex;
					$leadingComparison = false;
					if ($tokens[$beginningIndex]['code'] === T_FALSE) {
						$negated = !$negated;
					}
				}
			}

			if ($trailingComparison) {
				$possibleEndIndex = $this->findUnnecessaryLeadingComparisonStart($phpcsFile, $endIndex);
				if ($possibleEndIndex !== null) {
					$endIndex = $possibleEndIndex;
					$trailingComparison = false;
					if ($tokens[$endIndex]['code'] === T_FALSE) {
						$negated = !$negated;
					}
				}
			}

			if (!$needsBrackets && ($leadingComparison || $this->leadRequiresBrackets($phpcsFile, $beginningIndex))) {
				$needsBrackets = true;
			}
			if (!$needsBrackets && $trailingComparison) {
				$needsBrackets = true;
			}

			$comparisonString = ' ' . ($negated ? '!' : '=') . '== null';

			$phpcsFile->fixer->beginChangeset();

			if ($beginningIndex !== $stackPtr) {
				for ($i = $beginningIndex; $i < $stackPtr; $i++) {
					$phpcsFile->fixer->replaceToken($i, '');
				}
			}
			if ($endIndex !== $closingBraceIndex) {
				for ($i = $endIndex; $i > $closingBraceIndex; $i--) {
					$phpcsFile->fixer->replaceToken($i, '');
				}
			}

			$phpcsFile->fixer->replaceToken($stackPtr, '');
			if (!$needsBrackets) {
				$phpcsFile->fixer->replaceToken($openingBraceIndex, '');
				$phpcsFile->fixer->replaceToken($closingBraceIndex, $comparisonString);
			} else {
				$phpcsFile->fixer->replaceToken($closingBraceIndex, $comparisonString . ')');
			}

			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_STRING];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return bool
	 */
	protected function hasLeadingComparison(File $phpcsFile, $stackPtr) {
		$previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		return $this->isComparison($phpcsFile, $previous);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return bool
	 */
	protected function isComparison(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$blacklistedCodes = [
			T_IS_NOT_EQUAL, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_IS_GREATER_OR_EQUAL,
			T_IS_SMALLER_OR_EQUAL,
		];
		$blacklistedTypes = [
			'T_LESS_THAN', 'T_GREATER_THAN',
		];
		if (in_array($tokens[$index]['code'], $blacklistedCodes, false)) {
			return true;
		}
		if (in_array($tokens[$index]['type'], $blacklistedTypes, false)) {
			return true;
		}

		return false;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return bool
	 */
	protected function hasTrailingComparison(File $phpcsFile, $stackPtr) {
		$next = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		return $this->isComparison($phpcsFile, $next);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return int|null
	 */
	protected function findUnnecessaryLeadingComparisonStart(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $index - 1, null, true);
		if (!in_array($tokens[$previous]['code'], [T_IS_IDENTICAL, T_IS_NOT_IDENTICAL], false)) {
			return null;
		}

		$previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $previous - 1, null, true);
		if (!in_array($tokens[$previous]['code'], [T_TRUE, T_FALSE], false)) {
			return null;
		}

		return $previous;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return bool
	 */
	protected function leadRequiresBrackets(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $index - 1, null, true);
		if ($this->isCast($previous)) {
			return true;
		}
		if (in_array($tokens[$previous]['code'], Tokens::$arithmeticTokens, false)) {
			return true;
		}

		return false;
	}

	/**
	 * @param int $index
	 * @return bool
	 */
	protected function isCast($index) {
		return in_array($index, Tokens::$castTokens, false);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return int|null
	 */
	protected function findUnnecessaryTrailingComparisonEnd(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$next = $phpcsFile->findNext(Tokens::$emptyTokens, $index + 1, null, true);
		if (!$next || !in_array($tokens[$next]['code'], [T_IS_IDENTICAL, T_IS_NOT_IDENTICAL], false)) {
			return null;
		}

		$prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, $next - 1, null, true);
		if (!$prev || !in_array($tokens[$prev]['code'], [T_TRUE, T_FALSE], false)) {
			return null;
		}

		return $next;
	}

}
