<?php

namespace PSR2R\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Check for any line starting with 4 spaces - which would indicate space indenting.
 *
 * @author  Mark Scherer
 * @license MIT
 */
class TabIndentSniff implements Sniff {

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
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['code'] !== T_WHITESPACE) {
			// Doc block
			if (empty($tokens[$stackPtr]['comment_closer'])) {
				return;
			}

			for ($i = $stackPtr + 1; $i < $tokens[$stackPtr]['comment_closer']; $i++) {
				if ($tokens[$i]['code'] === 'PHPCS_T_DOC_COMMENT_WHITESPACE' && $tokens[$i]['column'] === 1) {
					$this->fixTab($phpcsFile, $i, $tokens);
				} /** @noinspection NotOptimalIfConditionsInspection */ elseif ($tokens[$i]['code'] ===
					'PHPCS_T_DOC_COMMENT_WHITESPACE'
				) {
					$this->fixSpace($phpcsFile, $i, $tokens);
				}
			}
			return;
		}

		$line = $tokens[$stackPtr]['line'];
		if ($stackPtr > 0 && $tokens[$stackPtr - 1]['line'] === $line) {
			return;
		}

		$this->fixTab($phpcsFile, $stackPtr, $tokens);
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_WHITESPACE, T_DOC_COMMENT_OPEN_TAG];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @param array $tokens
	 * @return void
	 */
	protected function fixTab(File $phpcsFile, $stackPtr, $tokens) {
		$content = $tokens[$stackPtr]['content'];
		$tabs = 0;
		while (strpos($content, '    ') === 0) {
			$content = substr($content, 4);
			$tabs++;
		}

		if ($tabs) {
			$error = ($tabs * 4) . ' spaces found, expected ' . $tabs . ' tabs';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpacesFound');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($stackPtr, str_repeat("\t", $tabs) . $content);
			}
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @param array $tokens
	 * @return void
	 */
	protected function fixSpace(File $phpcsFile, $stackPtr, $tokens) {
		$content = $tokens[$stackPtr]['content'];

		$newContent = str_replace("\t", '    ', $content);

		if ($newContent !== $content) {
			$error = 'Non-indentation (inline) tabs found, expected spaces';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'TabsFound');
			if ($fix) {
				$phpcsFile->fixer->replaceToken($stackPtr, $newContent);
			}
		}
	}

}
