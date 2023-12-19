<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\TagFetcher;

final class AlwaysThrowingTagFetcher implements TagFetcher
{
    public function fetchLatestTagVersion(): string
    {
        throw new \RuntimeException('Could not determine latest git tag');
    }
}
