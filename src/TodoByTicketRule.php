<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByTicketRule implements Rule
{
    private const PATTERN = <<<'REGEXP'
/
@?TODO # possible @ prefix
@?[a-zA-Z0-9_-]*\s* # optional username
\s*[:-]?\s* # optional colon or hyphen
(?P<ticket>[A-Z]+-\d+) # ticket id consisting of ABC-123 format
\s*[:-]?\s* # optional colon or hyphen
(?P<comment>.*) # rest of line as comment text
/ix
REGEXP;

    /** @var list<non-empty-string> */
    private array $resolvedStatuses;
    private string $keyPrefix;
    private TicketStatusFetcher $fetcher;
    private ExpiredCommentErrorBuilder $errorBuilder;

    /** @param list<non-empty-string> $resolvedStatuses */
    public function __construct(array $resolvedStatuses, string $keyPrefix, TicketStatusFetcher $fetcher, ExpiredCommentErrorBuilder $errorBuilder)
    {
        $this->resolvedStatuses = $resolvedStatuses;
        $this->keyPrefix = $keyPrefix;
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
        foreach($it as $comment => $matches) {
            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {
                $ticketKey = $match['ticket'][0];
                $todoText = trim($match['comment'][0]);

                if (strpos($ticketKey, $this->keyPrefix) === false) {
                    continue;
                }

                $ticketStatus = $this->fetcher->fetchTicketStatus($ticketKey);

                if ($ticketStatus === null || !in_array($ticketStatus, $this->resolvedStatuses, true)) {
                    continue;
                }

                if ($todoText !== '') {
                    $errorMessage = "Should have been resolved in {$ticketKey}: ". rtrim($todoText, '.') .".";
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
}
