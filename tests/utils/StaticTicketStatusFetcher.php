<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\utils\ticket\TicketStatusFetcher;

use function array_key_exists;

final class StaticTicketStatusFetcher implements TicketStatusFetcher
{
    /** @var array<string, string> */
    private array $statuses;

    /** @param array<string, string> $statuses */
    public function __construct(array $statuses)
    {
        $this->statuses = $statuses;
    }

    public function fetchTicketStatus(array $ticketKeys): array
    {
        $result = [];
        foreach ($ticketKeys as $ticketKey) {
            if (!array_key_exists($ticketKey, $this->statuses)) {
                $result[$ticketKey] = null;
                continue;
            }

            $result[$ticketKey] = $this->statuses[$ticketKey];
        }

        return $result;
    }

    public static function getKeyPattern(): string
    {
        return '[A-Z0-9]+-\d+';
    }

    public function resolveTicketUrl(string $ticketKey): string
    {
        return "https://issue-tracker.com/$ticketKey";
    }
}
