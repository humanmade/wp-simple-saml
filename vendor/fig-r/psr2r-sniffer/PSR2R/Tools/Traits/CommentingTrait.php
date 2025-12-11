<?php
namespace PSR2R\Tools\Traits;

use PHP_CodeSniffer\Files\File;

/**
 * Common functionality around commenting.
 */
trait CommentingTrait {

	/**
	 * Looks for either `@inheritdoc` or `{@inheritdoc}`.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $docBlockStartIndex
	 * @param int $docBlockEndIndex
	 *
	 * @return bool
	 */
	protected function hasInheritDoc(File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex) {
		$tokens = $phpCsFile->getTokens();

		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; ++$i) {
			if (empty($tokens[$i]['content'])) {
				continue;
			}
			$content = strtolower($tokens[$i]['content']);
			if (strpos($content, '@inheritdoc') === false) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * Allow \Foo\Bar[] to pass as array.
	 *
	 * @param array $docBlockTypes
	 * @return bool
	 */
	protected function containsTypeArray($docBlockTypes) {
		foreach ($docBlockTypes as $docBlockType) {
			if (strpos($docBlockType, '[]') !== false) {
				return true;
			}
		}

		return false;
	}

}
