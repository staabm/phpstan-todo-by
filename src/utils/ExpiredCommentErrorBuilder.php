<?php

namespace staabm\PHPStanTodoBy\utils;

use PhpParser\Comment;
use PHPStan\Rules\RuleErrorBuilder;

final class ExpiredCommentErrorBuilder
{
    private bool $nonIgnorable;

    public function __construct(bool $nonIgnorable)
    {
        $this->nonIgnorable = $nonIgnorable;
    }

    public function buildError(
        Comment $comment,
        string $errorMessage,
        ?string $tip,
        int $wholeMatchStartOffset
    ): \PHPStan\Rules\RuleError {
        $text = $comment->getText();

        // Count the number of newlines between the start of the whole comment, and the start of the match.
        $newLines = substr_count($text, "\n", 0, $wholeMatchStartOffset);

        // Set the message line to match the line the comment actually starts on.
        $messageLine = $comment->getStartLine() + $newLines;

        $errBuilder = RuleErrorBuilder::message($errorMessage)->line($messageLine);
        if ($this->nonIgnorable) {
            $errBuilder->nonIgnorable();
        }
        if ($tip !== null) {
            $errBuilder->tip($tip);
        }
        return $errBuilder->build();
    }
}
