<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->in([
        'src',
        'tests'
    ])
    ->exclude([
        'data'
    ]);

return (new Redaxo\PhpCsFixerConfig\Config())
    ->setFinder($finder)
    ;
