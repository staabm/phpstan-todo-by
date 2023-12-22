<?php

namespace staabm\PHPStanTodoBy\Tests;

use Exception;
use staabm\PHPStanTodoBy\utils\TagFetcher;

final class AlwaysThrowingTagFetcher implements TagFetcher
{
    private Exception $exception;

    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }

    public function fetchLatestTagVersion(?string $workingDirectory): string
    {
        throw $this->exception;
    }
}
