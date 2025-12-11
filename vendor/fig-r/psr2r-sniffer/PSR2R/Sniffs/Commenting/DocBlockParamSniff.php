<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;
use PSR2R\Tools\Traits\SignatureTrait;

/**
 * Makes sure doc block param types match the variable name of the method signature.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamSniff extends AbstractSniff {

	use CommentingTrait;
	use SignatureTrait;

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_FUNCTION,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpCsFile, $stackPointer) {
		$tokens = $phpCsFile->getTokens();

		$docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

		if (!$docBlockEndIndex) {
			return;
		}

		$docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

		if ($this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex)) {
			return;
		}

		$methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
		if (!$methodSignature) {
			return;
		}

		$docBlockParams = [];
		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if ($tokens[$i]['content'] !== '@param') {
				continue;
			}

			$classNameIndex = $i + 2;

			if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
				$phpCsFile->addError('Missing type in param doc block', $i, 'MissingParamType');
				continue;
			}

			$content = $tokens[$classNameIndex]['content'];

			$appendix = '';
			$spacePos = strpos($content, ' ');
			if ($spacePos) {
				$appendix = substr($content, $spacePos);
				$content = substr($content, 0, $spacePos);
			}

			/** @noinspection NotOptimalRegularExpressionsInspection */
			preg_match('/\$[^\s]+/', $appendix, $matches);
			$variable = $matches ? $matches[0] : '';

			$docBlockParams[] = [
				'index' => $classNameIndex,
				'type' => $content,
				'variable' => $variable,
				'appendix' => $appendix,
			];
		}

		if (count($docBlockParams) !== count($methodSignature)) {
			$phpCsFile->addError('Doc Block params do not match method signature', $stackPointer, 'ParamTypeMismatch');
			return;
		}

		foreach ($docBlockParams as $docBlockParam) {
			$methodParam = array_shift($methodSignature);
			$variableName = $tokens[$methodParam['variable']]['content'];

			if ($docBlockParam['variable'] === $variableName) {
				continue;
			}
			// We let other sniffers take care of missing type for now
			if (strpos($docBlockParam['type'], '$') !== false) {
				continue;
			}

			$error = 'Doc Block param variable `' . $docBlockParam['variable'] . '` should be `' . $variableName . '`';
			// For now just report (buggy yet)
			$phpCsFile->addError($error, $docBlockParam['index'], 'VariableWrong');

			/*
			$fix = $phpCsFile->addFixableError($error, $docBlockParam['index'], 'VariableWrong');
			if ($fix) {
				if ($docBlockParam['variable']) {
					$appendix = str_replace($docBlockParam['variable'], '', $docBlockParam['appendix']);
					$appendix = preg_replace('/' . preg_quote($docBlockParam['variable'], '/') . '\b/', $variableName, $appendix);
				} else {
					$appendix = ' ' . $variableName . $docBlockParam['appendix'];
				}
				$content = $docBlockParam['type'] . $appendix;
				$phpCsFile->fixer->replaceToken($docBlockParam['index'], $content);
			}
			*/
		}
	}

	/**
	 * //TODO: Replace with SignatureTrait
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $stackPtr
	 * @return array
	 */
	private function getMethodSignature(File $phpCsFile, $stackPtr) {
		$tokens = $phpCsFile->getTokens();

		$startIndex = $phpCsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
		$endIndex = $tokens[$startIndex]['parenthesis_closer'];

		$arguments = [];
		$i = $startIndex;
		while ($nextVariableIndex = $phpCsFile->findNext(T_VARIABLE, $i + 1, $endIndex)) {
			$typehint = $default = null;
			$possibleTypeHint =
				$phpCsFile->findPrevious([T_ARRAY_HINT, T_CALLABLE], $nextVariableIndex - 1, $nextVariableIndex - 3);
			if ($possibleTypeHint) {
				$typehint = $possibleTypeHint;
			}
			if ($possibleTypeHint) {
				$typehint = $possibleTypeHint;
			}

			$possibleEqualIndex = $phpCsFile->findNext([T_EQUAL], $nextVariableIndex + 1, $nextVariableIndex + 2);
			if ($possibleEqualIndex) {
				$possibleDefaultValue =
					$phpCsFile->findNext([T_STRING, T_TRUE, T_FALSE, T_NULL, T_ARRAY], $possibleEqualIndex + 1,
						$possibleEqualIndex + 2);
				if ($possibleDefaultValue) {
					$default = $possibleDefaultValue;
				}
			}

			$arguments[] = [
				'variable' => $nextVariableIndex,
				'typehint' => $typehint,
				'default' => $default,
			];

			$i = $nextVariableIndex;
		}

		return $arguments;
	}

}
