<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\TagFetcher;

final class SingleTimeTagFetcher implements TagFetcher
{
    static private int $counter = 0;

    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        if (self::$counter === 0) {
            self::$counter++;

            return '1.2.3';
        }

        throw new \RuntimeException('Could not determine latest git tag');
    }
}
