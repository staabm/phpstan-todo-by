<?php

namespace staabm\PHPStanTodoBy\utils;

interface TicketStatusFetcher
{
    /** @return string|null Status name or null if ticket doesn't exist */
    public function fetchTicketStatus(string $ticketKey): ?string;
}
