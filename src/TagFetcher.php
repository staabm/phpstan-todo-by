<?php

namespace staabm\PHPStanTodoBy;

interface TagFetcher
{
    public function fetchLatestTagVersion(): string;
}
