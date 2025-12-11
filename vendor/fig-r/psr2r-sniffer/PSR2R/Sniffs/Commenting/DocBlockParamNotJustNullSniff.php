<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;
use PSR2R\Tools\Traits\SignatureTrait;

/**
 * Makes sure doc block param types are never just `null`, but always another type and optionally nullable on top.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamNotJustNullSniff extends AbstractSniff {

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
			$paramCount++;

			$classNameIndex = $i + 2;

			if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
				// Let another sniffer take care of the missing type
				continue;
			}

			$content = $tokens[$classNameIndex]['content'];

			$spaceIndex = strpos($content, ' ');
			if ($spaceIndex) {
				$content = substr($content, 0, $spaceIndex);
			}
			if (empty($content) || $content !== 'null') {
				continue;
			}

			$phpCsFile->addError('"null" as only param type does not make sense', $classNameIndex, 'NotJustNull');
		}
	}

}
