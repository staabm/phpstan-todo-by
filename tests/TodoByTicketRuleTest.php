<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByTicketRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

/**
 * @extends RuleTestCase<TodoByTicketRule>
 * @internal
 */
final class TodoByTicketRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $fetcher = new StaticTicketStatusFetcher([
            'APP-123' => 'Done',
            'FOO-0001' => 'Done',
            'F01-12345' => 'Done',
            'APP-4444' => 'Resolved',
            'APP-5000' => 'To Do',
        ]);

        return new TodoByTicketRule(
            ['Done', 'Resolved'],
            ['APP', 'FOO', 'F01'],
            $fetcher,
            new ExpiredCommentErrorBuilder(true),
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/ticket.php'], [
            ['Should have been resolved in APP-123: rename this to doBar().', 5],
            ['Comment should have been resolved in APP-4444.', 11],
            ['Comment should have been resolved in FOO-0001.', 12],
            ['Should have been resolved in F01-12345: please change me.', 13],
        ]);
    }
}
