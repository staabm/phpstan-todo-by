<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use RuntimeException;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\TicketRuleConfiguration;

use function array_key_exists;
use function in_array;
use function strlen;

/**
 * @implements Rule<CollectedDataNode>
 */
final class TodoByTicketRule implements Rule
{
    private TicketRuleConfiguration $configuration;
    private ExpiredCommentErrorBuilder $errorBuilder;

    public function __construct(TicketRuleConfiguration $configuration, ExpiredCommentErrorBuilder $errorBuilder)
    {
        $this->configuration = $configuration;
        $this->errorBuilder = $errorBuilder;
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
                foreach ($tickets as [$json, $ticketKey, $todoText, $wholeMatchStartOffset, $line]) {
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

        $keyToTicketStatus = $this->configuration->getFetcher()->fetchTicketStatus(
            array_keys($ticketKeys)
        );

        $errors = [];
        foreach ($collectorData as $file => $collected) {
            foreach ($collected as $tickets) {
                foreach ($tickets as [$json, $ticketKey, $todoText, $wholeMatchStartOffset, $line]) {
                    if ([] !== $this->configuration->getKeyPrefixes() && !$this->hasPrefix($ticketKey)) {
                        continue;
                    }

                    if (!array_key_exists($ticketKey, $keyToTicketStatus)) {
                        throw new RuntimeException("Missing ticket-status for key $ticketKey");
                    }
                    $ticketStatus = $keyToTicketStatus[$ticketKey];

                    if (null === $ticketStatus) {
                        $errors[] = $this->errorBuilder->buildFileError(
                            $this->commentFromJson($json),
                            "Ticket $ticketKey doesn't exist or provided credentials do not allow for viewing it.",
                            null,
                            $wholeMatchStartOffset,
                            $file,
                            $line
                        );

                        continue;
                    }

                    if (!in_array($ticketStatus, $this->configuration->getResolvedStatuses(), true)) {
                        continue;
                    }

                    if ('' !== $todoText) {
                        $errorMessage = "Should have been resolved in {$ticketKey}: ". rtrim($todoText, '.') .'.';
                    } else {
                        $errorMessage = "Comment should have been resolved in {$ticketKey}.";
                    }

                    $errors[] = $this->errorBuilder->buildFileError(
                        $this->commentFromJson($json),
                        $errorMessage,
                        null,
                        $wholeMatchStartOffset,
                        $file,
                        $line
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

    private function commentFromJson(string $json): Comment
    {
        $arr = json_decode($json, true);

        if ('Comment_Doc' === $arr['nodeType']) {
            return new Comment\Doc(
                $arr['text'],
                $arr['line'],
                $arr['filePos'],
                $arr['tokenPos'],
                $arr['endLine'],
                $arr['endFilePos'],
                $arr['endTokenPos']
            );
        }

        return new Comment(
            $arr['text'],
            $arr['line'],
            $arr['filePos'],
            $arr['tokenPos'],
            $arr['endLine'],
            $arr['endFilePos'],
            $arr['endTokenPos']
        );
    }
}
