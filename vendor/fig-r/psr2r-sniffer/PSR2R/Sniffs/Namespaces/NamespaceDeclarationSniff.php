<?php
/**
 * PSR2_Sniffs_Namespaces_NamespaceDeclarationSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace PSR2R\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * PSR2_Sniffs_Namespaces_NamespaceDeclarationSniff.
 *
 * Ensures namespaces are declared correctly.
 *
 * @author Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version Release: @package_version@
 * @link http://pear.php.net/package/PHP_CodeSniffer
 */
class NamespaceDeclarationSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		for ($i = ($stackPtr + 1); $i < ($phpcsFile->numTokens - 1); $i++) {
			if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
				continue;
			}

			break;
		}

		// The $i var now points to the first token on the line after the
		// namespace declaration, which must be a blank line.
		$next = $phpcsFile->findNext(T_WHITESPACE, $i, $phpcsFile->numTokens, true);
		if ($next === false) {
			return;
		}

		$diff = ($tokens[$next]['line'] - $tokens[$i]['line']);
		if ($diff === 1) {
			return;
		}

		if ($diff < 0) {
			$diff = 0;
		}

		$error = 'There must be one blank line after the namespace declaration';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineAfter');

		if (!$fix) {
			return;
		}

		if ($diff === 0) {
			$phpcsFile->fixer->addNewlineBefore($i);
		} else {
			$phpcsFile->fixer->beginChangeset();
			for ($x = $i; $x < $next; $x++) {
				if ($tokens[$x]['line'] === $tokens[$next]['line']) {
					break;
				}

				$phpcsFile->fixer->replaceToken($x, '');
			}

			$phpcsFile->fixer->addNewline($i);
			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_NAMESPACE];
	}

}
