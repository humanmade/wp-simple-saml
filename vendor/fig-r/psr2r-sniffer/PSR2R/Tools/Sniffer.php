<?php

namespace PSR2R\Tools;

use PHP_CodeSniffer_CLI;

class Sniffer {

	const STANDARD = 'PSR2R/ruleset.xml';

	/**
	 * @var bool
	 */
	protected $fix;

	/**
	 * @param array $argv
	 * @throws \Exception
	 */
	public function __construct($argv) {
		$path = (!empty($argv[1]) && strpos($argv[1], '-') !== 0) ? $argv[1] : null;
		$ignore = null;
		if (!$path) {
			$path = array_shift($argv);
			$path = dirname(dirname($path));
			if (substr($path, -13) === 'psr2r-sniffer' && substr($path, -19, -14) === 'fig-r') {
				$path = dirname(dirname(dirname($path)));
			}
			$path .= DIRECTORY_SEPARATOR;
			$ignore = $path . 'vendor' . DIRECTORY_SEPARATOR;
		} else {
			unset($argv[1]);
			array_shift($argv);
		}

		$fix = false;
		foreach ($argv as $k => $v) {
			if ($v === '-f') {
				$fix = true;
				unset($argv[$k]);
				break;
			}
		}

		$root = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
		$standard = $root . static::STANDARD;
		$argv[] = '--standard=' . $standard;
		if ($ignore) {
			$argv[] = '--ignore=' . str_replace(DIRECTORY_SEPARATOR, '/', $ignore);
		}
		if (!$fix) {
			$argv[] = '-p';
		}

		$argv[] = $path;

		array_unshift($argv, 'dummy');

		$_SERVER['argv'] = $argv;
		$_SERVER['argc'] = count($_SERVER['argv']);
		$this->fix = $fix;
	}

	/**
	 * @return void
	 */
	public function sniff() {
		$cli = new PHP_CodeSniffer_CLI();
		if ($this->fix) {
			$cli->runphpcbf();
		} else {
			$cli->runphpcs();
		}
	}

}
