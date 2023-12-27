<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;

use function in_array;
use function strlen;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByTicketRule implements Rule
{
    private const PATTERN = <<<'REGEXP'
        {
            @?TODO # possible @ prefix
            @?[a-zA-Z0-9_-]* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/ticket separator
            (?P<ticketKey>[A-Z0-9]+-\d+) # ticket key consisting of ABC-123 or F01-12345 format
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>.*) # rest of line as comment text
        }ix
        REGEXP;

    /** @var list<non-empty-string> */
    private array $resolvedStatuses;
    /** @var list<non-empty-string> */
    private array $keyPrefixes;
    private TicketStatusFetcher $fetcher;
    private ExpiredCommentErrorBuilder $errorBuilder;

    /**
     * @param list<non-empty-string> $resolvedStatuses
     * @param list<non-empty-string> $keyPrefixes
     */
    public function __construct(array $resolvedStatuses, array $keyPrefixes, TicketStatusFetcher $fetcher, ExpiredCommentErrorBuilder $errorBuilder)
    {
        $this->resolvedStatuses = $resolvedStatuses;
        $this->keyPrefixes = $keyPrefixes;
        $this->fetcher = $fetcher;
        $this->errorBuilder = $errorBuilder;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $it = CommentMatcher::matchComments($node, self::PATTERN);

        $errors = [];
        foreach ($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $ticketKey = $match['ticketKey'][0];
                $todoText = trim($match['comment'][0]);

                if (!$this->hasPrefix($ticketKey)) {
                    continue;
                }

                $ticketStatus = $this->fetcher->fetchTicketStatus($ticketKey);

                if (null === $ticketStatus || !in_array($ticketStatus, $this->resolvedStatuses, true)) {
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

    private function hasPrefix(string $ticketKey): bool
    {
        foreach ($this->keyPrefixes as $prefix) {
            if (substr($ticketKey, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }

        return false;
    }
}
