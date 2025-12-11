<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;

/**
 * Makes sure doc block param type array is only used once.
 * So `array|\Foo\Bar[]` would prefer just `\Foo\Bar[]`.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamArraySniff extends AbstractSniff {

	use CommentingTrait;

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

		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if (!in_array($tokens[$i]['content'], ['@param', '@return'], true)) {
				continue;
			}

			$classNameIndex = $i + 2;

			if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
				continue;
			}

			$content = $tokens[$classNameIndex]['content'];

			$appendix = '';
			$spacePos = strpos($content, ' ');
			if ($spacePos) {
				$appendix = substr($content, $spacePos);
				$content = substr($content, 0, $spacePos);
			}

			$pieces = explode('|', $content);

			if (!in_array('array', $pieces, true)) {
				continue;
			}
			if (!$this->containsTypeArray($pieces)) {
				continue;
			}

			$error = 'Doc Block param type `array` not needed on top of  `...[]`';
			// For now just report
			$fix = $phpCsFile->addFixableError($error, $classNameIndex, 'TypeDuplicated');
			if (!$fix) {
				continue;
			}

			$keys = array_keys($pieces, 'array');
			foreach ($keys as $key) {
				unset($pieces[$key]);
			}
			$content = implode('|', $pieces);

			$phpCsFile->fixer->beginChangeset();
			$phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
			$phpCsFile->fixer->endChangeset();
		}
	}

}
