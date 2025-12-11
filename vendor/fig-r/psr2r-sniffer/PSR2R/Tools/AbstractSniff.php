<?php

namespace PSR2R\Tools;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

abstract class AbstractSniff implements Sniff {

	/**
	 * @var array These markers must remain as inline comments
	 */
	protected static $phpStormMarkers = ['@noinspection'];

	/** @noinspection MoreThanThreeArgumentsInspection */

	/**
	 * Checks if the given token scope contains a single or multiple token codes/types.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param string|int|array $search
	 * @param int $start
	 * @param int $end
	 * @param bool $skipNested
	 * @return bool
	 */
	protected function contains(File $phpcsFile, $search, $start, $end, $skipNested = true) {
		$tokens = $phpcsFile->getTokens();

		for ($i = $start; $i <= $end; $i++) {
			if ($skipNested && $tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
				$i = $tokens[$i]['parenthesis_closer'];
				continue;
			}
			if ($skipNested && $tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
				$i = $tokens[$i]['bracket_closer'];
				continue;
			}
			if ($skipNested && $tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
				$i = $tokens[$i]['bracket_closer'];
				continue;
			}

			if ($this->isGivenKind($search, $tokens[$i])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the given token is of this token code/type.
	 *
	 * @param array|int|string $search
	 * @param array $token
	 * @return bool
	 */
	protected function isGivenKind($search, array $token) {
		$search = (array)$search;
		if (in_array($token['code'], $search, true)) {
			return true;
		}
		if (in_array($token['type'], $search, true)) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the given token scope requires brackets when used standalone.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $openingBraceIndex
	 * @param int $closingBraceIndex
	 * @return bool
	 */
	protected function needsBrackets(File $phpcsFile, $openingBraceIndex, $closingBraceIndex) {
		$tokens = $phpcsFile->getTokens();

		$whitelistedCodes = [
			T_LNUMBER,
			T_STRING,
			T_BOOL_CAST,
			T_STRING_CAST,
			T_INT_CAST,
			T_ARRAY_CAST,
			T_COMMENT,
			T_WHITESPACE,
			T_VARIABLE,
			T_DOUBLE_COLON,
			T_OBJECT_OPERATOR,
		];

		for ($i = $openingBraceIndex + 1; $i < $closingBraceIndex; $i++) {
			if ($tokens[$i]['type'] === 'T_OPEN_PARENTHESIS') {
				$i = $tokens[$i]['parenthesis_closer'];
				continue;
			}
			if (in_array($tokens[$i]['code'], $whitelistedCodes, false)) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $stackPointer
	 *
	 * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
	 */
	protected function findRelatedDocBlock(File $phpCsFile, $stackPointer) {
		$tokens = $phpCsFile->getTokens();

		$line = $tokens[$stackPointer]['line'];
		$beginningOfLine = $stackPointer;
		while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
			$beginningOfLine--;
		}

		if (!empty($tokens[$beginningOfLine - 2]) &&
			$tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG'
		) {
			return $beginningOfLine - 2;
		}

		return null;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @param int $count
	 * @return void
	 */
	protected function outdent(File $phpcsFile, $index, $count = 1) {
		$tokens = $phpcsFile->getTokens();
		$char = $this->getIndentationCharacter($tokens[$index]['content'], true);

		$content = $tokens[$index]['content'];
		for ($i = 0; $i < $count; $i++) {
			$content = $this->strReplaceOnce($char, '', $content);
		}
		$phpcsFile->fixer->replaceToken($index, $content);
	}

	/**
	 * @param string $content
	 * @param bool $correctLength
	 * @return string
	 */
	protected function getIndentationCharacter($content, $correctLength = false) {
		if (strpos($content, "\n")) {
			$parts = explode("\n", $content);
			array_shift($parts);
		} else {
			$parts = (array)$content;
		}

		$char = "\t";
		$countTabs = $countSpaces = 0;
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach ($parts as $part) {
			$countTabs += substr_count($content, $char);
			$countSpaces += (int)(substr_count($content, ' ') / 4);
		}

		if ($countSpaces > 3 && $countSpaces > $countTabs) {
			$char = $correctLength ? '    ' : ' ';
		}

		return $char;
	}

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	protected function strReplaceOnce($search, $replace, $subject) {
		$pos = strpos($subject, $search);
		if ($pos === false) {
			return $subject;
		}

		return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @param int $count
	 * @return void
	 */
	protected function indent(File $phpcsFile, $index, $count = 1) {
		$tokens = $phpcsFile->getTokens();
		$char = $this->getIndentationCharacter($tokens[$index]['content'], true);

		$content = str_repeat($char, $count) . $tokens[$index]['content'];
		$phpcsFile->fixer->replaceToken($index, $content);
	}

	/**
	 * Get level of indentation, 0 based.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return int
	 */
	protected function getIndentationLevel(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$whitespace = $this->getIndentationWhitespace($phpcsFile, $index);
		$char = $this->getIndentationCharacter($whitespace);

		$level = $tokens[$index]['column'] - 1;

		if ($char === "\t") {
			return $level;
		}

		return (int)($level / 4);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return string
	 */
	protected function getIndentationWhitespace(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$firstIndex = $this->getFirstTokenOfLine($tokens, $index);
		$whitespace = '';
		if ($tokens[$firstIndex]['type'] === 'T_WHITESPACE' ||
			$tokens[$firstIndex]['type'] === 'T_DOC_COMMENT_WHITESPACE'
		) {
			$whitespace = $tokens[$firstIndex]['content'];
		}

		return $whitespace;
	}

	/**
	 * @param array $tokens
	 * @param int $index
	 * @return int
	 */
	protected function getFirstTokenOfLine(array $tokens, $index) {
		$line = $tokens[$index]['line'];

		$currentIndex = $index;
		while ($tokens[$currentIndex - 1]['line'] === $line) {
			$currentIndex--;
		}

		return $currentIndex;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $index
	 * @return int
	 */
	protected function getIndentationColumn(File $phpcsFile, $index) {
		$tokens = $phpcsFile->getTokens();

		$firstIndex = $this->getFirstTokenOfLine($tokens, $index);

		$nextIndex = $phpcsFile->findNext(T_WHITESPACE, $firstIndex + 1, null, true);
		if ($tokens[$nextIndex]['line'] !== $tokens[$index]['line']) {
			return 0;
		}
		return $tokens[$nextIndex]['column'] - 1;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @return bool
	 */
	protected function hasNamespace(File $phpCsFile) {
		return $this->findNamespaceIndex($phpCsFile) !== null;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @return int|null
	 */
	protected function findNamespaceIndex(File $phpCsFile) {
		$namespacePosition = $phpCsFile->findNext(T_NAMESPACE, 0);
		if (!$namespacePosition) {
			return null;
		}
		return $namespacePosition;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @return array
	 */
	protected function getNamespaceInfo(File $phpcsFile) {
		$startIndex = $this->findNamespaceIndex($phpcsFile);

		$endIndex = 0;
		if ($startIndex) {
			$endIndex = $phpcsFile->findNext(T_SEMICOLON, $startIndex + 1);
		}

		/** @noinspection IsEmptyFunctionUsageInspection */
		if (empty($startIndex) || empty($endIndex)) {
			return [];
		}

		return [
			'start' => $startIndex,
			'namespace' => $this->getNamespaceAsString($phpcsFile, $startIndex + 1, $endIndex - 1),
			'end' => $endIndex,
		];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $startIndex
	 * @param int $endIndex
	 *
	 * @return string
	 */
	protected function getNamespaceAsString(File $phpCsFile, $startIndex, $endIndex) {
		$tokens = $phpCsFile->getTokens();

		$namespace = '';
		for ($i = $startIndex; $i <= $endIndex; $i++) {
			$namespace .= $tokens[$i]['content'];
		}

		return trim($namespace);
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpCsFile
	 * @param int $stackPtr
	 * @return bool
	 */
	protected function isPhpStormMarker(File $phpCsFile, $stackPtr) {
		$tokens = $phpCsFile->getTokens();
		$line = $tokens[$stackPtr]['line'];
		if ($tokens[$stackPtr]['type'] !== 'T_DOC_COMMENT_OPEN_TAG') {
			return false;
		}
		$end = $tokens[$stackPtr]['comment_closer'] - 1;
		if ($line !== $tokens[$end]['line']) {
			return false; // Not an inline comment
		}
		foreach (static::$phpStormMarkers as $marker) {
			if ($phpCsFile->findNext(T_DOC_COMMENT_TAG, $stackPtr + 1, $end, false, $marker) !== false) {
				return true;
			}
		}
		return false;
	}

}
