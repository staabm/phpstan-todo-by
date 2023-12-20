<?php

namespace staabm\PHPStanTodoBy\Tests;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\TagFetcher;

final class SingleTimeTagFetcher implements TagFetcher
{
    private static int $counter = 0;

    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        if (0 === self::$counter) {
            ++self::$counter;

            return '1.2.3';
        }

        throw new RuntimeException('Tag fetched more than once');
    }
}
