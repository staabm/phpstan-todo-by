<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use RuntimeException;
use staabm\PHPStanTodoBy\utils\CommentMatcher;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

use function strtotime;
use function trim;

/**
 * @implements Rule<Node>
 */
final class TodoByDateRule implements Rule
{
    private const PATTERN = <<<'REGEXP'
        {
            @?TODO # possible @ prefix
            @?[a-zA-Z0-9_-]*\s* # optional username
            \s*[:-]?\s* # optional colon or hyphen
            \s+ # keyword/date separator
            (?P<date>\d{4}-\d{2}-\d{2}) # date consisting of YYYY-MM-DD format
            \s*[:-]?\s* # optional colon or hyphen
            (?P<comment>.*) # rest of line as comment text
        }ix
        REGEXP;

    private int $now;
    private ExpiredCommentErrorBuilder $errorBuilder;

    public function __construct(
        string $referenceTime,
        ExpiredCommentErrorBuilder $errorBuilder
    ) {
        $time = strtotime($referenceTime);

        if (false === $time) {
            throw new RuntimeException('Unable to parse reference time "' . $referenceTime . '"');
        }

        $this->now = $time;
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
                $date = $match['date'][0];
                $todoText = trim($match['comment'][0]);

                /**
                 * strtotime() will parse date-only values with time set to 00:00:00.
                 * This is fine, because this will count any expiration matching
                 * the current date as expired, except when ran exactly at 00:00:00.
                 */
                if (strtotime($date) > $this->now) {
                    continue;
                }

                // Have always present date at the start of the message.
                // If there is further text, append it.
                if ('' !== $todoText) {
                    $errorMessage = "Expired on {$date}: ". rtrim($todoText, '.') .'.';
                } else {
                    $errorMessage = "Comment expired on {$date}.";
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
