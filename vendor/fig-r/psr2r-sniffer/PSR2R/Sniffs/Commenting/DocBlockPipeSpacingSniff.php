<?php

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * No spaces around pipes in doc block hints.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockPipeSpacingSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_DOC_COMMENT_STRING,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$content = $tokens[$stackPtr]['content'];
		$isInlineDocBlock = substr($content, -1, 1) === ' ';

		// Fix inline doc blocks
		$appendix = '';
		$varIndex = strpos($content, '$');
		if ($varIndex) {
			$appendix = substr($content, $varIndex);
			$content = substr($content, 0, $varIndex);
		}

		if (strpos($content, '|') === false) {
			return;
		}

		$pieces = explode('|', $content);
		$newContent = [];
		foreach ($pieces as $piece) {
			$newContent[] = trim($piece);
		}
		$newContent = implode('|', $newContent);

		if (trim($newContent) !== trim($content)) {
			$fix = $phpcsFile->addFixableError('There should be no space around pipes in doc blocks.', $stackPtr,
				'PipeSpacing');
			if ($fix) {
				if ($appendix) {
					$appendix = ' ' . $appendix;
				}
				if ($isInlineDocBlock) {
					$appendix .= ' ';
				}

				$phpcsFile->fixer->replaceToken($stackPtr, $newContent . $appendix);
			}
		}
	}

}
