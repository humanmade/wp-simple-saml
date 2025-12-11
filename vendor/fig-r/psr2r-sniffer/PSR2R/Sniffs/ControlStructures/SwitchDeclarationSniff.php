<?php
/**
 * PSR2_Sniffs_ControlStructures_SwitchDeclarationSniff.
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

namespace PSR2R\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * PSR2_Sniffs_ControlStructures_SwitchDeclarationSniff.
 *
 * Ensures all switch statements are defined correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class SwitchDeclarationSniff implements Sniff {

	/**
	 * The number of spaces code should be indented.
	 *
	 * @var int
	 */
	public $indent = 4;

	/**
	 * @inheritDoc
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// TODO: Auto-detect spaces vs tabs, maybe using a trait method indent($index, $this->indent)
		$this->indent = 1;

		// We can't process SWITCH statements unless we know where they start and end.
		if (isset($tokens[$stackPtr]['scope_opener']) === false
			|| isset($tokens[$stackPtr]['scope_closer']) === false
		) {
			return;
		}

		$switch = $tokens[$stackPtr];
		$nextCase = $stackPtr;
		$caseAlignment = ($switch['column'] + $this->indent);

		while (($nextCase = $this->findNextCase($phpcsFile, $nextCase + 1, $switch['scope_closer'])) !== false) {
			$type = 'case';
			if ($tokens[$nextCase]['code'] === T_DEFAULT) {
				$type = 'default';
			}

			if ($tokens[$nextCase]['content'] !== strtolower($tokens[$nextCase]['content'])) {
				$expected = strtolower($tokens[$nextCase]['content']);
				$error = strtoupper($type) . ' keyword must be lowercase; expected "%s" but found "%s"';
				$data = [
					$expected,
					$tokens[$nextCase]['content'],
				];

				$fix = $phpcsFile->addFixableError($error, $nextCase, $type . 'NotLower', $data);
				if ($fix === true) {
					$phpcsFile->fixer->replaceToken($nextCase, $expected);
				}
			}

			if ($type === 'case'
				&& ($tokens[$nextCase + 1]['code'] !== T_WHITESPACE
					|| $tokens[$nextCase + 1]['content'] !== ' ')
			) {
				$error = 'CASE keyword must be followed by a single space';
				$fix = $phpcsFile->addFixableError($error, $nextCase, 'SpacingAfterCase');
				if ($fix === true) {
					if ($tokens[$nextCase + 1]['code'] !== T_WHITESPACE) {
						$phpcsFile->fixer->addContent($nextCase, ' ');
					} else {
						$phpcsFile->fixer->replaceToken($nextCase + 1, ' ');
					}
				}
			}

			$opener = $tokens[$nextCase]['scope_opener'];
			if ($tokens[$opener]['code'] === T_COLON) {
				if ($tokens[$opener - 1]['code'] === T_WHITESPACE) {
					$error = 'There must be no space before the colon in a ' . strtoupper($type) . ' statement';
					$fix = $phpcsFile->addFixableError($error, $nextCase, 'SpaceBeforeColon' . strtoupper($type));
					if ($fix === true) {
						$phpcsFile->fixer->replaceToken($opener - 1, '');
					}
				}

				$next = $phpcsFile->findNext(T_WHITESPACE, $opener + 1, null, true);
				if ($tokens[$next]['code'] === T_COMMENT && $tokens[$next]['line'] === $tokens[$opener]['line']) {
					// Skip comments on the same line.
					$next = $phpcsFile->findNext(T_WHITESPACE, $next + 1, null, true);
				}

				if ($tokens[$next]['line'] !== ($tokens[$opener]['line'] + 1)) {
					$error = 'The ' . strtoupper($type) . ' body must start on the line following the statement';
					$fix = $phpcsFile->addFixableError($error, $nextCase, 'SpaceBeforeColon' . strtoupper($type));
					if ($fix === true) {
						if ($tokens[$next]['line'] === $tokens[$opener]['line']) {
							$padding = str_repeat(' ', $caseAlignment + $this->indent - 1);
							$phpcsFile->fixer->addContentBefore($next, $phpcsFile->eolChar . $padding);
						} else {
							$phpcsFile->fixer->beginChangeset();
							for ($i = ($opener + 1); $i < $next; $i++) {
								if ($tokens[$i]['line'] === $tokens[$next]['line']) {
									break;
								}

								$phpcsFile->fixer->replaceToken($i, '');
							}

							$phpcsFile->fixer->addNewlineBefore($i);
							$phpcsFile->fixer->endChangeset();
						}
					}
				}
			} else {
				$error = strtoupper($type) . ' statements must be defined using a colon';
				$phpcsFile->addError($error, $nextCase, 'WrongOpener' . $type);
			}

			$nextCloser = $tokens[$nextCase]['scope_closer'];
			if ($tokens[$nextCloser]['scope_condition'] === $nextCase) {
				// Only need to check some things once, even if the
				// closer is shared between multiple case statements, or even
				// the default case.
				$prev = $phpcsFile->findPrevious(T_WHITESPACE, $nextCloser - 1, $nextCase, true);
				if ($tokens[$prev]['line'] === $tokens[$nextCloser]['line']) {
					$error = 'Terminating statement must be on a line by itself';
					$fix = $phpcsFile->addFixableError($error, $nextCloser, 'BreakNotNewLine');
					if ($fix === true) {
						$phpcsFile->fixer->addNewline($prev);
						$phpcsFile->fixer->replaceToken($nextCloser, trim($tokens[$nextCloser]['content']));
					}
				} else {
					$diff = ($caseAlignment + $this->indent - $tokens[$nextCloser]['column']);
					if ($diff !== 0) {
						$error = 'Terminating statement must be indented to the same level as the CASE body';
						$fix = $phpcsFile->addFixableError($error, $nextCloser, 'BreakIndent');
						if ($fix === true) {
							if ($diff > 0) {
								$phpcsFile->fixer->addContentBefore($nextCloser, str_repeat(' ', $diff));
							} else {
								$phpcsFile->fixer->substrToken($nextCloser - 1, 0, $diff);
							}
						}
					}
				}
			}

			// We only want cases from here on in.
			if ($type !== 'case') {
				continue;
			}

			$nextCode = $phpcsFile->findNext(T_WHITESPACE,
				$tokens[$nextCase]['scope_opener'] + 1,
				$nextCloser,
				true);

			if ($tokens[$nextCode]['code'] !== T_CASE && $tokens[$nextCode]['code'] !== T_DEFAULT) {
				// This case statement has content. If the next case or default comes
				// before the closer, it means we dont have a terminating statement
				// and instead need a comment.
				$nextCode = $this->findNextCase($phpcsFile, $tokens[$nextCase]['scope_opener'] + 1, $nextCloser);
				if ($nextCode !== false) {
					$prevCode = $phpcsFile->findPrevious(T_WHITESPACE, $nextCode - 1, $nextCase, true);
					if ($tokens[$prevCode]['code'] !== T_COMMENT) {
						$error = 'There must be a comment when fall-through is intentional in a non-empty case body';
						$phpcsFile->addError($error, $nextCase, 'TerminatingComment');
					}
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_SWITCH];
	}

	/**
	 * Find the next CASE or DEFAULT statement from a point in the file.
	 *
	 * Note that nested switches are ignored.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int|bool $stackPtr The position to start looking at.
	 * @param int $end The position to stop looking at.
	 *
	 * @return int|bool
	 */
	protected function findNextCase(File $phpcsFile, $stackPtr, $end) {
		$tokens = $phpcsFile->getTokens();
		while (($stackPtr = $phpcsFile->findNext([T_CASE, T_DEFAULT, T_SWITCH], $stackPtr, $end)) !== false) {
			// Skip nested SWITCH statements; they are handled on their own.
			if ($tokens[$stackPtr]['code'] === T_SWITCH) {
				$stackPtr = $tokens[$stackPtr]['scope_closer'];
				continue;
			}

			break;
		}

		return $stackPtr;
	}

}
