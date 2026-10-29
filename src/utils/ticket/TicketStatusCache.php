<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use function array_key_exists;

/**
 * Fetches the status of each ticket only once per process.
 * The rule and TicketStatusValueExtension both ask for the statuses, so they share the request.
 *
 * @internal
 */
final class TicketStatusCache
{
    private TicketRuleConfiguration $configuration;

    /** @var array<non-empty-string, null|string> */
    private array $statuses = [];

    public function __construct(TicketRuleConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param non-empty-list<non-empty-string> $ticketKeys
     *
     * @return array<non-empty-string, null|string>
     */
    public function fetchTicketStatus(array $ticketKeys): array
    {
        $missingKeys = [];
        foreach ($ticketKeys as $ticketKey) {
            if (!array_key_exists($ticketKey, $this->statuses)) {
                $missingKeys[] = $ticketKey;
            }
        }

        if ([] !== $missingKeys) {
            $this->statuses = $this->configuration->getFetcher()->fetchTicketStatus($missingKeys) + $this->statuses;
        }

        $result = [];
        foreach ($ticketKeys as $ticketKey) {
            if (array_key_exists($ticketKey, $this->statuses)) {
                $result[$ticketKey] = $this->statuses[$ticketKey];
            }
        }

        return $result;
    }
}
