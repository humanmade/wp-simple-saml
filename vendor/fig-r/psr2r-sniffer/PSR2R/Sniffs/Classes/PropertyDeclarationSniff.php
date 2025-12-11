<?php

namespace PSR2R\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that properties are declared correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @version   Release: @package_version@
 *
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PropertyDeclarationSniff extends AbstractVariableSniff {
	/**
	 * @inheritDoc
	 */
	protected function processMemberVar(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Detect multiple properties defined at the same time. Throw an error
		// for this, but also only process the first property in the list so we don't
		// repeat errors.
		$find = Tokens::$scopeModifiers;
		$find = array_merge($find, [T_VARIABLE, T_VAR, T_SEMICOLON]);
		$prev = $phpcsFile->findPrevious($find, $stackPtr - 1);
		if ($tokens[$prev]['code'] === T_VARIABLE) {
			return;
		}

		if ($tokens[$prev]['code'] === T_VAR) {
			$error = 'The var keyword must not be used to declare a property';
			$phpcsFile->addError($error, $stackPtr, 'VarUsed');
		}

		$next = $phpcsFile->findNext([T_VARIABLE, T_SEMICOLON], $stackPtr + 1);
		if ($tokens[$next]['code'] === T_VARIABLE) {
			$error = 'There must not be more than one property declared per statement';
			$phpcsFile->addError($error, $stackPtr, 'Multiple');
		}

		$modifier = $phpcsFile->findPrevious(Tokens::$scopeModifiers, $stackPtr);
		if (($modifier === false) || ($tokens[$modifier]['line'] !== $tokens[$stackPtr]['line'])) {
			$error = 'Visibility must be declared on property "%s"';
			$data = [$tokens[$stackPtr]['content']];
			$phpcsFile->addError($error, $stackPtr, 'ScopeMissing', $data);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function processVariable(File $phpcsFile, $stackPtr) {
		/*
            We don't care about normal variables.
        */
	}

	/**
	 * @inheritDoc
	 */
	protected function processVariableInString(File $phpcsFile, $stackPtr) {
		/*
            We don't care about normal variables.
        */
	}

}
