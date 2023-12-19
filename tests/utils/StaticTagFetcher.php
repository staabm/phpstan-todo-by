<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\utils\TagFetcher;

final class StaticTagFetcher implements TagFetcher
{
    private string $tag;

    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }
    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        return $this->tag;
    }
}
