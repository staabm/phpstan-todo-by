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

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
    ]
);
