<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByIssueUrlRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\GitHubTicketStatusFetcher;

/**
 * @extends RuleTestCase<TodoByIssueUrlRule>
 * @internal
 */
final class TodoByIssueUrlRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TodoByIssueUrlRule(
            new ExpiredCommentErrorBuilder(true),
            self::getContainer()->getByType(GitHubTicketStatusFetcher::class)
        );
    }

    /**
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     * @dataProvider provideErrors
     */
    public function testRule(array $errors): void
    {
        $this->analyse([__DIR__ . '/data/issue-urls.php'], $errors);
    }

    /**
     * @return iterable<array{list<array{0: string, 1: int, 2?: string|null}>}>
     */
    public static function provideErrors(): iterable
    {
        yield [
            [
                [
                    'Should have been resolved in https://github.com/staabm/phpstan-todo-by/issues/47: we need todo something when this issue is resolved.',
                    5,
                ],
                [
                    'Comment should have been resolved with https://github.com/staabm/phpstan-todo-by/pull/155.',
                    6,
                ],
            ],
        ];
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../extension.neon',
        ];
    }
}
