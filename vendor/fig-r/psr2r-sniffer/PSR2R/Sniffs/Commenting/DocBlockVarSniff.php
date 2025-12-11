<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;

/**
 * Ensures Doc Blocks for variables exist and are correct.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockVarSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_VARIABLE,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpCsFile, $stackPointer) {
		$tokens = $phpCsFile->getTokens();

		$previousIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, $stackPointer - 1, null, true);
		if (!$this->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE], $tokens[$previousIndex])) {
			return;
		}

		$docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

		if (!$docBlockEndIndex) {
			$phpCsFile->addError('Doc Block for variable missing', $stackPointer, 'DocVarMissing');
			return;
		}

		$docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

		$defaultValueType = $this->findDefaultValueType($phpCsFile, $stackPointer);

		$varIndex = null;
		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if ($tokens[$i]['content'] !== '@var') {
				continue;
			}

			$varIndex = $i;
		}

		if (!$varIndex) {
			$this->handleMissingVar($phpCsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType);
			return;
		}

		$classNameIndex = $varIndex + 2;

		if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
			$this->handleMissingVarType($phpCsFile, $varIndex, $defaultValueType);
			return;
		}

		$content = $tokens[$classNameIndex]['content'];

		$appendix = '';
		$spaceIndex = strpos($content, ' ');
		if ($spaceIndex) {
			$appendix = substr($content, $spaceIndex);
			$content = substr($content, 0, $spaceIndex);
		}

		if (empty($content)) {
			$error = 'Doc Block type for annotation @var for variable missing';
			if ($defaultValueType) {
				$error .= ', type `' . $defaultValueType . '` detected';
			}
			$phpCsFile->addError($error, $stackPointer, 'VarTypeEmpty');
			return;
		}

		if ($defaultValueType === null) {
			return;
		}

		$parts = explode('|', $content);
		if (in_array($defaultValueType, $parts, true)) {
			return;
		}
		if ($defaultValueType === 'bool' && (in_array('true', $parts, true) || in_array('false', $parts, true))) {
			return;
		}

		if ($defaultValueType === 'array' && strpos($content, '[]') !== false) {
			return;
		}

		if ($defaultValueType === 'null' || count($parts) > 1) {
			$fix =
				$phpCsFile->addFixableError('Doc Block type for annotation @var incorrect, type `' . $defaultValueType .
					'` missing', $stackPointer, 'VarTypeIncorrect');
			if ($fix) {
				$phpCsFile->fixer->replaceToken($classNameIndex,
					implode('|', $parts) . '|' . $defaultValueType . $appendix);
			}
			return;
		}

		$fix = $phpCsFile->addFixableError('Doc Block type `' . $content . '` for annotation @var incorrect, type `' .
			$defaultValueType . '` expected', $stackPointer, 'VarTypeIncorrect');
		if ($fix) {
			$phpCsFile->fixer->replaceToken($classNameIndex, $defaultValueType . $appendix);
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $stackPointer
	 * @return string|null
	 */
	protected function findDefaultValueType(File $phpCsFile, $stackPointer) {
		$tokens = $phpCsFile->getTokens();

		$nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);
		if (!$nextIndex || !$this->isGivenKind(T_EQUAL, $tokens[$nextIndex])) {
			return null;
		}

		$nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
		if (!$nextIndex) {
			return null;
		}

		return $this->detectType($tokens[$nextIndex]);
	}

	/**
	 * @param array $token
	 * @return null|string
	 */
	protected function detectType(array $token) {
		if ($this->isGivenKind(T_OPEN_SHORT_ARRAY, $token)) {
			return 'array';
		}

		if ($this->isGivenKind(T_LNUMBER, $token)) {
			return 'int';
		}

		if ($this->isGivenKind(T_CONSTANT_ENCAPSED_STRING, $token)) {
			return 'string';
		}

		if ($this->isGivenKind([T_FALSE, T_TRUE], $token)) {
			return 'bool';
		}

		if ($this->isGivenKind(T_NULL, $token)) {
			return 'null';
		}

		return null;
	}

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $docBlockEndIndex
	 * @param int $docBlockStartIndex
	 * @param string|null $defaultValueType
	 * @return void
	 */
	protected function handleMissingVar(File $phpCsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType) {
		$error = 'Doc Block annotation @var for variable missing';
		if ($defaultValueType === null) {
			$phpCsFile->addError($error, $docBlockEndIndex, 'VarMissing');
			return;
		}

		$error .= ', type `' . $defaultValueType . '` detected';
		$fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'TypeFixable');
		if (!$fix) {
			return;
		}

		$index = $phpCsFile->findPrevious(Tokens::$emptyTokens, $docBlockEndIndex - 1, $docBlockStartIndex, true);
		if (!$index) {
			$index = $docBlockStartIndex;
		}

		$phpCsFile->fixer->beginChangeset();
		$phpCsFile->fixer->addNewline($index);
		$phpCsFile->fixer->addContent($index, "\t" . ' * @var ' . $defaultValueType);
		$phpCsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $varIndex
	 * @param string|null $defaultValueType
	 * @return void
	 */
	protected function handleMissingVarType(File $phpCsFile, $varIndex, $defaultValueType) {
		$error = 'Doc Block type for annotation @var for variable missing';
		if ($defaultValueType === null) {
			$phpCsFile->addError($error, $varIndex, 'VarTypeMissing');
			return;
		}

		$error .= ', type `' . $defaultValueType . '` detected';
		$fix = $phpCsFile->addFixableError($error, $varIndex, 'TypeDetected');
		if (!$fix) {
			return;
		}

		$phpCsFile->fixer->addContent($varIndex, ' ' . $defaultValueType);
	}

}
