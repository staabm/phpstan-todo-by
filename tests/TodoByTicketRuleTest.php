<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByTicketRule;
use staabm\PHPStanTodoBy\Tests\StaticTicketStatusFetcher;

/**
 * @extends RuleTestCase<TodoByTicketRule>
 */
final class TodoByTicketRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $fetcher = new StaticTicketStatusFetcher([
            'APP-123' => 'Done',
            'APP-444' => 'To Do',
        ]);

        return new TodoByTicketRule(true, ['Done'], $fetcher);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/example.php'], [
            ['Resolved in APP-123: fix it.', 63]
        ]);
    }
}
