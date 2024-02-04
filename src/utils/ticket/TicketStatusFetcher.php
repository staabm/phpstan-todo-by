<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

/** @internal */
interface TicketStatusFetcher
{
    /**
     * @param non-empty-list<non-empty-string> $ticketKeys
     *
     * @return array<non-empty-string, null|string> Map using the ticket-key as key and Status name or null if ticket doesn't exist as value
     */
    public function fetchTicketStatus(array $ticketKeys): array;

    /** @return non-empty-string */
    public static function getKeyPattern(): string;

    /**
     * @param non-empty-string $ticketKey
     *
     * @return non-empty-string
     */
    public function resolveTicketUrl(string $ticketKey): string;
}
