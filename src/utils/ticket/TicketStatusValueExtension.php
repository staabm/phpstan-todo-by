<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use Throwable;

use function bin2hex;
use function explode;
use function json_encode;
use function random_bytes;
use function sha1;

/**
 * Lets the result cache reuse the errors of TodoByTicketRule only while the ticket statuses stay the same.
 * The key is the comma-separated list of ticket keys the rule asked for.
 *
 * @internal
 */
final class TicketStatusValueExtension implements ResultCacheValueExtension
{
    private TicketStatusCache $ticketStatusCache;

    public function __construct(TicketStatusCache $ticketStatusCache)
    {
        $this->ticketStatusCache = $ticketStatusCache;
    }

    public function getValue(string $key): string
    {
        /** @var non-empty-list<non-empty-string> $ticketKeys */
        $ticketKeys = explode(',', $key);

        try {
            $statuses = $this->ticketStatusCache->fetchTicketStatus($ticketKeys);
        } catch (Throwable $e) {
            // a value that never matches, so the rule runs and reports the error
            return 'failed:' . bin2hex(random_bytes(8));
        }

        return sha1((string) json_encode($statuses));
    }

    public function keyToResultCache(string $key): string
    {
        return $key;
    }

    public function keyFromResultCache(string $storedKey): string
    {
        return $storedKey;
    }
}
