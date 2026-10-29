<?php declare(strict_types = 1);

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use staabm\PHPStanTodoBy\utils\ticket\TicketStatusValueExtension;

// ResultCacheValueExtension exists since PHPStan 2.3.0
if (!interface_exists(ResultCacheValueExtension::class)) {
    return [];
}

return [
    'services' => [
        [
            'class' => TicketStatusValueExtension::class,
            'tags' => ['phpstan.resultCacheValueExtension'],
        ],
    ],
];
