<?php
/**
 * PSR2_Sniffs_ControlStructures_ElseIfDeclarationSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

namespace PSR2R\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * PSR2_Sniffs_ControlStructures_ElseIfDeclarationSniff.
 *
 * Verifies that there are no else if statements. Elseif should be used instead.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @version   Release: @package_version@
 *
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class ElseIfDeclarationSniff implements Sniff {

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['code'] === T_ELSEIF) {
			$phpcsFile->recordMetric($stackPtr, 'Use of ELSE IF or ELSEIF', 'elseif');

			return;
		}

		$next = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
		if ($tokens[$next]['code'] === T_IF) {
			$phpcsFile->recordMetric($stackPtr, 'Use of ELSE IF or ELSEIF', 'else if');
			$error = 'Usage of ELSE IF is discouraged; use ELSEIF instead';
			$fix = $phpcsFile->addFixableWarning($error, $stackPtr, 'NotAllowed');

			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken($stackPtr, 'elseif');
				for ($i = ($stackPtr + 1); $i <= $next; $i++) {
					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_ELSE,
			T_ELSEIF,
		];
	}

}
