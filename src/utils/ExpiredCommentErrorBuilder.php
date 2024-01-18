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
        return $this->build(
            $comment,
            $errorMessage,
            $tip,
            $wholeMatchStartOffset,
            null,
            null
        );
    }

    public function buildFileError(
        Comment $comment,
        string $errorMessage,
        ?string $tip,
        int $wholeMatchStartOffset,
        string $file,
        int $line
    ): \PHPStan\Rules\RuleError {
        return $this->build(
            $comment,
            $errorMessage,
            $tip,
            $wholeMatchStartOffset,
            $file,
            $line
        );
    }

    private function build(
        Comment $comment,
        string $errorMessage,
        ?string $tip,
        int $wholeMatchStartOffset,
        ?string $file,
        ?int $line
    ): \PHPStan\Rules\RuleError {
        // Count the number of newlines between the start of the whole comment, and the start of the match.
        $newLines = substr_count($comment->getText(), "\n", 0, $wholeMatchStartOffset);

        // Set the message line to match the line the comment actually starts on.
        $messageLine = $comment->getStartLine() + $newLines;

        $errBuilder = RuleErrorBuilder::message($errorMessage)->line($messageLine);
        if (null !== $file) {
            $errBuilder->file($file);
        }
        if (null !== $line) {
            $errBuilder->line($line);
        }
        if ($this->nonIgnorable) {
            $errBuilder->nonIgnorable();
        }
        if (null !== $tip) {
            $errBuilder->tip($tip);
        }
        return $errBuilder->build();
    }
}
