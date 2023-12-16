<?php

namespace staabm\PHPStanTodoBy;

use PhpParser\Node;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
final class TodoByRule implements Rule
{
    private const PATTERN = '/^TODO:?\s*([0-9]{4}-[0-9]{2}-[0-9]{2}):?(.*)$/';

    private int $now;
    private bool $nonIgnorable;

    public function __construct(bool $nonIgnorable)
    {
        $this->now = time();
        $this->nonIgnorable = $nonIgnorable;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(\PhpParser\Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if (
            $node instanceof VirtualNode
            || $node instanceof Node\Expr
        ) {
            // prevent duplicate errors
            return [];
        }

        $comments = $node->getComments();
        if (count($comments) === 0) {
            return [];
        }

        $errors = [];
        foreach($comments as $comment) {
            $text = ltrim($comment->getText(), "\t /");
            if (!str_starts_with($text, 'TODO')) {
                continue;
            }

            if (preg_match(self::PATTERN, $text, $matches) !== 1) {
                continue;
            }

            $date = $matches[1];
            $todoText = trim($matches[2]);
            if (strtotime($date) > $this->now) {
                continue;
            }

            if ($todoText === '') {
                $errorMessage = 'comment expired on '. $date .'.';
            } else {
                $errorMessage = "comment '$todoText' expired on ". $date .'.';
            }

            $errBuilder = RuleErrorBuilder::message($errorMessage)->line($comment->getStartLine());
            if ($this->nonIgnorable) {
                $errBuilder->nonIgnorable();
            }
            $errors[] = $errBuilder->build();
        }

        return $errors;
    }
}
