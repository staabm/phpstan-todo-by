<?php

namespace staabm\PHPStanTodoBy;

final class GitTagFetcher implements TagFetcher
{
    // fetch version of the latest created git tag
    public function fetchLatestTagVersion(): string
    {
        // requires tags have been fetched into the local clone
        // see https://github.com/staabm/phpstan-todo-by#reference-version
        exec('git for-each-ref --sort=-creatordate --count 1 --format="%(refname:short)" "refs/tags/"', $output, $returnCode);
        
        if ($returnCode !== 0 || count($output) !== 1) {
            throw new \RuntimeException('Could not determine latest git tag');
        }
        return $output[0];
    }
}
