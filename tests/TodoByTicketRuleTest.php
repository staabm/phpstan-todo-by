<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByTicketCollector;
use staabm\PHPStanTodoBy\TodoByTicketRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\TicketRuleConfiguration;

/**
 * @extends RuleTestCase<TodoByTicketRule>
 * @internal
 */
final class TodoByTicketRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TodoByTicketRule(
            $this->getTicketConfiguration(),
            new ExpiredCommentErrorBuilder(true),
        );
    }

    protected function getCollectors(): array
    {
        return [
            new TodoByTicketCollector($this->getTicketConfiguration()),
        ];
    }

    private function getTicketConfiguration(): TicketRuleConfiguration
    {
        $fetcher = new StaticTicketStatusFetcher([
            'APP-123' => 'Done',
            'FOO-0001' => 'Done',
            'F01-12345' => 'Done',
            'APP-4444' => 'Resolved',
            'APP-5000' => 'To Do',
        ]);

        return new TicketRuleConfiguration(
            '[A-Z0-9]+-\d+',
            ['Done', 'Resolved'],
            ['APP', 'FOO', 'F01'],
            $fetcher,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/ticket.php'], [
            [
                'Should have been resolved in APP-123: rename this to doBar().',
                5,
                'See https://issue-tracker.com/APP-123',
            ],
            [
                'Comment should have been resolved in APP-4444.',
                11,
                'See https://issue-tracker.com/APP-4444',
            ],
            [
                'Comment should have been resolved in FOO-0001.',
                12,
                'See https://issue-tracker.com/FOO-0001',
            ],
            [
                'Should have been resolved in F01-12345: please change me.',
                13,
                'See https://issue-tracker.com/F01-12345',
            ],
            [
                'Should have been resolved in F01-12345: please change me.',
                14,
                'See https://issue-tracker.com/F01-12345',
            ],
            [
                'Should have been resolved in F01-12345: please change me.',
                15,
                'See https://issue-tracker.com/F01-12345',
            ],
            [
                'Should have been resolved in F01-12345: please change me.',
                16,
                'See https://issue-tracker.com/F01-12345',
            ],
            [
                'Should have been resolved in F01-12345: please change me.',
                17,
                'See https://issue-tracker.com/F01-12345',
            ],
        ]);
    }

    public function testTicketNotFound(): void
    {
        $this->analyse([__DIR__ . '/data/ticket-not-found.php'], [
            ["Ticket APP-000 doesn't exist or provided credentials do not allow for viewing it.", 5],
        ]);
    }

    public function testBug64(): void
    {
        $this->analyse([__DIR__ . '/data/bug64.php'], [
            [
                'Should have been resolved in F01-12345: please change me.',
                9,
                'See https://issue-tracker.com/F01-12345',
            ],
            [
                'Should have been resolved in F01-12345: please * change me.',
                13,
                'See https://issue-tracker.com/F01-12345',
            ],
        ]);
    }
}
