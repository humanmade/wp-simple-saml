<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PSR2R\Tools\AbstractSniff;

class EmptyLinesSniff extends AbstractSniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = [
		'PHP',
		'JS',
		'CSS',
	];

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_WHITESPACE];
	}

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$this->assertMaximumOneEmptyLineBetweenContent($phpcsFile, $stackPtr);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 *
	 * @return void
	 */
	protected function assertMaximumOneEmptyLineBetweenContent(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		if ($tokens[$stackPtr]['content'] === $phpcsFile->eolChar
			&& isset($tokens[($stackPtr + 1)])
			&& $tokens[($stackPtr + 1)]['content'] === $phpcsFile->eolChar
			&& isset($tokens[($stackPtr + 2)])
			&& $tokens[($stackPtr + 2)]['content'] === $phpcsFile->eolChar
		) {
			$error = 'Found more than a single empty line between content';
			$fix = $phpcsFile->addFixableError($error, ($stackPtr + 2), 'EmptyLines');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($stackPtr + 2, '');
			}
		}
	}

}
