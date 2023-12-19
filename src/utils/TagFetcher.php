<?php

namespace staabm\PHPStanTodoBy\utils;

interface TagFetcher
{
    public function fetchLatestTagVersion(?string $workingDirectory): string;
}
