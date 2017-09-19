#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);

function fixCode()
{
    global $root;
    if (file_exists("$root/vendor/bin/php-cs-fixer")) {
        `php $root/vendor/bin/php-cs-fixer fix`;
    } else {
        `php-cs-fixer fix -vvv`;
    }
}

function checkSyntax()
{
    global $root;
    /** @var \PhpCsFixer\Config $config */
    $config = require $root . '/.php_cs.dist';
    $files = $config->getFinder();

    echo "Check php syntax!\n";
    $index = 0;
    foreach ($files as $f) {
        $str = `php -l {$f->getPathname()}`;

        if (strpos($str, 'No syntax errors detected') === false) {
            echo "【", $f->getPathname(), '】has syntax errors detected', PHP_EOL;
            die();
        }

        $index++;
        echo "Check: ", $index, "\r";
    }
}

$help = <<<EOF
usage: php format.php [--all|--check|--fix]
argv:
    --all:      check & fix
    --check:    check syntax
    --fix:      fix use psr2

EOF;

$cmd = isset($argv[1]) ? $argv[1] : 1;

switch ($cmd) {
    case '--all':
        checkSyntax();
        fixCode();
        checkSyntax();
        break;
    case '--check':
        checkSyntax();
        break;
    case '--fix':
        fixCode();
        checkSyntax();
        break;
    default:
        echo $help;
}

