<?php

namespace staabm\PHPStanTodoBy\Tests;

use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;

final class StaticTicketStatusFetcher implements TicketStatusFetcher
{
    /** @var array<string, string> */
    private array $statuses;

    /** @param array<string, string> $statuses */
    public function __construct(array $statuses)
    {
        $this->statuses = $statuses;
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        if (!array_key_exists($ticketKey, $this->statuses)) {
            return null;
        }

        return $this->statuses[$ticketKey];
    }
}
