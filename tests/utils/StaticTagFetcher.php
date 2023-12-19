<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\TagFetcher;

final class StaticTagFetcher implements TagFetcher
{
    private string $tag;

    public function __construct(string $tag) {
        $this->tag = $tag;
    }
    public function fetchLatestTagVersion(): string
    {
        return $this->tag;
    }
}
