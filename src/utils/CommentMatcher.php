<?php

namespace staabm\PHPStanTodoBy\utils;

use PhpParser\Comment;
use PHPStan\Node\VirtualNode;
use PhpParser\Node;

final class CommentMatcher
{
    /**
     * @return iterable<Comment, array<mixed>>
     */
    public static function matchComments(Node $node, string $pattern): iterable
    {
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
                preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === false
                || count($matches) === 0
            ) {
                if (preg_last_error() !== PREG_NO_ERROR) {
                    throw new \RuntimeException('Error in PCRE: '. preg_last_error_msg());
                }

                continue;
            }

            yield $comment => $matches;
        }
    }
}
