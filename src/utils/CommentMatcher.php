<?php

namespace staabm\PHPStanTodoBy\utils;

use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Node\VirtualNode;
use RuntimeException;

use function count;

final class CommentMatcher
{
    /**
     * @return iterable<Comment, array<mixed>>
     */
    public static function matchComments(Node $node, string $pattern): iterable
    {
        if (
            $node instanceof Node\Stmt\InlineHTML
            || $node instanceof Node\Name
            || $node instanceof Node\Identifier
            || $node instanceof \PHPStan\Node\CollectedDataNode // see https://github.com/phpstan/phpstan/discussions/11701
        ) {
            // prevent unnecessary work / reduce memory consumption
            return [];
        }

        if (
            $node instanceof VirtualNode
            || $node instanceof Node\Expr
        ) {
            // prevent duplicate errors
            return [];
        }

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
                false === preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)
                || 0 === count($matches)
            ) {
                if (PREG_NO_ERROR !== preg_last_error()) {
                    throw new RuntimeException('Error in PCRE: '. preg_last_error_msg());
                }

                continue;
            }

            yield $comment => $matches;
        }
    }
}
