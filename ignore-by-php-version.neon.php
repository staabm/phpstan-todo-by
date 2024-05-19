<?php declare(strict_types = 1);

$includes = [];
if (PHP_VERSION_ID < 80000) {
	$includes[] = __DIR__ . '/baseline-7.4.neon';
}

return [
    'includes' => $includes,
    'parameters' => [
        'phpVersion' => PHP_VERSION_ID,
    ],
];
