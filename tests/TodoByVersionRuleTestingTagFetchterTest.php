<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 * @internal
 */
final class TodoByVersionRuleTestingTagFetchterTest extends RuleTestCase
{
    private string $referenceVersion;

    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            true,
            new ReferenceVersionFinder($this->referenceVersion, new StaticTagFetcher('testing')),
            new ExpiredCommentErrorBuilder(true)
        );
    }

    public function testNoTagIsFetched(): void
    {
        $this->referenceVersion = 'nextMajor';

        $this->analyse([__DIR__ . '/data/tagNotFound.php'], [
            [
                "Could not parse version from tag 'testing'",
                5,
            ],
        ]);
    }
}
