<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Doc blocks for class attributes should not have the variable name duplicated.
 * Type suffices: `@var <type>`.
 *
 * @author Graham Campbell <graham@mineuk.com>
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockVarWithoutNameSniff extends AbstractSniff {

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_DOC_COMMENT_OPEN_TAG];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$docBlockStartIndex = $stackPtr;
		$docBlockEndIndex = $tokens[$stackPtr]['comment_closer'];

		$indentationLevel = $this->getIndentationLevel($phpcsFile, $stackPtr);

		// Skip for inline comments or other non class constructs.
		if ($indentationLevel !== 1) {
			return;
		}

		for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
			if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
				continue;
			}
			if ($tokens[$i]['content'] !== '@var') {
				continue;
			}

			//$nextIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $i + 1, $docBlockEndIndex, true);
			$nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $i + 1, $docBlockEndIndex);
			if (!$nextIndex || strpos($tokens[$nextIndex]['content'], ' ') === false) {
				continue;
			}

			$content = $tokens[$nextIndex]['content'];
			preg_match_all('/ \$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $content, $matches);

			if (isset($matches[0][0]) && trim($matches[0][0]) !== '$this') {
				$fix = $phpcsFile->addFixableError('@var annotations should not contain the variable name.', $i,
					'RemoveVarName');
				if ($fix) {
					$phpcsFile->fixer->replaceToken($nextIndex, str_replace($matches[0][0], '', $content));
				}
			}
		}
	}

}
