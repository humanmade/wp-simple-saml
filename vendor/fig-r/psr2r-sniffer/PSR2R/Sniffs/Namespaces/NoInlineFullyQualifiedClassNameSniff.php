<?php

namespace PSR2R\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PSR2R\Tools\AbstractSniff;
use PSR2R\Tools\Traits\CommentingTrait;
use PSR2R\Tools\Traits\NamespaceTrait;
use RuntimeException;

/**
 * All inline FQCN must be moved to use statements.
 */
class NoInlineFullyQualifiedClassNameSniff extends AbstractSniff {

	use CommentingTrait;
	use NamespaceTrait;

	/**
	 * @var array
	 */
	protected $existingStatements;

	/**
	 * @var array
	 */
	protected $newStatements = [];

	/**
	 * @var array
	 */
	protected $allStatements;

	/**
	 * @var array
	 */
	protected $useStatements;

	/**
	 * @var int Last token we will process
	 */
	protected $sentinelPtr;

	/**
	 * @var \PHP_CodeSniffer\Files\File
	 */
	protected $sentinelFile;

	/**
	 * @inheritDoc
	 * @throws \RuntimeException
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Skip non-namespaces files for now
		if (!$this->hasNamespace($phpcsFile)) {
			return;
		}

		$this->loadStatements($phpcsFile);
		$this->findSentinel($phpcsFile);

		if ($tokens[$stackPtr]['code'] === T_CLASS || $tokens[$stackPtr]['code'] === T_INTERFACE || $tokens[$stackPtr]['code'] === T_TRAIT) {
			$this->checkUseForClass($phpcsFile, $stackPtr);
		} elseif ($tokens[$stackPtr]['code'] === T_NEW) {
			$this->checkUseForNew($phpcsFile, $stackPtr);
		} elseif ($tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
			$this->checkUseForStatic($phpcsFile, $stackPtr);
		} elseif ($tokens[$stackPtr]['code'] === T_INSTANCEOF) {
			$this->checkUseForInstanceOf($phpcsFile, $stackPtr);
		} elseif ($tokens[$stackPtr]['code'] === T_CATCH || $tokens[$stackPtr]['code'] === T_CALLABLE) {
			$this->checkUseForCatchOrCallable($phpcsFile, $stackPtr);
		} else {
			$this->checkUseForSignature($phpcsFile, $stackPtr);
			$this->checkUseForReturnTypeHint($phpcsFile, $stackPtr);
		}
		$this->insertUseWhenSentinel($phpcsFile, $stackPtr);
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_NEW, T_FUNCTION, T_DOUBLE_COLON, T_CLASS];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
	 *
	 * @return void
	 */
	protected function loadStatements(File $phpcsFile) {
		if ($this->existingStatements !== null) {
			return;
		}

		$existingStatements = $this->getUseStatements($phpcsFile);
		$this->existingStatements = $existingStatements;
		$this->allStatements = $existingStatements;
		$this->newStatements = [];
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @return array
	 */
	protected function getUseStatements(File $phpcsFile) {
		$tokens = $phpcsFile->getTokens();

		$statements = [];
		foreach ($tokens as $index => $token) {
			if ($token['code'] !== T_USE) {
				continue;
			}
			if ($this->shouldIgnoreUse($phpcsFile, $index)) {
				continue;
			}

			$useStatementStartIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $index + 1, null, true);

			// Ignore function () use ($foo) {}
			if ($tokens[$useStatementStartIndex]['content'] === '(') {
				continue;
			}

			$semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $useStatementStartIndex + 1);
			$useStatementEndIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $semicolonIndex - 1, null, true);

			$statement = '';
			for ($i = $useStatementStartIndex; $i <= $useStatementEndIndex; $i++) {
				$statement .= $tokens[$i]['content'];
			}

			if ($this->isMultipleUseStatement($statement)) {
				continue;
			}

			$statementParts = preg_split('/\s+as\s+/i', $statement);

			if (count($statementParts) === 1) {
				$fullName = $statement;
				$statementParts = explode('\\', $fullName);
				$shortName = end($statementParts);
				$alias = null;
			} else {
				$fullName = $statementParts[0];
				/** @noinspection MultiAssignmentUsageInspection */
				$alias = $statementParts[1];
				$statementParts = explode('\\', $fullName);
				$shortName = end($statementParts);
			}

			$shortName = trim($shortName);
			$fullName = trim($fullName);
			$key = $alias ?: $shortName;

