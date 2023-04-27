<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['bootstrap', 'storage', 'vendor', 'config', 'database/squashed-db', 'tests/_support/_generated'])
    ->name('*.php')
    ->notName('_ide_helper.php')
    ->notName('*.blade.php')
    ->notName('c3.php')
    ->notName('phpunit.xml')
    ->ignoreUnreadableDirs()
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

$config->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP80Migration' => true,
        '@Symfony' => true,
        'yoda_style' => false,
        'phpdoc_var_without_name' => false,
        'no_unused_imports' => true,
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/cache/.php_cs.cache');

return $config;
