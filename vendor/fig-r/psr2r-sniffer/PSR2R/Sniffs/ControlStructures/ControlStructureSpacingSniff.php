<?php
/**
 * PSR2_Sniffs_WhiteSpace_ControlStructureSpacingSniff.
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
 * PSR2_Sniffs_WhiteSpace_ControlStructureSpacingSniff.
 *
 * Checks that control structures have the correct spacing around brackets.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 *
 * @version   Release: @package_version@
 *
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class ControlStructureSpacingSniff implements Sniff {

	/**
	 * How many spaces should follow the opening bracket.
	 *
	 * @var int
	 */
	public $requiredSpacesAfterOpen = 0;

	/**
	 * How many spaces should precede the closing bracket.
	 *
	 * @var int
	 */
	public $requiredSpacesBeforeClose = 0;

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$this->requiredSpacesAfterOpen = (int)$this->requiredSpacesAfterOpen;
		$this->requiredSpacesBeforeClose = (int)$this->requiredSpacesBeforeClose;
		$tokens = $phpcsFile->getTokens();

		if (isset($tokens[$stackPtr]['parenthesis_opener']) === false
			|| isset($tokens[$stackPtr]['parenthesis_closer']) === false
		) {
			return;
		}

		$parenOpener = $tokens[$stackPtr]['parenthesis_opener'];
		$parenCloser = $tokens[$stackPtr]['parenthesis_closer'];
		$spaceAfterOpen = 0;
		if ($tokens[$parenOpener + 1]['code'] === T_WHITESPACE) {
			if (strpos($tokens[$parenOpener + 1]['content'], $phpcsFile->eolChar) !== false) {
				$spaceAfterOpen = 'newline';
			} else {
				$spaceAfterOpen = strlen($tokens[$parenOpener + 1]['content']);
			}
		}

		$phpcsFile->recordMetric($stackPtr, 'Spaces after control structure open parenthesis', $spaceAfterOpen);

		if (($spaceAfterOpen !== $this->requiredSpacesAfterOpen) &&
			($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line'])
		) {
			$error = 'Expected %s spaces after opening bracket; %s found';
			$data = [
				$this->requiredSpacesAfterOpen,
				$spaceAfterOpen,
			];
			$fix = $phpcsFile->addFixableError($error, $parenOpener + 1, 'SpacingAfterOpenBrace', $data);
			if ($fix === true) {
				$padding = str_repeat(' ', $this->requiredSpacesAfterOpen);
				if ($spaceAfterOpen === 0) {
					$phpcsFile->fixer->addContent($parenOpener, $padding);
				} elseif ($spaceAfterOpen === 'newline') {
					$phpcsFile->fixer->replaceToken($parenOpener + 1, '');
				} else {
					$phpcsFile->fixer->replaceToken($parenOpener + 1, $padding);
				}
			}
		}

		if ($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line']) {
			$spaceBeforeClose = 0;
			if ($tokens[$parenCloser - 1]['code'] === T_WHITESPACE) {
				$spaceBeforeClose = strlen(ltrim($tokens[$parenCloser - 1]['content'], $phpcsFile->eolChar));
			}

			$phpcsFile->recordMetric($stackPtr, 'Spaces before control structure close parenthesis', (string)$spaceBeforeClose);

			if ($spaceBeforeClose !== $this->requiredSpacesBeforeClose) {
				$error = 'Expected %s spaces before closing bracket; %s found';
				$data = [
					$this->requiredSpacesBeforeClose,
					$spaceBeforeClose,
				];
				$fix = $phpcsFile->addFixableError($error, $parenCloser - 1, 'SpaceBeforeCloseBrace', $data);
				if ($fix === true) {
					$padding = str_repeat(' ', $this->requiredSpacesBeforeClose);
					if ($spaceBeforeClose === 0) {
						$phpcsFile->fixer->addContentBefore($parenCloser, $padding);
					} else {
						$phpcsFile->fixer->replaceToken($parenCloser - 1, $padding);
					}
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_TRY,
			T_CATCH,
		];
	}

}
