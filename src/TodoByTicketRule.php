<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ticket\TicketRuleConfiguration;

use function in_array;
use function strlen;
use function trim;

/**
 * @implements Rule<Node>
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
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $it = CommentMatcher::matchComments($node, $this->createPattern());

        $errors = [];
        foreach ($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $ticketKey = $match['ticketKey'][0];
                $todoText = trim($match['comment'][0]);

                if ([] !== $this->configuration->getKeyPrefixes() && !$this->hasPrefix($ticketKey)) {
                    continue;
                }

                $ticketStatus = $this->configuration->getFetcher()->fetchTicketStatus($ticketKey);

                if (null === $ticketStatus) {
                    $errors[] = $this->errorBuilder->buildError(
                        $comment,
                        "Ticket $ticketKey doesn't exist or provided credentials do not allow for viewing it.",
                        null,
                        $match[0][1]
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

                $errors[] = $this->errorBuilder->buildError(
                    $comment,
                    $errorMessage,
                    null,
                    $match[0][1]
                );
            }
        }

        return $errors;
    }

    private function createPattern(): string
    {
        $keyRegex = $this->configuration->getKeyPattern();

        return <<<"REGEXP"
            {
                @?TODO # possible @ prefix
                @?[a-zA-Z0-9_-]* # optional username
                \s*[:-]?\s* # optional colon or hyphen
                \s+ # keyword/ticket separator
                (?P<ticketKey>$keyRegex) # ticket key
                \s*[:-]?\s* # optional colon or hyphen
                (?P<comment>(?:(?!\*+/).)*) # rest of line as comment text, excluding block end
            }ix
            REGEXP;
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
