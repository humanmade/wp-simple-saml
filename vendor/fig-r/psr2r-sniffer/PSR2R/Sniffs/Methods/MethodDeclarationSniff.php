<?php
/**
 * PSR2_Sniffs_Methods_MethodDeclarationSniff.
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

namespace PSR2R\Sniffs\Methods;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * PSR2_Sniffs_Methods_MethodDeclarationSniff.
 *
 * Checks that the method declaration is correct.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class MethodDeclarationSniff extends AbstractScopeSniff {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct([T_CLASS, T_INTERFACE], [T_FUNCTION]);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function processTokenOutsideScope(File $phpcsFile, $stackPtr) {
	}

	/**
	 * @inheritDoc
	 * @throws \PHP_CodeSniffer\Exceptions\RuntimeException
	 */
	protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope) {
		$tokens = $phpcsFile->getTokens();

		$methodName = $phpcsFile->getDeclarationName($stackPtr);
		if ($methodName === null) {
			// Ignore closures.
			return;
		}

		$visibility = 0;
		$static = 0;
		$abstract = 0;
		$final = 0;

		$find = Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;
		$prev = $phpcsFile->findPrevious($find, $stackPtr - 1, null, true);

		$prefix = $stackPtr;
		while (($prefix = $phpcsFile->findPrevious(Tokens::$methodPrefixes, $prefix - 1, $prev)) !== false) {
			switch ($tokens[$prefix]['code']) {
				case T_STATIC:
					$static = $prefix;
					break;
				case T_ABSTRACT:
					$abstract = $prefix;
					break;
				case T_FINAL:
					$final = $prefix;
					break;
				default:
					$visibility = $prefix;
					break;
			}
		}

		$fixes = [];

		if ($visibility !== 0 && $final > $visibility) {
			$error = 'The final declaration must precede the visibility declaration';
			$fix = $phpcsFile->addFixableError($error, $final, 'FinalAfterVisibility');
			if ($fix === true) {
				$fixes[$final] = '';
				$fixes[$final + 1] = '';
				if (isset($fixes[$visibility]) === true) {
					$fixes[$visibility] = 'final ' . $fixes[$visibility];
				} else {
					$fixes[$visibility] = 'final ' . $tokens[$visibility]['content'];
				}
			}
		}

		if ($visibility !== 0 && $abstract > $visibility) {
			$error = 'The abstract declaration must precede the visibility declaration';
			$fix = $phpcsFile->addFixableError($error, $abstract, 'AbstractAfterVisibility');
			if ($fix === true) {
				$fixes[$abstract] = '';
				$fixes[$abstract + 1] = '';
				if (isset($fixes[$visibility]) === true) {
					$fixes[$visibility] = 'abstract ' . $fixes[$visibility];
				} else {
					$fixes[$visibility] = 'abstract ' . $tokens[$visibility]['content'];
				}
			}
		}

		if ($static !== 0 && $static < $visibility) {
			$error = 'The static declaration must come after the visibility declaration';
			$fix = $phpcsFile->addFixableError($error, $static, 'StaticBeforeVisibility');
			if ($fix === true) {
				$fixes[$static] = '';
				$fixes[$static + 1] = '';
				if (isset($fixes[$visibility]) === true) {
					$fixes[$visibility] .= ' static';
				} else {
					$fixes[$visibility] = $tokens[$visibility]['content'] . ' static';
				}
			}
		}

		// Batch all the fixes together to reduce the possibility of conflicts.
		if (empty($fixes) === false) {
			$phpcsFile->fixer->beginChangeset();
			foreach ($fixes as $stackPtr2 => $content) {
				$phpcsFile->fixer->replaceToken($stackPtr2, $content);
			}

			$phpcsFile->fixer->endChangeset();
		}
	}

}
