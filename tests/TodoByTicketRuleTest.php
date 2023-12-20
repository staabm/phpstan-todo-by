<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByTicketRule;
use staabm\PHPStanTodoBy\Tests\StaticTicketStatusFetcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

/**
 * @extends RuleTestCase<TodoByTicketRule>
 */
final class TodoByTicketRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $fetcher = new StaticTicketStatusFetcher([
            'APP-123' => 'Done',
            'APP-4444' => 'Resolved',
            'APP-5000' => 'To Do',
        ]);

        return new TodoByTicketRule(
            ['Done', 'Resolved'],
            'APP',
            $fetcher,
            new ExpiredCommentErrorBuilder(true),
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/example.php'], [
            ['Should have been resolved in APP-123: fix it.', 63],
            ['Comment should have been resolved in APP-4444.', 64],
        ]);
    }
}
