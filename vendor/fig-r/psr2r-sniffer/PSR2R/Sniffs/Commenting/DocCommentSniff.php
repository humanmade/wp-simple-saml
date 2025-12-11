<?php
/**
 * Ensures doc blocks follow basic formatting.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace PSR2R\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

/**
 * Ensures doc blocks follow basic formatting.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class DocCommentSniff extends AbstractSniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = [
		'PHP',
		'JS',
	];

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

		// Skip for PhpStorm markers which must remain as inline comments
		if ($this->isPhpStormMarker($phpcsFile, $stackPtr)) {
			return;
		}

		$commentStart = $stackPtr;
		$commentEnd = $tokens[$stackPtr]['comment_closer'];

		$indentationLevel = $this->getIndentationLevel($phpcsFile, $stackPtr);

		// Skip for inline comments
		if ($indentationLevel > 1) {
			return;
		}

		$empty = [
			T_DOC_COMMENT_WHITESPACE,
			T_DOC_COMMENT_STAR,
		];

		$short = $phpcsFile->findNext($empty, $stackPtr + 1, $commentEnd, true);
		if ($short === false) {
			return;
		}

		// The first line of the comment should just be the /** code.
		if ($tokens[$short]['line'] === $tokens[$stackPtr]['line']) {
			$error = 'The open comment tag must be the only content on the line';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'ContentAfterOpen');
			if ($fix === true) {
				$indentation = $tokens[$commentStart]['column'] - 1;
				$indentation = str_repeat("\t", $indentation);

				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContentBefore($short, $indentation . ' * ');
				$phpcsFile->fixer->addNewlineBefore($short);
				$phpcsFile->fixer->endChangeset();
			}
		}

		// The last line of the comment should just be the */ code.
		$prev = $phpcsFile->findPrevious($empty, $commentEnd - 1, $stackPtr, true);
		if ($tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
			$error = 'The close comment tag must be the only content on the line';
			$fix = $phpcsFile->addFixableError($error, $commentEnd, 'ContentBeforeClose');
			if ($fix === true) {
				$indentation = $tokens[$commentStart]['column'] - 1;
				$indentation = str_repeat("\t", $indentation);

				$phpcsFile->fixer->beginChangeset();

				$phpcsFile->fixer->replaceToken($commentEnd, $indentation . ' ' . $tokens[$commentEnd]['content']);
				$phpcsFile->fixer->addNewlineBefore($commentEnd);

				$phpcsFile->fixer->endChangeset();
			}
		}

		// Check for additional blank lines at the end of the comment.
		if ($tokens[$prev]['line'] < ($tokens[$commentEnd]['line'] - 1)) {
			$error = 'Additional blank lines found at end of doc comment';
			$fix = $phpcsFile->addFixableError($error, $commentEnd, 'SpacingAfter');
			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				for ($i = ($prev + 1); $i < $commentEnd; $i++) {
					if ($tokens[$i + 1]['line'] === $tokens[$commentEnd]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}

		// No extra newline before short description.
		if ($tokens[$short]['line'] !== ($tokens[$stackPtr]['line'] + 1)) {
			$error = 'Doc comment short description must be on the first line';
			$fix = $phpcsFile->addFixableError($error, $short, 'SpacingBeforeShort');
			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				for ($i = $stackPtr; $i < $short; $i++) {
					if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
						continue;
					}
					if ($tokens[$i]['line'] === $tokens[$short]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}

		// Account for the fact that a short description might cover
		// multiple lines.
		$shortContent = $tokens[$short]['content'];
		$shortEnd = $short;
		for ($i = ($short + 1); $i < $commentEnd; $i++) {
			if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
				if ($tokens[$i]['line'] === ($tokens[$shortEnd]['line'] + 1)) {
					$shortContent .= $tokens[$i]['content'];
					$shortEnd = $i;
				} else {
					break;
				}
			}
		}

		if (empty($tokens[$commentStart]['comment_tags']) === true) {
			// No tags in the comment.
			return;
		}

		$firstTag = $tokens[$commentStart]['comment_tags'][0];
		if ($tokens[$firstTag]['line'] === $tokens[$commentStart]['line'] + 1) {
			return;
		}

		$prev = $phpcsFile->findPrevious($empty, $firstTag - 1, $stackPtr, true);
		if ($tokens[$firstTag]['line'] !== ($tokens[$prev]['line'] + 2)) {
			$error = 'There must be exactly one blank line before the tags in a doc comment';
			$fix = $phpcsFile->addFixableError($error, $firstTag, 'SpacingBeforeTags');
			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				for ($i = ($prev + 1); $i < $firstTag; $i++) {
					if ($tokens[$i]['line'] === $tokens[$firstTag]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$indent = str_repeat("\t", $tokens[$stackPtr]['column'] - 1) . ' ';
				$phpcsFile->fixer->addContent($prev, $phpcsFile->eolChar . $indent . '*' . $phpcsFile->eolChar);
				$phpcsFile->fixer->endChangeset();
			}
		}
	}

}
