<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Warn about `@param null $var ` etc as a null/true/false would be a NO-OP.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamNoOpSniff extends AbstractSniff {

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

		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if ($tokens[$i]['content'] !== '@param') {
				continue;
			}

			$classNameIndex = $i + 2;

			if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
				continue;
			}

			$content = $tokens[$classNameIndex]['content'];

			$spaceIndex = strpos($content, ' ');
			if ($spaceIndex) {
				$content = substr($content, 0, $spaceIndex);
			}
			if (empty($content) || strpos($content, '|') !== false) {
				continue;
			}

			if (!in_array($content, ['null', 'false', 'true'], true)) {
				continue;
			}

			$error = 'Possible doc block error: `' . $content .
				'` as only param type does not seem right. Makes this a no-op.';
			$phpCsFile->addWarning($error, $i, 'ParamNoOp');
		}
	}

}
