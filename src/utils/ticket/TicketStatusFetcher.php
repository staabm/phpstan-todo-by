<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

/** @internal */
interface TicketStatusFetcher
{
    /** @return string|null Status name or null if ticket doesn't exist */
    public function fetchTicketStatus(string $ticketKey): ?string;

    /** @return non-empty-string */
    public static function getKeyPattern(): string;
}
