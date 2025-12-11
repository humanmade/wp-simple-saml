<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;

/**
 * Methods that may not return anything need to be declared as `@return void`.
 * Constructor and destructor may not have this addition, as they cannot return by definition.
 */
class DocBlockReturnVoidSniff extends AbstractSniff {

	use CommentingTrait;

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_FUNCTION];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if ($tokens[$nextIndex]['content'] === '__construct' || $tokens[$nextIndex]['content'] === '__destruct') {
			$this->checkConstructorAndDestructor($phpcsFile, $nextIndex);
			return;
		}

		// Don't mess with closures
		$prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
		if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
			return;
		}

		$docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
		if (!$docBlockEndIndex) {
			return;
		}

		$docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

		$docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

		$hasInheritDoc = $this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

		// If interface we will at least report it
		if (empty($tokens[$stackPtr]['scope_opener']) || empty($tokens[$stackPtr]['scope_closer'])) {
			if (!$docBlockReturnIndex && !$hasInheritDoc) {
				$phpcsFile->addError('Method does not have a return statement in doc block: ' .
					$tokens[$nextIndex]['content'], $nextIndex, 'NoReturnDoc1');
			}
			return;
		}

		// If inheritdoc is present assume the parent contains it
		if ($docBlockReturnIndex || (!$docBlockReturnIndex && $hasInheritDoc)) {
			return;
		}

		// We only look for void methods right now
		$returnType = $this->detectReturnTypeVoid($phpcsFile, $stackPtr);
		if ($returnType === null) {
			$phpcsFile->addError('Method does not have a return statement in doc block: ' .
				$tokens[$nextIndex]['content'], $nextIndex, 'NoReturnDoc2');
			return;
		}

		$fix = $phpcsFile->addFixableError('Method does not have a return void statement in doc block: ' .
			$tokens[$nextIndex]['content'], $nextIndex, 'NoReturnVoid');
		if (!$fix) {
			return;
		}

		$this->addReturnAnnotation($phpcsFile, $docBlockEndIndex, $returnType);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return void
	 */
	protected function checkConstructorAndDestructor(File $phpcsFile, $index) {
		$docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $index);
		if (!$docBlockEndIndex) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

		$docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
		if (!$docBlockReturnIndex) {
			return;
		}

		$fix = $phpcsFile->addFixableError($tokens[$index]['content'] . ' has invalid return statement.',
			$docBlockReturnIndex, 'InvalidReturn');
		if ($fix) {
			$phpcsFile->fixer->replaceToken($docBlockReturnIndex, '');

			$possibleStringToken = $tokens[$docBlockReturnIndex + 2];
			if ($this->isGivenKind(T_DOC_COMMENT_STRING, $possibleStringToken)) {
				$phpcsFile->fixer->replaceToken($docBlockReturnIndex + 1, '');
				$phpcsFile->fixer->replaceToken($docBlockReturnIndex + 2, '');
			}
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $docBlockStartIndex
	 * @param int $docBlockEndIndex
	 *
	 * @return int|null
	 */
	protected function findDocBlockReturn(File $phpcsFile, $docBlockStartIndex, $docBlockEndIndex) {
		$tokens = $phpcsFile->getTokens();

		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if (!$this->isGivenKind(T_DOC_COMMENT_TAG, $tokens[$i])) {
				continue;
			}
			if ($tokens[$i]['content'] !== '@return') {
				continue;
			}

			return $i;
		}

		return null;
	}

	/**
	 * For right now we only try to detect void.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 *
	 * @return string|null
	 */
	protected function detectReturnTypeVoid(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$type = 'void';

		$methodStartIndex = $tokens[$index]['scope_opener'];
		$methodEndIndex = $tokens[$index]['scope_closer'];

		for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
			if ($this->isGivenKind([T_FUNCTION], $tokens[$i])) {
				$endIndex = $tokens[$i]['scope_closer'];
				$i = $endIndex;
				continue;
			}

			if (!$this->isGivenKind([T_RETURN], $tokens[$i])) {
				continue;
			}

			$nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, null, true);
			if (!$this->isGivenKind(T_SEMICOLON, $tokens[$nextIndex])) {
				return null;
			}
		}

		return $type;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $docBlockEndIndex
	 * @param string $returnType
	 *
	 * @return void
	 */
	protected function addReturnAnnotation(File $phpcsFile, $docBlockEndIndex, $returnType = 'void') {
		$indentation = $this->getIndentationWhitespace($phpcsFile, $docBlockEndIndex);

		$lastLineEndIndex = $phpcsFile->findPrevious([T_DOC_COMMENT_WHITESPACE], $docBlockEndIndex - 1, null, true);

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addNewline($lastLineEndIndex);
		$phpcsFile->fixer->addContent($lastLineEndIndex, $indentation . '* @return ' . $returnType);
		$phpcsFile->fixer->endChangeset();
	}

}
