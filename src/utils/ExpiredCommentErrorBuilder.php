<?php

namespace staabm\PHPStanTodoBy\utils;

use PhpParser\Comment;
use PHPStan\Rules\RuleErrorBuilder;

final class ExpiredCommentErrorBuilder
{
    public const ERROR_IDENTIFIER_PREFIX = 'todoBy.';

    private bool $nonIgnorable;

    public function __construct(bool $nonIgnorable)
    {
        $this->nonIgnorable = $nonIgnorable;
    }

    public function buildError(
        string $comment,
        int $startLine,
        string $errorMessage,
        string $errorIdentifier,
        ?string $tip,
        int $wholeMatchStartOffset,
        ?string $username = null
    ): \PHPStan\Rules\IdentifierRuleError {
        return $this->build(
            $comment,
            $startLine,
            $errorMessage,
            $errorIdentifier,
            $tip,
            $wholeMatchStartOffset,
            $username,
            null,
            null
        );
    }

    public function buildFileError(
        string $comment,
        int $startLine,
        string $errorMessage,
        string $errorIdentifier,
        ?string $tip,
        int $wholeMatchStartOffset,
        string $file,
        int $line,
        ?string $username = null
    ): \PHPStan\Rules\IdentifierRuleError {
        return $this->build(
            $comment,
            $startLine,
            $errorMessage,
            $errorIdentifier,
            $tip,
            $wholeMatchStartOffset,
            $username,
            $file,
            $line
        );
    }

    /**
     * Formats the error reported for a TODO comment that is not attributed to a username.
     */
    public function buildMissingUsernameError(
        string $comment,
        int $startLine,
        int $wholeMatchStartOffset,
        ?string $file = null,
        ?int $line = null
    ): \PHPStan\Rules\IdentifierRuleError {
        return $this->build(
            $comment,
            $startLine,
            'Missing TODO author. Expected an @-prefixed username, e.g. "TODO@john".',
            'missingUsername',
            null,
            $wholeMatchStartOffset,
            null,
            $file,
            $line
        );
    }

    private function build(
        string $comment,
        int $startLine,
        string $errorMessage,
        string $errorIdentifier,
        ?string $tip,
        int $wholeMatchStartOffset,
        ?string $username,
        ?string $file,
        ?int $line
    ): \PHPStan\Rules\IdentifierRuleError {
        // Attribute the error to the TODO author, when present, e.g.
        // "Todo by @john expired on 2023-12-14: fix it.".
        if (null !== $username && '' !== $username) {
            // the text-less variants start with "Comment "; drop it so the author reads as the subject
            if (0 === strncmp($errorMessage, 'Comment ', 8)) {
                $errorMessage = substr($errorMessage, 8);
            } else {
                $errorMessage = lcfirst($errorMessage);
            }
            $errorMessage = 'Todo by @' . $username . ' ' . $errorMessage;
        }

        // Count the number of newlines between the start of the whole comment, and the start of the match.
        $newLines = substr_count($comment, "\n", 0, $wholeMatchStartOffset);

        // Set the message line to match the line the comment actually starts on.
        $messageLine = $startLine + $newLines;

        $errBuilder = RuleErrorBuilder::message($errorMessage)
            ->line($messageLine)
            ->identifier(self::ERROR_IDENTIFIER_PREFIX.$errorIdentifier);

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
