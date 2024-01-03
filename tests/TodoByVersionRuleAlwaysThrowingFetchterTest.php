<?php

namespace staabm\PHPStanTodoBy\Tests;

use Exception;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use RuntimeException;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\LatestTagNotFoundException;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 * @internal
 */
final class TodoByVersionRuleAlwaysThrowingFetchterTest extends RuleTestCase
{
    private string $referenceVersion;

    private Exception $exception;

    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            true,
            new ReferenceVersionFinder($this->referenceVersion, new AlwaysThrowingTagFetcher($this->exception)),
            new ExpiredCommentErrorBuilder(true)
        );
    }

    // make sure no tag-fetching is done internally when no version related comments are present
    public function testNoTagIsFetched(): void
    {
        $this->referenceVersion = 'nextMajor';
        $this->exception = new RuntimeException('This should never happen');

        $this->analyse([__DIR__ . '/data/regularComments.php'], []);
    }

    public function testLatestTagNotFoundError(): void
    {
        $this->referenceVersion = 'nextMajor';
        $this->exception = new LatestTagNotFoundException('Latest tag not found');

        $this->analyse([__DIR__ . '/data/tagNotFound.php'], [
            [
                'Latest tag not found',
                5,
                'See https://github.com/staabm/phpstan-todo-by#could-not-determine-latest-git-tag-error',
            ],
        ]);
    }

    public function testDateCommentsNotPickedUpByVersionRule(): void
    {
        $this->referenceVersion = 'nextMajor';
        $this->exception = new RuntimeException('This should never happen');

        $this->analyse([__DIR__ . '/data/bug59.php'], []);
    }
}
