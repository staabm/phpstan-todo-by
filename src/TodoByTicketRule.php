<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use RuntimeException;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\TicketRuleConfiguration;
use staabm\PHPStanTodoBy\utils\ticket\TicketStatusCache;
use staabm\PHPStanTodoBy\utils\ticket\TicketStatusValueExtension;

use function array_key_exists;
use function implode;
use function in_array;
use function ksort;
use function strlen;

/**
 * @implements Rule<CollectedDataNode>
 */
final class TodoByTicketRule implements Rule
{
    public const ERROR_IDENTIFIER = 'ticket';

    private TicketRuleConfiguration $configuration;
    private TicketStatusCache $ticketStatusCache;
    private ExpiredCommentErrorBuilder $errorBuilder;
    private bool $requireUsername;

    public function __construct(
        TicketRuleConfiguration $configuration,
        TicketStatusCache $ticketStatusCache,
        ExpiredCommentErrorBuilder $errorBuilder,
        bool $requireUsername = false
    ) {
        $this->configuration = $configuration;
        $this->ticketStatusCache = $ticketStatusCache;
        $this->errorBuilder = $errorBuilder;
        $this->requireUsername = $requireUsername;
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $collectorData = $node->get(TodoByTicketCollector::class);

        $ticketKeys = [];
        foreach ($collectorData as $collected) {
            foreach ($collected as $tickets) {
                foreach ($tickets as [,,$ticketKey]) {
                    if ([] !== $this->configuration->getKeyPrefixes() && !$this->hasPrefix($ticketKey)) {
                        continue;
                    }
                    if ('' === $ticketKey) {
                        continue;
                    }
                    // de-duplicate keys
                    $ticketKeys[$ticketKey] = true;
                }
            }
        }

        if ([] === $ticketKeys) {
            return [];
        }
        ksort($ticketKeys);

        // the result cache reuses the errors only while the ticket statuses stay the same.
        // the scope is a DependencyTracker since PHPStan 2.3.0
        if ($scope instanceof DependencyTracker) { // @phpstan-ignore instanceof.alwaysTrue
            $scope->trackValueDependency(TicketStatusValueExtension::class, implode(',', array_keys($ticketKeys)));
        }

        $keyToTicketStatus = $this->ticketStatusCache->fetchTicketStatus(array_keys($ticketKeys));

        $errors = [];
        foreach ($collectorData as $file => $collected) {
            foreach ($collected as $tickets) {
                foreach ($tickets as [$comment, $startLine, $ticketKey, $todoText, $username, $wholeMatchStartOffset, $line]) {
                    if ([] !== $this->configuration->getKeyPrefixes() && !$this->hasPrefix($ticketKey)) {
                        continue;
                    }

                    if (!array_key_exists($ticketKey, $keyToTicketStatus)) {
                        throw new RuntimeException("Missing ticket-status for key $ticketKey");
                    }
                    $ticketStatus = $keyToTicketStatus[$ticketKey];

                    if (null === $ticketStatus) {
                        $errors[] = $this->errorBuilder->buildFileError(
                            $comment,
                            $startLine,
                            "Ticket $ticketKey doesn't exist or provided credentials do not allow for viewing it.",
                            self::ERROR_IDENTIFIER,
                            null,
                            $wholeMatchStartOffset,
                            $file,
                            $line,
                            $username
                        );

                        continue;
                    }

                    if (!in_array($ticketStatus, $this->configuration->getResolvedStatuses(), true)) {
                        // not yet resolved, but still require attribution when configured
                        if ($this->requireUsername && '' === $username) {
                            $errors[] = $this->errorBuilder->buildMissingUsernameError(
                                $comment,
                                $startLine,
                                $wholeMatchStartOffset,
                                $file,
                                $line
                            );
                        }

                        continue;
                    }

                    if ('' !== $todoText) {
                        $errorMessage = "Should have been resolved in {$ticketKey}: ". rtrim($todoText, '.') .'.';
                    } else {
                        $errorMessage = "Comment should have been resolved in {$ticketKey}.";
                    }

                    $errors[] = $this->errorBuilder->buildFileError(
                        $comment,
                        $startLine,
                        $errorMessage,
                        self::ERROR_IDENTIFIER,
                        "See {$this->configuration->getFetcher()->resolveTicketUrl($ticketKey)}",
                        $wholeMatchStartOffset,
                        $file,
                        $line,
                        $username
                    );
                }
            }
        }

        return $errors;
    }

    private function hasPrefix(string $ticketKey): bool
    {
        foreach ($this->configuration->getKeyPrefixes() as $prefix) {
            if (substr($ticketKey, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }

        return false;
    }
}
