<?php

namespace staabm\PHPStanTodoBy\Tests;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\TagFetcher;

final class AlwaysThrowingTagFetcher implements TagFetcher
{
    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        throw new RuntimeException('This should never happen');
    }
}
