<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;
use function preg_match_all;
use function substr_count;
use function trim;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

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

    private bool $nonIgnorable;
    /** @var list<non-empty-string> */
    private array $doneStatuses;
    private TicketStatusFetcher $fetcher;

    /** @param list<non-empty-string> $doneStatuses */
    public function __construct(bool $nonIgnorable, array $doneStatuses, TicketStatusFetcher $fetcher)
    {
        $this->nonIgnorable = $nonIgnorable;
        $this->doneStatuses = $doneStatuses;
        $this->fetcher = $fetcher;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (
            $node instanceof VirtualNode
            || $node instanceof Node\Expr
        ) {
            // prevent duplicate errors
            return [];
        }

        $errors = [];

        foreach ($node->getComments() as $comment) {

            $text = $comment->getText();

            /**
             * PHP doc comments have the entire multi-line comment as the text.
             * Since this could potentially contain multiple "todo" comments, we need to check all lines.
             * This works for single line comments as well.
             *
             * PREG_OFFSET_CAPTURE: Track where each "todo" comment starts within the whole comment text.
             * PREG_SET_ORDER: Make each value of $matches be structured the same as if from preg_match().
             */
            if (
                preg_match_all(self::PATTERN, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === false
                || count($matches) === 0
            ) {
                continue;
            }

            /** @var array<int, array<array{0: string, 1: int}>> $matches */
            foreach ($matches as $match) {

                $ticketKey = $match['ticket'][0];
                $todoText = trim($match['comment'][0]);

                $ticketStatus = $this->fetcher->fetchTicketStatus($ticketKey);

                if ($ticketStatus === null || !in_array($ticketStatus, $this->doneStatuses, true)) {
                    continue;
                }

                // Have always present date at the start of the message.
                // If there is further text, append it.
                if ($todoText !== '') {
                    $errorMessage = "Resolved in {$ticketKey}: ". rtrim($todoText, '.') .".";
                } else {
                    $errorMessage = "Comment resolved in {$ticketKey}.";
                }

                $wholeMatchStartOffset = $match[0][1];

                // Count the number of newlines between the start of the whole comment, and the start of the match.
                $newLines = substr_count($text, "\n", 0, $wholeMatchStartOffset);

                // Set the message line to match the line the comment actually starts on.
                $messageLine = $comment->getStartLine() + $newLines;

                $errBuilder = RuleErrorBuilder::message($errorMessage)->line($messageLine);
                if ($this->nonIgnorable) {
                    $errBuilder->nonIgnorable();
                }
                $errors[] = $errBuilder->build();
            }
        }

        return $errors;
    }
}