			$statements[$key] = [
				'alias' => $alias,
				'end' => $semicolonIndex,
				'fullName' => ltrim($fullName, '\\'),
				'shortName' => $shortName,
				'start' => $index,
			];
		}

		return $statements;
	}

	/**
	 * Another sniff takes care of that, we just ignore then.
	 *
	 * @param string $statementContent
	 *
	 * @return bool
	 */
	protected function isMultipleUseStatement($statementContent) {
		return strpos($statementContent, ',') !== false;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @return void
	 */
	protected function findSentinel(File $phpcsFile) {
		if ($this->sentinelFile !== $phpcsFile) {
			$tokens = $phpcsFile->getTokens();
			$last = count($tokens) - 1;
			$this->sentinelPtr = $phpcsFile->findPrevious($this->register(), $last);
			$this->sentinelFile = $phpcsFile;
			$this->useStatements = [];
		}
	}

	/**
	 * Checks extends, implements.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForClass(File $phpcsFile, $stackPtr) {
		$nextIndex = $phpcsFile->findNext(T_EXTENDS, $stackPtr + 1);
		if ($nextIndex) {
			$this->checkUseForExtends($phpcsFile, $nextIndex);
		}

		$nextIndex = $phpcsFile->findNext(T_IMPLEMENTS, $stackPtr + 1);
		if ($nextIndex) {
			$this->checkUseForImplements($phpcsFile, $nextIndex);
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $nextIndex
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForExtends(File $phpcsFile, $nextIndex) {
		$endIndex = $phpcsFile->findNext([T_IMPLEMENTS, T_CURLY_OPEN, T_OPEN_CURLY_BRACKET], $nextIndex + 1);

		$startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
		$endIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $endIndex - 1, null, true);

		if (!$this->contains($phpcsFile, T_NS_SEPARATOR, $startIndex, $endIndex)) {
			return;
		}

		$extractedUseStatements = $this->extractUseStatements($phpcsFile, $startIndex, $endIndex);
		foreach ($extractedUseStatements as $extractedUseStatement) {
			if (strpos($extractedUseStatement['statement'], '\\') === false) {
				continue;
			}

			$className = $this->extractClassNameFromUseStatementAsString($extractedUseStatement['statement']);
			$error = 'Use statement ' . $extractedUseStatement['statement'] . ' for ' . $className .
				' should be in use block.';
			$fix = $phpcsFile->addFixableError($error, $extractedUseStatement['start'], 'Extends');
			if (!$fix) {
				return;
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$phpcsFile->fixer->beginChangeset();
			$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement['statement']);

			for ($k = $extractedUseStatement['start']; $k < $extractedUseStatement['end']; ++$k) {
				$phpcsFile->fixer->replaceToken($k, '');
			}

			if ($addedUseStatement['alias'] !== null) {
				$phpcsFile->fixer->replaceToken($extractedUseStatement['end'], $addedUseStatement['alias']);
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $startIndex
	 * @param int $endIndex
	 * @return array
	 */
	protected function extractUseStatements(File $phpcsFile, $startIndex, $endIndex) {
		$tokens = $phpcsFile->getTokens();

		$result = [];
		$start = null;
		for ($i = $startIndex; $i <= $endIndex; ++$i) {
			if ($start === null && $this->isGivenKind(T_WHITESPACE, $tokens[$i])) {
				continue;
			}

			if ($this->isGivenKind(T_COMMA, $tokens[$i])) {
				$result[] = [
					'start' => $start,
					'end' => $phpcsFile->findPrevious(Tokens::$emptyTokens, $i - 1, null, true),
					'statement' => $this->extractUseStatementsAsString($tokens, $start, $i - 1),
				];
				$start = null;
				continue;
			}

			if ($start === null) {
				$start = $i;
			}
		}

		$result[] = [
			'start' => $start,
			'end' => $phpcsFile->findPrevious(Tokens::$emptyTokens, $i, null, true),
			'statement' => $this->extractUseStatementsAsString($tokens, $start, $i),
		];

		return $result;
	}

	/**
	 * @param array $tokens
	 * @param int $start
	 * @param int $end
	 * @return string
	 */
	protected function extractUseStatementsAsString(array $tokens, $start, $end) {
		$string = '';
		for ($i = $start; $i <= $end; ++$i) {
			$string .= $tokens[$i]['content'];
		}

		$string = trim($string);

		return ltrim($string, '\\');
	}

	/**
	 * @param string $useStatement
	 * @return string
	 */
	protected function extractClassNameFromUseStatementAsString($useStatement) {
		$lastSeparator = strrpos($useStatement, '\\');
		if ($lastSeparator === false) {
			return $useStatement;
		}

		return substr($useStatement, $lastSeparator + 1);
	}

	/**
	 * @param string $shortName
	 * @param string $fullName
	 *
	 * @return array
	 * @throws \RuntimeException
	 */
	protected function addUseStatement($shortName, $fullName) {
		foreach ($this->allStatements as $useStatement) {
			if ($useStatement['fullName'] === $fullName) {
				return $useStatement;
			}
		}

		$alias = $this->generateUniqueAlias($shortName, $fullName);
		if (!$alias) {
			throw new RuntimeException('Could not generate unique alias for `' . $shortName . ' (' . $fullName . ')`.');
		}

		$result = [
			'alias' => $alias === $shortName ? null : $alias,
			'fullName' => $fullName,
			'shortName' => $shortName,
		];
		$this->insertUseStatement($result);

		$this->allStatements[$alias] = $result;
		$this->newStatements[$alias] = $result;

		return $result;
	}

	/**
	 * @param string $shortName
	 * @param string $fullName
	 *
	 * @return string|null
	 */
	protected function generateUniqueAlias($shortName, $fullName) {
		$alias = $shortName;
		$count = 0;
		$pieces = explode('\\', $fullName);
		$pieces = array_reverse($pieces);
		array_shift($pieces);

		while (isset($this->allStatements[$alias])) {
			$alias = $shortName;

			/** @noinspection PhpParamsInspection */
			if (count($pieces) - 1 < $count && !in_array('Php', $pieces, true)) {
				$pieces[] = 'Php';
			}
			if (count($pieces) - 1 < $count) {
				return null;
			}

			$prefix = '';
			/** @noinspection ForeachInvariantsInspection */
			for ($i = 0; $i <= $count; ++$i) {
				$prefix .= $pieces[$i];
			}

			$alias = $prefix . $alias;

			$count++;
		}

		return $alias;
	}

	/**
	 * @param array $useStatement
	 * @return void
	 */
	protected function insertUseStatement(array $useStatement) {
		$this->useStatements[] = $useStatement;
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $nextIndex
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForImplements(File $phpcsFile, $nextIndex) {
		$endIndex = $phpcsFile->findNext([T_OPEN_CURLY_BRACKET], $nextIndex + 1);

		$startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
		$endIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $endIndex - 1, null, true);

		$extractedUseStatements = $this->extractUseStatements($phpcsFile, $startIndex, $endIndex);
		foreach ($extractedUseStatements as $extractedUseStatement) {
			if (strpos($extractedUseStatement['statement'], '\\') === false) {
				continue;
			}

			$className = $this->extractClassNameFromUseStatementAsString($extractedUseStatement['statement']);
			$error = 'Use statement ' . $extractedUseStatement['statement'] . ' for ' . $className .
				' should be in use block.';
			$fix = $phpcsFile->addFixableError($error, $extractedUseStatement['start'], 'Implements');
			if (!$fix) {
				continue;
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$phpcsFile->fixer->beginChangeset();
			$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement['statement']);
			//$lastSeparatorIndex = $phpcsFile->findPrevious(T_NS_SEPARATOR, $extractedUseStatement['end'] - 1, $extractedUseStatement['start']);

			for ($k = $extractedUseStatement['start']; $k < $extractedUseStatement['end']; ++$k) {
				$phpcsFile->fixer->replaceToken($k, '');
			}

			if ($addedUseStatement['alias'] !== null) {
				$phpcsFile->fixer->replaceToken($extractedUseStatement['end'], $addedUseStatement['alias']);
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForNew(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
		$lastIndex = null;
		$i = $nextIndex;
		$extractedUseStatement = '';
		$lastSeparatorIndex = null;
		while (true) {
			if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
				break;
			}
			$lastIndex = $i;
			$extractedUseStatement .= $tokens[$i]['content'];

			if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
				$lastSeparatorIndex = $i;
			}
			++$i;
		}

		if ($lastIndex === null || $lastSeparatorIndex === null) {
			return;
		}

		$extractedUseStatement = ltrim($extractedUseStatement, '\\');

		$className = '';
		for ($i = $lastSeparatorIndex + 1; $i <= $lastIndex; ++$i) {
			$className .= $tokens[$i]['content'];
		}

		$error = 'Use statement ' . $extractedUseStatement . ' for ' . $className . ' should be in use block.';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'New');
		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();

		$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);

		for ($i = $nextIndex; $i <= $lastSeparatorIndex; ++$i) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		if ($addedUseStatement['alias'] !== null) {
			$phpcsFile->fixer->replaceToken($lastSeparatorIndex + 1, $addedUseStatement['alias']);
			for ($i = $lastSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}

		if ($nextIndex === $stackPtr + 1) {
			$phpcsFile->fixer->replaceToken($stackPtr, $tokens[$stackPtr]['content'] . ' ');
		}

		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForStatic(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);

		$lastIndex = null;
		$i = $prevIndex;
		$extractedUseStatement = '';
		$firstSeparatorIndex = null;
		while (true) {
			if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
				break;
			}
			$lastIndex = $i;
			$extractedUseStatement = $tokens[$i]['content'] . $extractedUseStatement;

			if ($firstSeparatorIndex === null && $this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
				$firstSeparatorIndex = $i;
			}
			--$i;
		}

		if ($lastIndex === null || $firstSeparatorIndex === null) {
			return;
		}

		$extractedUseStatement = ltrim($extractedUseStatement, '\\');

		$className = '';
		for ($i = $firstSeparatorIndex + 1; $i <= $prevIndex; ++$i) {
			$className .= $tokens[$i]['content'];
		}

		$error = 'Use statement ' . $extractedUseStatement . ' for ' . $className . ' should be in use block.';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'Static');
		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);

		for ($i = $lastIndex; $i <= $firstSeparatorIndex; ++$i) {
			$phpcsFile->fixer->replaceToken($i, '');
		}

		if ($addedUseStatement['alias'] !== null) {
			$phpcsFile->fixer->replaceToken($firstSeparatorIndex + 1, $addedUseStatement['alias']);
			for ($i = $firstSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}

		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 *
	 * @return void
	 */
	protected function checkUseForInstanceOf(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$classNameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);

		$lastIndex = null;
		$i = $classNameIndex;
		$extractedUseStatement = '';
		$lastSeparatorIndex = null;
		while (true) {
			if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
				break;
			}
			$lastIndex = $i;
			$extractedUseStatement .= $tokens[$i]['content'];

			if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
				$lastSeparatorIndex = $i;
			}
			++$i;
		}

		if ($lastIndex === null || $lastSeparatorIndex === null) {
			return;
		}

		$extractedUseStatement = ltrim($extractedUseStatement, '\\');

		$className = '';
		for ($i = $lastSeparatorIndex + 1; $i <= $lastIndex; ++$i) {
			$className .= $tokens[$i]['content'];
		}

		$error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'InstanceOf');
		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();

		$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);
		$firstSeparatorIndex = $classNameIndex;

		for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
			$phpcsFile->fixer->replaceToken($k, '');
		}
		$phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

		if ($addedUseStatement['alias'] !== null) {
			$phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
			for ($k = $lastSeparatorIndex + 1; $k < $lastIndex; ++$k) {
				$phpcsFile->fixer->replaceToken($k, '');
			}
		}

		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 *
	 * @return void
	 */
	public function checkUseForCatchOrCallable(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
		$closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];
		$classNameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $openParenthesisIndex + 1, null, true);

		$lastIndex = null;
		$i = $classNameIndex;
		$extractedUseStatement = '';
		$lastSeparatorIndex = null;
		while ($i < $closeParenthesisIndex) {
			if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
				break;
			}
			$lastIndex = $i;
			$extractedUseStatement .= $tokens[$i]['content'];

			if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
				$lastSeparatorIndex = $i;
			}
			++$i;
		}

		if ($lastIndex === null || $lastSeparatorIndex === null) {
			return;
		}

		$extractedUseStatement = ltrim($extractedUseStatement, '\\');

		$className = '';
		for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
			$className .= $tokens[$k]['content'];
		}

		$error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
		$fix = $phpcsFile->addFixableError($error, $stackPtr, 'Catch');
		if (!$fix) {
			return;
		}

		$startIndex = $openParenthesisIndex;

		$phpcsFile->fixer->beginChangeset();

		$firstSeparatorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, null, true);

		$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);

		for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
			$phpcsFile->fixer->replaceToken($k, '');
		}
		$phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

		if ($addedUseStatement['alias'] !== null) {
			$phpcsFile->fixer->replaceToken($firstSeparatorIndex + 1, $addedUseStatement['alias']);
			for ($i = $firstSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
				$phpcsFile->fixer->replaceToken($i, '');
			}
		}

		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 *
	 * @return void
	 */
	protected function checkUseForReturnTypeHint(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
		$closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

		$colonIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenthesisIndex + 1, null, true);
		if (!$colonIndex) {
			return;
		}

		$startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $colonIndex + 1, $colonIndex + 3, true);
		if (!$startIndex) {
			return;
		}

		if ($tokens[$startIndex]['type'] === 'T_NULLABLE') {
			$startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, $startIndex + 3, true);
		}

		$lastIndex = null;
		$j = $startIndex;
		$extractedUseStatement = '';
		$lastSeparatorIndex = null;
		while (true) {
			if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING, T_RETURN_TYPE], $tokens[$j])) {
				break;
			}

			$lastIndex = $j;
			$extractedUseStatement .= $tokens[$j]['content'];
			if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$j])) {
				$lastSeparatorIndex = $j;
			}
			++$j;
		}

		if ($lastIndex === null || $lastSeparatorIndex === null) {
			return;
		}

		$extractedUseStatement = ltrim($extractedUseStatement, '\\');
		$className = '';
		for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
			$className .= $tokens[$k]['content'];
		}

		$error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
		$fix = $phpcsFile->addFixableError($error, $colonIndex, 'ReturnSignature');
		if (!$fix) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();

		$firstSeparatorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex, null, true);

		$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);

		for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
			$phpcsFile->fixer->replaceToken($k, '');
		}
		$phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

		if ($addedUseStatement['alias'] !== null) {
			$phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
		}

		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function checkUseForSignature(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
		if (empty($tokens[$openParenthesisIndex]['parenthesis_closer'])) {
			return;
		}
		$closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

		for ($i = $openParenthesisIndex + 1; $i < $closeParenthesisIndex; $i++) {
			$lastIndex = null;
			$j = $i;
			$extractedUseStatement = '';
			$lastSeparatorIndex = null;
			$start = null;
			while (true) {
				if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$j])) {
					break;
				}

				$lastIndex = $j;
				$extractedUseStatement .= $tokens[$j]['content'];
				if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$j])) {
					$lastSeparatorIndex = $j;
					if ($start === null) {
						$start = $lastSeparatorIndex;
					}
				}
				++$j;
			}
			$i = $j;

			if ($lastIndex === null || $lastSeparatorIndex === null) {
				continue;
			}

			$extractedUseStatement = ltrim($extractedUseStatement, '\\');
			$className = '';
			for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
				$className .= $tokens[$k]['content'];
			}

			$error = 'Use statement ' . $extractedUseStatement . ' for ' . $className . ' should be in use block.';
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'Signature');
			if (!$fix) {
				return;
			}

			$phpcsFile->fixer->beginChangeset();
			$addedUseStatement = $this->addUseStatement($className, $extractedUseStatement);

			for ($k = $start; $k < $lastIndex; ++$k) {
				$phpcsFile->fixer->replaceToken($k, '');
			}

			if ($addedUseStatement['alias'] !== null) {
				$phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
				for ($k = $lastSeparatorIndex + 2; $k <= $lastIndex; ++$k) {
					$phpcsFile->fixer->replaceToken($k, '');
				}
			}

			$phpcsFile->fixer->endChangeset();
		}
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 * @param int $stackPtr
	 * @return void
	 */
	protected function insertUseWhenSentinel(File $phpcsFile, $stackPtr) {
		if (($stackPtr === $this->sentinelPtr) && count($this->useStatements)) {
			$existingStatements = $this->existingStatements;
			$haveExistingStatements = $existingStatements;
			if ($existingStatements) {
				$lastOne = array_pop($existingStatements);

				$lastUseStatementIndex = $lastOne['end'];
			} else {
				$namespaceStatement = $this->getNamespaceInfo($phpcsFile);

				$lastUseStatementIndex = $namespaceStatement['end'];
			}

			$phpcsFile->fixer->beginChangeset();
			if (!$haveExistingStatements) {
				// Should be blank line between namespace statement and block of use statements
				$phpcsFile->fixer->addNewline($lastUseStatementIndex);
			}
			foreach ($this->useStatements as $useStatement) {
				/** @noinspection DisconnectedForeachInstructionInspection */
				$phpcsFile->fixer->addNewline($lastUseStatementIndex);
				$phpcsFile->fixer->addContent($lastUseStatementIndex, $this->generateUseStatement($useStatement));
			}
			$phpcsFile->fixer->endChangeset();
			$this->useStatements = [];
		}
	}

	/**
	 * @param array $useStatement
	 *
	 * @return string
	 */
	protected function generateUseStatement(array $useStatement) {
		$alias = '';
		if (!empty($useStatement['alias'])) {
			$alias = ' as ' . $useStatement['alias'];
		}

		return 'use ' . $useStatement['fullName'] . $alias . ';';
	}

	/**
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile
	 *
	 * @return bool
	 */
	protected function isBlacklistedFile(File $phpcsFile) {
		$file = $phpcsFile->getFilename();
		return strpos($file, DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR) !== false;
	}

}
