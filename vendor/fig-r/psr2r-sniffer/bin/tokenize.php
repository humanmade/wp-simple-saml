#!/usr/bin/php -q
<?php

$options = [
	__DIR__ . '/../vendor/autoload.php',
	__DIR__ . '/vendor/autoload.php'
];
if (!empty($_SERVER['PWD'])) {
	array_unshift($options, $_SERVER['PWD'] . '/vendor/autoload.php');
}

foreach ($options as $file) {
	if (file_exists($file)) {
		define('SNIFFER_COMPOSER_INSTALL', $file);
		break;
	}
}
require SNIFFER_COMPOSER_INSTALL;

$tokenizer = new \PSR2R\Tools\Tokenizer($argv);

$tokenizer->tokenize();
echo 0;
