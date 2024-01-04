<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

final class TicketRuleConfiguration
{
    /** @var non-empty-string */
    private string $keyPattern;
    /** @var list<string> */
    private array $resolvedStatuses;
    /** @var list<string> */
    private array $keyPrefixes;
    private TicketStatusFetcher $fetcher;

    /**
     * @param non-empty-string $keyPattern
     * @param list<string> $resolvedStatuses
     * @param list<string> $keyPrefixes
     */
    public function __construct(string $keyPattern, array $resolvedStatuses, array $keyPrefixes, TicketStatusFetcher $fetcher)
    {
        $this->keyPattern = $keyPattern;
        $this->resolvedStatuses = $resolvedStatuses;
        $this->keyPrefixes = $keyPrefixes;
        $this->fetcher = $fetcher;
    }

    /** @return non-empty-string */
    public function getKeyPattern(): string
    {
        return $this->keyPattern;
    }

    /** @return list<string> */
    public function getResolvedStatuses(): array
    {
        return $this->resolvedStatuses;
    }

    /** @return list<string> */
    public function getKeyPrefixes(): array
    {
        return $this->keyPrefixes;
    }

    public function getFetcher(): TicketStatusFetcher
    {
        return $this->fetcher;
    }
}
