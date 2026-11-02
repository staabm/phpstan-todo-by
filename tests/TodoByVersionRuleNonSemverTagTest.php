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
final class TodoByVersionRuleNonSemverTagTest extends RuleTestCase
{
    private string $tag;

    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            true,
            new ReferenceVersionFinder('nextMajor', new StaticTagFetcher($this->tag)),
            new ExpiredCommentErrorBuilder(true)
        );
    }

    // https://github.com/staabm/phpstan-todo-by/issues/203
    public function testNonSemverTag(): void
    {
        $this->tag = 'testing';

        $this->analyse([__DIR__ . '/data/tagNotFound.php'], [
            [
                "Could not parse version from tag 'testing'",
                5,
            ],
        ]);
    }

    public function testNonSemverTagWithThreeParts(): void
    {
        $this->tag = 'testing.0.0';

        $this->analyse([__DIR__ . '/data/tagNotFound.php'], [
            [
                "Could not parse version from tag 'testing.0.0'",
                5,
            ],
        ]);
    }
}
