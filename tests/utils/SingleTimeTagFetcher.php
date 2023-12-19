<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\TagFetcher;

final class SingleTimeTagFetcher implements TagFetcher
{
    private static int $counter = 0;

    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        if (self::$counter === 0) {
            self::$counter++;

            return '1.2.3';
        }

        throw new \RuntimeException('Tag fetched more than once');
    }
}
