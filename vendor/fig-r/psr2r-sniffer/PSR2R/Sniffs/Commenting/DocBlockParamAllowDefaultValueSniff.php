<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;
use PSR2R\Tools\Traits\SignatureTrait;

/**
 * Makes sure doc block param types allow `|null`, `|array` etc, when those are used
 * as default values in the method signature.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamAllowDefaultValueSniff extends AbstractSniff {

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

		$methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
		if (!$methodSignature) {
			return;
		}

		$docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

		$paramCount = 0;
		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if ($tokens[$i]['content'] !== '@param') {
				continue;
			}

			if (empty($methodSignature[$paramCount])) {
				continue;
			}
			$methodSignatureValue = $methodSignature[$paramCount];
			$paramCount++;

			$classNameIndex = $i + 2;

			if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
				$phpCsFile->addError('Missing type in param doc block', $i, 'CommentAllowDefault');
				continue;
			}

			$content = $tokens[$classNameIndex]['content'];

			$appendix = '';
			$spaceIndex = strpos($content, ' ');
			if ($spaceIndex) {
				$appendix = substr($content, $spaceIndex);
				$content = substr($content, 0, $spaceIndex);
			}
			if (empty($content)) {
				continue;
			}

			if (empty($methodSignatureValue['typehint']) && empty($methodSignatureValue['default'])) {
				continue;
			}

			$pieces = explode('|', $content);
			// We skip for mixed
			if (in_array('mixed', $pieces, true)) {
				continue;
			}

			if ($methodSignatureValue['typehintIndex']) {
				$typeIndex = $methodSignatureValue['typehintIndex'];
				$type = $tokens[$typeIndex]['content'];
				if (!in_array($type, $pieces, false) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
					$pieces[] = $type;
					$error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
					$fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Typehint');
					if ($fix) {
						$content = implode('|', $pieces);
						$phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
					}
				}
			}
			if ($methodSignatureValue['default']) {
				$type = $methodSignatureValue['default'];

				if (!in_array($type, $pieces, false) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
					$pieces[] = $type;
					$error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
					$fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Default');
					if ($fix) {
						$content = implode('|', $pieces);
						$phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
					}
				}
			}
		}
	}

}
