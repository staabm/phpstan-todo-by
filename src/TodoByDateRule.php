<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function preg_match_all;
use function strtotime;
use function substr_count;
use function time;
use function trim;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

/**
 * @implements Rule<Node>
 */
final class TodoByDateRule implements Rule
{
    private const PATTERN = <<<'REGEXP'
/
@?TODO # possible @ prefix
@?[a-zA-Z0-9_-]*\s* # optional username
\s*[:-]?\s* # optional colon or hyphen
(?P<date>\d{4}-\d{2}-\d{2}) # date consisting of YYYY-MM-DD format
\s*[:-]?\s* # optional colon or hyphen
(?P<comment>.*) # rest of line as comment text
/ix
REGEXP;

    private int $now;
    private bool $nonIgnorable;

    public function __construct(bool $nonIgnorable, string $referenceTime)
    {
        $time =  strtotime($referenceTime);

        if ($time === false) {
            throw new \RuntimeException('Unable to parse reference time "' . $referenceTime . '"');
        }

        $this->now = $time;
        $this->nonIgnorable = $nonIgnorable;
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
                preg_match_all(self::PATTERN, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === FALSE
                || count($matches) === 0
            ) {
                continue;
            }

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
                if ($todoText !== '') {
                    $errorMessage = "Expired on {$date}: ". rtrim($todoText, '.') .".";
                } else {
                    $errorMessage = "Comment expired on {$date}.";
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
