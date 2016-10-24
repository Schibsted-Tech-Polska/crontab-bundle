<?php

use Symfony\CS\Finder\DefaultFinder as Finder;
use Symfony\CS\Config\Config;

$finder = Finder::create()
    ->in([
        __DIR__ . DIRECTORY_SEPARATOR . 'src',
    ])
    ->exclude([
        'cache',
        'logs',
        'sessions',
        'uploads',
    ])
    ->files()
    ->name('*.php')
    ->name('*.twig')
    ->name('*.xml')
    ->name('*.yml')
    ->notName('AppCache.php')
    ->notName('AppKernel.php')
    ->notName('autoload.php')
    ->notName('check.php')
    ->notName('SymfonyRequirements.php')
    ->ignoreUnreadableDirs(true)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
;

return Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-empty_return',
        '-extra_empty_lines',
        '-phpdoc_short_description',
        '-phpdoc_to_comment',
        '-phpdoc_var_without_name',
        '-pre_increment',
        'concat_with_spaces',
        'ordered_use',
        'short_array_syntax',
    ])
    ->setUsingCache(false)
    ->finder($finder);
