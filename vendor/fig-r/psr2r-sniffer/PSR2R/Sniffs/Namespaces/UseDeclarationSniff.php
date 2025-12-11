<?php
/**
 * PSR2_Sniffs_Namespaces_UseDeclarationSniff.
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
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;
use PSR2R\Tools\Traits\NamespaceTrait;

/**
 * PSR2_Sniffs_Namespaces_UseDeclarationSniff.
 *
 * Ensures USE blocks are declared correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class UseDeclarationSniff extends AbstractSniff {

	use CommentingTrait;
	use NamespaceTrait;

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		if ($this->shouldIgnoreUse($phpcsFile, $stackPtr) === true) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		// One space after the use keyword.
		if ($tokens[$stackPtr + 1]['content'] !== ' ') {
			$error = 'There must be a single space after the USE keyword';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterUse');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
			}
		}

		// Namespaces in use statements must not have a leading separator
		$next = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		if ($tokens[$next]['code'] === T_NS_SEPARATOR) {
			$error = 'Namespaces in use statements should not start with a namespace separator';
			$fix = $phpcsFile->addFixableError($error, $next, 'NamespaceStart');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($next, '');
			}
		}

		// Only one USE declaration allowed per statement.
		$next = $phpcsFile->findNext([T_COMMA, T_SEMICOLON], $stackPtr + 1);
		if ($tokens[$next]['code'] === T_COMMA) {
			$error = 'There must be one USE keyword per declaration';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'MultipleDeclarations');
			if ($fix === true) {
				$phpcsFile->fixer->replaceToken($next, ';' . $phpcsFile->eolChar . 'use ');
			}
		} else {
			$nextUse = $phpcsFile->findNext(T_USE, $next + 1);
			if ($nextUse && !$this->shouldIgnoreUse($phpcsFile, $nextUse)) {
				if ($tokens[$nextUse]['line'] > $tokens[$next]['line'] + 1) {
					$error = 'There should not be newlines between use statements';
					$fix = $phpcsFile->addFixableError($error, $nextUse, 'NewlineBetweenUse');
					if ($fix) {
						$phpcsFile->fixer->replaceToken($nextUse - 1, '');
					}
				}
			}
		}

		// Make sure this USE comes after the first namespace declaration.
		$prev = $phpcsFile->findPrevious(T_NAMESPACE, $stackPtr - 1);
		if ($prev !== false) {
			$first = $phpcsFile->findNext(T_NAMESPACE, 1);
			if ($prev !== $first) {
				$error = 'USE declarations must go after the first namespace declaration';
				$phpcsFile->addError($error, $stackPtr, 'UseAfterNamespace');
			}
		}

		// Only interested in the last USE statement from here onwards.
		$nextUse = $phpcsFile->findNext(T_USE, $stackPtr + 1);
		while ($this->shouldIgnoreUse($phpcsFile, $nextUse)) {
			$nextUse = $phpcsFile->findNext(T_USE, $nextUse + 1);
			if ($nextUse === false) {
				break;
			}
		}

		if ($nextUse !== false) {
			return;
		}

		$end = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
		$next = $phpcsFile->findNext(T_WHITESPACE, $end + 1, null, true);
		$diff = ($tokens[$next]['line'] - $tokens[$end]['line'] - 1);
		if ($diff !== 1) {
			if ($diff < 0) {
				$diff = 0;
			}

			$error = 'There must be one blank line after the last USE statement; %s found;';
			$data = [$diff];
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterLastUse', $data);
			if ($fix === true) {
				if ($diff === 0) {
					$phpcsFile->fixer->addNewline($end);
				} else {
					$phpcsFile->fixer->beginChangeset();
					for ($i = ($end + 1); $i < $next; $i++) {
						if ($tokens[$i]['line'] === $tokens[$next]['line']) {
							break;
						}

						$phpcsFile->fixer->replaceToken($i, '');
					}

					$phpcsFile->fixer->addNewline($end);
					$phpcsFile->fixer->endChangeset();
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_USE];
	}

}
